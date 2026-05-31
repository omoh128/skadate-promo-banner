<?php

declare(strict_types=1);

namespace PromoBanner\Controllers;

use PromoBanner\Bol\BannerService;
use PromoBanner\Components\AdminPanel;

/**
 * Admin Controller
 *
 * Handles all HTTP interactions for the plugin's admin dashboard.
 * Registered as 'PROMOBANNER_CTRL_Admin' in init.php so the OW router
 * can resolve it via the platform's class-name convention.
 *
 * Actions:
 *   GET  /admin/promo-banner         → index()   – list + create form
 *   POST /admin/promo-banner/save    → save()    – persist new banner
 *   POST /admin/promo-banner/delete  → delete()  – remove a banner
 */
class PROMOBANNER_CTRL_Admin extends \OW_ActionController
{
    private BannerService $service;

    public function __construct()
    {
        parent::__construct();

        // Guard: only platform admins may access this controller.
        if (!\OW::getUser()->isAdmin()) {
            \OW::getFeedback()->error(
                \OW::getLanguage()->text('promo_banner', 'admin_access_denied')
            );
            $this->redirect(\OW::getRouter()->urlForRoute('base_index'));
        }

        $this->service = BannerService::getInstance();
    }

    // ── index ────────────────────────────────────────────────────────────────

    /**
     * GET /admin/promo-banner
     *
     * Renders the admin dashboard: existing banners list + creation form.
     */
    public function index(): void
    {
        $this->setPageHeading(
            \OW::getLanguage()->text('promo_banner', 'admin_page_title')
        );
        $this->setPageTitle(
            \OW::getLanguage()->text('promo_banner', 'admin_page_title')
        );

        $component = new AdminPanel();
        $component->assign('banners', $this->service->getAllBanners());
        $component->assign('formAction', \OW::getRouter()->urlForRoute('promo_banner_admin') . '/save');
        $component->assign('deleteAction', \OW::getRouter()->urlForRoute('promo_banner_admin') . '/delete');
        $component->assign('csrfToken', \OW::getSession()->get('ow_security_token') ?? '');

        $this->addComponent('adminPanel', $component);
    }

    // ── save ─────────────────────────────────────────────────────────────────

    /**
     * POST /admin/promo-banner/save
     *
     * Validates and persists a new banner, then redirects back to index.
     */
    public function save(): void
    {
        $this->enforcePost();
        $this->verifyCsrf();

        $data = $this->collectFormData();

        try {
            $this->service->createBanner($data);
            \OW::getFeedback()->info(
                \OW::getLanguage()->text('promo_banner', 'banner_saved_success')
            );
        } catch (\InvalidArgumentException $e) {
            \OW::getFeedback()->error($e->getMessage());
        }

        $this->redirect(\OW::getRouter()->urlForRoute('promo_banner_admin'));
    }

    // ── delete ───────────────────────────────────────────────────────────────

    /**
     * POST /admin/promo-banner/delete
     *
     * Deletes the banner with the given id and redirects.
     */
    public function delete(): void
    {
        $this->enforcePost();
        $this->verifyCsrf();

        $id = (int) \OW::getRequest()->post('id');

        if ($id > 0) {
            $this->service->deleteBanner($id);
            \OW::getFeedback()->info(
                \OW::getLanguage()->text('promo_banner', 'banner_deleted_success')
            );
        }

        $this->redirect(\OW::getRouter()->urlForRoute('promo_banner_admin'));
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function enforcePost(): void
    {
        if (!\OW::getRequest()->isPost()) {
            $this->redirect(\OW::getRouter()->urlForRoute('promo_banner_admin'));
        }
    }

    private function verifyCsrf(): void
    {
        $token         = \OW::getRequest()->post('csrf_token', '');
        $sessionToken  = \OW::getSession()->get('ow_security_token') ?? '';

        if (!hash_equals($sessionToken, (string) $token)) {
            \OW::getFeedback()->error(
                \OW::getLanguage()->text('base', 'csrf_token_error')
            );
            $this->redirect(\OW::getRouter()->urlForRoute('promo_banner_admin'));
        }
    }

    private function collectFormData(): array
    {
        $post = \OW::getRequest()->post(...);

        return [
            'title'           => trim((string) $post('title',           '')),
            'body'            => trim((string) $post('body',            '')),
            'cta_url'         => trim((string) $post('cta_url',         '')),
            'cta_label'       => trim((string) $post('cta_label',       '')),
            'target_sex'      => trim((string) $post('target_sex',      'any')),
            'target_language' => trim((string) $post('target_language', 'any')),
            'is_active'       => (bool) $post('is_active', false),
        ];
    }
}
