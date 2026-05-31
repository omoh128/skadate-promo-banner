# SkaDate Profile-Based Promo Banner Plugin

A clean-architecture WordPress/SkaDate plugin that displays a targeted promotional banner to authenticated users based on their **sex** and **language** profile attributes.

---

## Architecture Overview

```
skadate-promo-banner/
│
├── init.php                   ← Platform bootstrap (routes, events, assets)
├── install.php                ← DB schema + language strings (runs once on activation)
├── uninstall.php              ← Teardown
├── plugin.xml                 ← SkaDate plugin manifest
├── composer.json              ← PSR-4 autoload config
│
├── src/                       ← PSR-4 namespace: PromoBanner\
│   ├── Dto/
│   │   └── BannerDto.php      ← Immutable value object (raw DB row → typed object)
│   │
│   ├── Bol/                   ← Business Object Layer
│   │   ├── BannerDao.php      ← SQL: promo_banners table
│   │   ├── UserProfileDao.php ← SQL: ow_base_user + ow_base_question_data (read-only)
│   │   └── BannerService.php  ← Targeting logic; single entry point for controllers
│   │
│   ├── Components/
│   │   └── AdminPanel.php     ← Native OW_Component; owns the admin template
│   │
│   └── Controllers/
│       └── AdminController.php ← HTTP actions: index / save / delete
│
├── views/
│   └── admin/
│       └── admin_panel.html   ← Smarty template (banner list + create form)
│
└── static/
    ├── css/banner.css         ← Front-end banner styles
    └── js/banner.js           ← Dismiss behaviour + sessionStorage
```

---

## SkaDate/Oxwall Integration Points

| Mechanism | Where Used |
|---|---|
| `OW_BaseDao` | `BannerDao` — platform DB wrapper with `queryForRow` / `queryForList` |
| `OW_Component` | `AdminPanel` — native component render pipeline |
| `OW_ActionController` | `PROMOBANNER_CTRL_Admin` — platform HTTP dispatcher |
| `OW_Route` + `OW::getRouter()` | Registers `/admin/promo-banner` in `init.php` |
| `OW_EventManager::ON_BEFORE_DOCUMENT_RENDER` | Injects banner data + static assets |
| `OW_EventManager::ON_PLUGINS_INIT` | Adds admin menu item |
| `BOL_LanguageService` | Language string registration in `install.php` |
| `OW::getFeedback()` | Flash messages after form submit |
| `OW::getSession()` | CSRF token validation |
| Smarty templating | `views/admin/admin_panel.html` |

---

## Database Schema

```sql
CREATE TABLE `ow_promo_banners` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`           VARCHAR(120) NOT NULL,
    `body`            TEXT         NOT NULL,
    `cta_url`         VARCHAR(255) NOT NULL,
    `cta_label`       VARCHAR(60)  NOT NULL,
    `target_sex`      ENUM('male','female','any') NOT NULL DEFAULT 'any',
    `target_language` VARCHAR(10)  NOT NULL DEFAULT 'any',
    `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_is_active` (`is_active`)
);
```

---

## Targeting Logic

`BannerService::userQualifiesForBanner(int $userId): bool`

```
banner.target_sex  == 'any'  OR  user's sex  == banner.target_sex
                              AND
banner.target_language == 'any'  OR  user's language == banner.target_language
```

User profile attributes are read from SkaDate's core tables:

- **Sex** → `ow_base_question_data` where `questionName = 'sex'`  
  (`1` = male, `2` = female)
- **Language** → `ow_base_user.language_id` resolved via `BOL_LanguageService`

---

## Installation

```bash
# 1. Clone into your SkaDate plugins directory
cd /path/to/skadate/ow_plugins
git clone <repo> promo_banner

# 2. Install Composer dependencies
cd promo_banner
composer install --no-dev --optimize-autoloader

# 3. Activate via SkaDate Admin → Plugins → Promo Banner → Activate
```

---

## Clean Code Principles Applied

- **Single Responsibility** — DAO handles only SQL; Service handles only business logic; Controller handles only HTTP
- **Immutable DTO** — `BannerDto` uses `readonly` properties; no accidental mutation
- **Dependency via Singleton** — Follows the OW platform pattern; Service composes its own DAOs
- **Type Safety** — `declare(strict_types=1)` on every file; typed return signatures throughout
- **CSRF Protection** — Every POST action validates `ow_security_token` via `hash_equals`
- **Input Validation** — `BannerService::validateBannerData()` throws typed exceptions; controller catches and surfaces them as user-facing feedback
- **No Business Logic in Templates** — Smarty templates receive pre-computed view data only
