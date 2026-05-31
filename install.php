<?php
/**
 * install.php – runs ONCE when the plugin is first activated via
 * the SkaDate admin panel (Admin → Plugins → Activate).
 *
 * Responsibilities:
 *   1. Create the promo_banners table.
 *   2. Seed default language strings.
 */

declare(strict_types=1);

// ── 1. Database schema ───────────────────────────────────────────────────────

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$_DB_PREFIX_}promo_banners` (
    `id`              INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(120) NOT NULL,
    `body`            TEXT         NOT NULL,
    `cta_url`         VARCHAR(255) NOT NULL,
    `cta_label`       VARCHAR(60)  NOT NULL,
    `target_sex`      ENUM('male','female','any') NOT NULL DEFAULT 'any',
    `target_language` VARCHAR(10)  NOT NULL DEFAULT 'any'
                          COMMENT 'ISO-639-1 code or the literal string "any"',
    `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

\OW::getDbo()->query($sql);

// ── 2. Language strings ──────────────────────────────────────────────────────

$langService = \BOL_LanguageService::getInstance();
$plugin      = 'promo_banner';

$strings = [
    'admin_menu_label'        => 'Promo Banner',
    'admin_page_title'        => 'Promo Banner Manager',
    'admin_existing_banners'  => 'Existing Banners',
    'admin_create_banner'     => 'Create New Banner',
    'no_banners_yet'          => 'No banners have been created yet.',
    'banner_saved_success'    => 'Banner saved successfully.',
    'banner_deleted_success'  => 'Banner deleted.',
    'admin_access_denied'     => 'Access denied.',
    'confirm_delete'          => 'Delete this banner?',
    // Form fields
    'field_title'             => 'Banner Title',
    'field_body'              => 'Banner Body',
    'field_cta_url'           => 'CTA URL',
    'field_cta_label'         => 'CTA Button Label',
    'field_target_sex'        => 'Target Sex',
    'field_target_language'   => 'Target Language',
    'field_make_active'       => 'Make this the active banner',
    'field_make_active_hint'  => 'Activating this banner will deactivate all others.',
    'btn_save_banner'         => 'Save Banner',
    // Table columns
    'col_title'               => 'Title',
    'col_target_sex'          => 'Sex Filter',
    'col_target_language'     => 'Language Filter',
    'col_status'              => 'Status',
    'col_created'             => 'Created',
    'col_actions'             => 'Actions',
    'action_delete'           => 'Delete',
    // Status badges
    'status_active'           => 'Active',
    'status_inactive'         => 'Inactive',
    // Sex options
    'sex_any'                 => 'Any',
    'sex_male'                => 'Male',
    'sex_female'              => 'Female',
    // Language options
    'language_any'            => 'Any Language',
];

foreach ($strings as $key => $value) {
    $langService->addValue('en', $plugin, $key, $value);
}
