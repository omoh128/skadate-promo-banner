<?php
/**
 * SkaDate Profile-Based Promo Banner Plugin
 *
 * @package     PromoBanner
 * @author      Plugin Author
 * @version     1.0.0
 *
 * Registers all plugin components with the Oxwall/SkaDate event system.
 * Entry point loaded by the platform's plugin manager.
 */

declare(strict_types=1);

// ── Composer autoloader ──────────────────────────────────────────────────────
require_once __DIR__ . '/vendor/autoload.php';

use PromoBanner\Bol\BannerService;
use PromoBanner\Components\AdminPanel;

// ── Route registration ───────────────────────────────────────────────────────
OW::getRouter()->addRoute(
    new OW_Route(
        'promo_banner_admin',
        'admin/promo-banner',
        'PROMOBANNER_CTRL_Admin',
        'index'
    )
);

// ── Admin menu item ──────────────────────────────────────────────────────────
OW::getEventManager()->bind(
    OW_EventManager::ON_PLUGINS_INIT,
    static function () : void {
        $adminMenu = BOL_NavigationService::getInstance()->findMenuItemByKey('admin_plugins_menu');

        if ($adminMenu !== null) {
            $menuItem        = new BOL_MenuItem();
            $menuItem->key   = 'promo_banner_admin_menu';
            $menuItem->label = OW::getLanguage()->text('promo_banner', 'admin_menu_label');
            $menuItem->menuId = $adminMenu->id;
            $menuItem->routePath = OW::getRouter()->urlForRoute('promo_banner_admin');
            $menuItem->order = 10;
        }
    }
);

// ── Inject banner into page header on every front-end page ───────────────────
OW::getEventManager()->bind(
    OW_EventManager::ON_BEFORE_DOCUMENT_RENDER,
    static function (OW_Event $event) : void {
        // Only act for authenticated users
        if (!OW::getUser()->isAuthenticated()) {
            return;
        }

        $userId  = (int) OW::getUser()->getId();
        $service = BannerService::getInstance();

        if (!$service->userQualifiesForBanner($userId)) {
            return;
        }

        $activeBanner = $service->getActiveBanner();

        if ($activeBanner === null) {
            return;
        }

        $component = new AdminPanel();
        $component->assign('banner', $activeBanner);
        OW::getDocument()->addScriptDeclarationBeforeIncludes(
            $component->render()
        );
    }
);

// ── Static assets ────────────────────────────────────────────────────────────
OW::getEventManager()->bind(
    OW_EventManager::ON_BEFORE_DOCUMENT_RENDER,
    static function () : void {
        OW::getDocument()->addStyleSheet(
            OW::getPluginManager()->getPlugin('promo_banner')->getStaticCssUrl() . 'banner.css'
        );
        OW::getDocument()->addScript(
            OW::getPluginManager()->getPlugin('promo_banner')->getStaticJsUrl() . 'banner.js'
        );
    }
);
