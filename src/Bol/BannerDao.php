<?php

declare(strict_types=1);

namespace PromoBanner\Bol;

use PromoBanner\Dto\BannerDto;

/**
 * Data Access Object for the `promo_banners` table.
 *
 * Thin layer: every method executes exactly one query and returns typed
 * value objects or primitives. No business logic lives here.
 */
final class BannerDao extends \OW_BaseDao
{
    private const TABLE = 'promo_banners';

    // ── Singleton ────────────────────────────────────────────────────────────

    private static ?self $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        parent::__construct();
    }

    // ── OW_BaseDao contract ──────────────────────────────────────────────────

    public function getTableName(): string
    {
        return \OW_DB_PREFIX . self::TABLE;
    }

    public function getDtoClassName(): string
    {
        return BannerDto::class;
    }

    // ── Queries ──────────────────────────────────────────────────────────────

    /**
     * Return the single active banner, or null when none is configured.
     */
    public function findActiveBanner(): ?BannerDto
    {
        $sql = 'SELECT * FROM `' . $this->getTableName() . '`
                WHERE `is_active` = 1
                ORDER BY `created_at` DESC
                LIMIT 1';

        $row = $this->dbo->queryForRow($sql);

        return $row ? new BannerDto($row) : null;
    }

    /**
     * Persist a new banner and return its newly assigned id.
     */
    public function insert(array $data): int
    {
        $sql = 'INSERT INTO `' . $this->getTableName() . '`
                    (`title`, `body`, `cta_url`, `cta_label`,
                     `target_sex`, `target_language`, `is_active`, `created_at`)
                VALUES
                    (:title, :body, :cta_url, :cta_label,
                     :target_sex, :target_language, :is_active, NOW())';

        $this->dbo->query($sql, [
            ':title'           => $data['title'],
            ':body'            => $data['body'],
            ':cta_url'         => $data['cta_url'],
            ':cta_label'       => $data['cta_label'],
            ':target_sex'      => $data['target_sex'],
            ':target_language' => $data['target_language'],
            ':is_active'       => (int) ($data['is_active'] ?? 1),
        ]);

        return (int) $this->dbo->getInsertId();
    }

    /**
     * Deactivate every banner except the given id (ensures single active banner).
     */
    public function deactivateAllExcept(int $keepId): void
    {
        $sql = 'UPDATE `' . $this->getTableName() . '`
                SET `is_active` = 0
                WHERE `id` != :keep_id';

        $this->dbo->query($sql, [':keep_id' => $keepId]);
    }

    /**
     * Return all banners for the admin list view.
     *
     * @return BannerDto[]
     */
    public function findAll(): array
    {
        $sql  = 'SELECT * FROM `' . $this->getTableName() . '` ORDER BY `created_at` DESC';
        $rows = $this->dbo->queryForList($sql);

        return array_map(static fn(array $row): BannerDto => new BannerDto($row), $rows);
    }

    /**
     * Hard-delete a banner by primary key.
     */
    public function deleteById(int $id): void
    {
        $sql = 'DELETE FROM `' . $this->getTableName() . '` WHERE `id` = :id LIMIT 1';
        $this->dbo->query($sql, [':id' => $id]);
    }
}
