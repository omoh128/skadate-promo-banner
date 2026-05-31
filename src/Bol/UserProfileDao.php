<?php

declare(strict_types=1);

namespace PromoBanner\Bol;

/**
 * Read-only DAO that interrogates SkaDate's core user tables to retrieve
 * profile attributes needed for banner targeting.
 *
 * Tables accessed (read-only):
 *   - ow_base_user            – account row with language code
 *   - ow_base_question_data   – EAV-style profile field answers (sex stored here)
 */
final class UserProfileDao
{
    /** SkaDate EAV question name that stores biological sex. */
    private const SEX_QUESTION_NAME = 'sex';

    private static ?self $instance = null;

    /** @var \OW_Database */
    private \OW_Database $dbo;

    private function __construct()
    {
        $this->dbo = \OW::getDbo();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Return the user's interface language code (e.g. 'en', 'fr').
     * Falls back to the platform default when the user row is not found.
     */
    public function getUserLanguage(int $userId): string
    {
        $sql = 'SELECT `language_id`
                FROM `' . \OW_DB_PREFIX . 'base_user`
                WHERE `id` = :id
                LIMIT 1';

        $row = $this->dbo->queryForRow($sql, [':id' => $userId]);

        if (empty($row['language_id'])) {
            return $this->getPlatformDefaultLanguage();
        }

        return $this->resolveLanguageTag((int) $row['language_id']);
    }

    /**
     * Return 'male', 'female', or 'unknown' for the given user.
     *
     * SkaDate stores sex in `ow_base_question_data` as an integer:
     *   1 → male  |  2 → female
     */
    public function getUserSex(int $userId): string
    {
        $sql = 'SELECT `value`
                FROM `' . \OW_DB_PREFIX . 'base_question_data`
                WHERE `userId`       = :user_id
                  AND `questionName` = :question
                LIMIT 1';

        $row = $this->dbo->queryForRow($sql, [
            ':user_id'  => $userId,
            ':question' => self::SEX_QUESTION_NAME,
        ]);

        return match ((int) ($row['value'] ?? 0)) {
            1       => 'male',
            2       => 'female',
            default => 'unknown',
        };
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function getPlatformDefaultLanguage(): string
    {
        return \BOL_LanguageService::getInstance()
            ->findDefault()
            ?->tag ?? 'en';
    }

    private function resolveLanguageTag(int $languageId): string
    {
        $language = \BOL_LanguageService::getInstance()->findById($languageId);
        return $language?->tag ?? 'en';
    }
}
