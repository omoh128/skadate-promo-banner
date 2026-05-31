/**
 * banner.js – Promo Banner Plugin
 *
 * Injects the banner HTML into the page and handles dismiss / session storage.
 * The banner data is encoded as a JSON object on `window.PROMO_BANNER` which
 * is written by the PHP server-side render hook in init.php.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'pb_dismissed';

    /**
     * Return true if the user has dismissed this banner in the current
     * browser session.
     */
    function isDismissed(bannerId) {
        try {
            var dismissed = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
            return dismissed.indexOf(bannerId) !== -1;
        } catch (e) {
            return false;
        }
    }

    /**
     * Persist the dismissed banner id so the banner does not re-appear
     * on page navigation within the same session.
     */
    function markDismissed(bannerId) {
        try {
            var dismissed = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
            if (dismissed.indexOf(bannerId) === -1) {
                dismissed.push(bannerId);
            }
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(dismissed));
        } catch (e) {
            // sessionStorage not available – silently ignore.
        }
    }

    /**
     * Build the banner DOM element from the data object.
     *
     * @param {{ id: number, title: string, body: string, ctaUrl: string, ctaLabel: string }} data
     * @returns {HTMLElement}
     */
    function buildBanner(data) {
        var el = document.createElement('div');
        el.className = 'pb-banner';
        el.setAttribute('role', 'banner');
        el.setAttribute('aria-label', 'Promotional banner');

        var content = document.createElement('div');
        content.className = 'pb-banner__content';

        var title = document.createElement('p');
        title.className = 'pb-banner__title';
        title.textContent = data.title;

        var body = document.createElement('p');
        body.className = 'pb-banner__body';
        body.textContent = data.body;

        content.appendChild(title);
        content.appendChild(body);

        var cta = document.createElement('a');
        cta.className = 'pb-banner__cta';
        cta.href      = data.ctaUrl;
        cta.textContent = data.ctaLabel;
        cta.setAttribute('target', '_blank');
        cta.setAttribute('rel', 'noopener noreferrer');

        var dismiss = document.createElement('button');
        dismiss.className = 'pb-banner__dismiss';
        dismiss.setAttribute('aria-label', 'Dismiss banner');
        dismiss.textContent = '×';
        dismiss.addEventListener('click', function () {
            markDismissed(data.id);
            el.classList.add('pb-banner--dismissed');
            el.addEventListener('animationend', function () {
                el.remove();
            });
        });

        el.appendChild(content);
        el.appendChild(cta);
        el.appendChild(dismiss);

        return el;
    }

    /**
     * Insert the banner as the first child of the main content wrapper.
     * Falls back to document.body if the OW content container is absent.
     */
    function injectBanner(bannerEl) {
        // Try the typical SkaDate/Oxwall content container first.
        var container = document.getElementById('ow_page_content_area')
            || document.querySelector('.ow_page_content')
            || document.body;

        container.insertBefore(bannerEl, container.firstChild);
    }

    // ── Bootstrap ─────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', function () {
        var data = window.PROMO_BANNER;

        if (!data || !data.id) {
            return;
        }

        if (isDismissed(data.id)) {
            return;
        }

        injectBanner(buildBanner(data));
    });
}());
