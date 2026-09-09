(function () {
    'use strict';
    var ownScript = document.currentScript;
    var id = ownScript && ownScript.getAttribute('data-ga4-id');
    var title = ownScript && ownScript.getAttribute('data-ga4-page');
    if (!/^G-[A-Z0-9]{4,20}$/.test(id || '') || window.__oktoGa4Scheduled) return;
    window.__oktoGa4Scheduled = true;

    function start() {
        try {
            // Use one installation only; custom GA/GTM owns measurement if present.
            if (typeof window.gtag === 'function' || window.google_tag_manager) return;
            var scripts = document.querySelectorAll('script[src]');
            for (var i = 0; i < scripts.length; i++) {
                if (/googletagmanager\.com\/(gtag\/js|gtm\.js)/i.test(scripts[i].src)) return;
            }
            window.dataLayer = window.dataLayer || [];
            window.gtag = function () { window.dataLayer.push(arguments); };
            var referrer = '';
            try { referrer = document.referrer ? new URL(document.referrer).origin : ''; } catch (ignored) {}
            window.gtag('js', new Date());
            window.gtag('config', id, {
                send_page_view: true,
                page_location: window.location.origin + window.location.pathname,
                page_referrer: referrer,
                page_title: title || 'Public page',
                allow_google_signals: false,
                allow_ad_personalization_signals: false
            });
            var tag = document.createElement('script');
            tag.async = true;
            tag.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
            tag.referrerPolicy = 'no-referrer';
            tag.onerror = function () {}; // Optional analytics must never retry or block the player.
            document.head.appendChild(tag);
        } catch (ignored) { /* Analytics failure must not interrupt the application. */ }
    }
    function schedule() {
        if (window.requestIdleCallback) window.requestIdleCallback(start, { timeout: 2000 });
        else window.setTimeout(start, 0);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', schedule, { once: true });
    else schedule();
})();
