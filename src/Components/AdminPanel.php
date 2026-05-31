<?php

declare(strict_types=1);

namespace PromoBanner\Components;

/**
 * AdminPanel Component
 *
 * A native SkaDate/Oxwall component that owns the admin dashboard template.
 * Extending OW_Component wires this class into the platform's component
 * render pipeline so the template receives all assigned variables
 * automatically when render() is called.
 *
 * Template: views/admin/admin_panel.html
 */
final class AdminPanel extends \OW_Component
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Called by the platform renderer before the template is compiled.
     * Use this hook for any last-minute data preparation.
     */
    public function onBeforeRender(): void
    {
        // Expose supported sex options to the template for the <select>.
        $lang = \OW::getLanguage();
        $this->assign('sexOptions', [
            'any'    => $lang->text('promo_banner', 'sex_any'),
            'male'   => $lang->text('promo_banner', 'sex_male'),
            'female' => $lang->text('promo_banner', 'sex_female'),
        ]);

        // Expose common language options. In a full implementation this
        // would be fetched dynamically from BOL_LanguageService.
        $this->assign('languageOptions', [
            'any' => $lang->text('promo_banner', 'language_any'),
            'en'  => 'English',
            'de'  => 'Deutsch',
            'fr'  => 'Français',
            'es'  => 'Español',
            'pt'  => 'Português',
            'ru'  => 'Русский',
        ]);
    }

    /**
     * Resolve the path to the admin panel template file.
     *
     * OW_Component::getTemplatePath() is called by the platform renderer
     * to locate the Smarty template on disk.
     */
    public function getTemplatePath(): string
    {
        return \OW::getPluginManager()
            ->getPlugin('promo_banner')
            ->getRootDir() . 'views/admin/admin_panel.html';
    }
}
