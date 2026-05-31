<?php
/**
 * uninstall.php – runs when the plugin is removed from SkaDate.
 *
 * Drops the plugin's table and removes all registered language strings
 * so the database is left in a clean state.
 */

declare(strict_types=1);

\OW::getDbo()->query(
    'DROP TABLE IF EXISTS `' . \OW_DB_PREFIX . 'promo_banners`'
);

\BOL_LanguageService::getInstance()->deletePluginKeys('promo_banner');
