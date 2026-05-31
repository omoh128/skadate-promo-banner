<?php

declare(strict_types=1);

namespace PromoBanner\Bol;

use PromoBanner\Dto\BannerDto;

/**
 * BannerService – the sole entry point for all banner-related business logic.
 *
 * Controllers and event listeners depend ONLY on this service; they never
 * touch DAOs directly.  This preserves the classic SkaDate/Oxwall
 * BOL (Business Object Layer) separation.
 */
final class BannerService
{
    private static ?self $instance = null;

    private BannerDao     $bannerDao;
    private UserProfileDao $profileDao;

    private function __construct(
        BannerDao      $bannerDao,
        UserProfileDao $profileDao
    ) {
        $this->bannerDao  = $bannerDao;
        $this->profileDao = $profileDao;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(
                BannerDao::getInstance(),
                UserProfileDao::getInstance()
            );
        }
        return self::$instance;
    }

    // ── Targeting logic ──────────────────────────────────────────────────────

    /**
     * Decide whether the given user should see the active promo banner.
     *
     * Rules:
     *   1. A banner set to target_sex = 'any' matches every user.
     *   2. A banner set to target_language = 'any' matches every language.
     *   3. Both conditions must be satisfied simultaneously.
     */
    public function userQualifiesForBanner(int $userId): bool
    {
        $banner = $this->getActiveBanner();

        if ($banner === null) {
            return false;
        }

        return $this->sexMatches($banner, $userId)
            && $this->languageMatches($banner, $userId);
    }

    // ── Read methods ─────────────────────────────────────────────────────────

    public function getActiveBanner(): ?BannerDto
    {
        return $this->bannerDao->findActiveBanner();
    }

    /**
     * @return BannerDto[]
     */
    public function getAllBanners(): array
    {
        return $this->bannerDao->findAll();
    }

    // ── Write methods ────────────────────────────────────────────────────────

    /**
     * Create a new banner and optionally make it the active one.
     *
     * @param array{
     *   title: string,
     *   body: string,
     *   cta_url: string,
     *   cta_label: string,
     *   target_sex: string,
     *   target_language: string,
     *   is_active: bool
     * } $data
     */
    public function createBanner(array $data): int
    {
        $this->validateBannerData($data);

        $newId = $this->bannerDao->insert($data);

        if (!empty($data['is_active'])) {
            $this->bannerDao->deactivateAllExcept($newId);
        }

        return $newId;
    }

    public function deleteBanner(int $id): void
    {
        $this->bannerDao->deleteById($id);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function sexMatches(BannerDto $banner, int $userId): bool
    {
        if ($banner->targetSex === 'any') {
            return true;
        }

        return $this->profileDao->getUserSex($userId) === $banner->targetSex;
    }

    private function languageMatches(BannerDto $banner, int $userId): bool
    {
        if ($banner->targetLanguage === 'any') {
            return true;
        }

        return $this->profileDao->getUserLanguage($userId) === $banner->targetLanguage;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateBannerData(array $data): void
    {
        $required = ['title', 'body', 'cta_url', 'cta_label', 'target_sex', 'target_language'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException(
                    sprintf('Banner field "%s" is required and cannot be empty.', $field)
                );
            }
        }

        $allowedSex = ['male', 'female', 'any'];
        if (!\in_array($data['target_sex'], $allowedSex, true)) {
            throw new \InvalidArgumentException(
                sprintf('target_sex must be one of: %s', implode(', ', $allowedSex))
            );
        }

        if (!filter_var($data['cta_url'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('cta_url must be a valid URL.');
        }
    }
}
