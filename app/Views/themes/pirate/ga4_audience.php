<?php $ga4 = config('GoogleAnalytics'); ?>
<?php if (preg_match('/^G-[A-Z0-9]+$/', $ga4->measurementId)): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($ga4->measurementId, 'attr') ?>"></script>
<script>
(function () {
    if (window.embedGa4Configured) return;
    window.embedGa4Configured = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    var measurementId = <?= json_encode($ga4->measurementId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    gtag('js', new Date());
    gtag('config', measurementId, {
        send_page_view: false,
        page_location: window.location.origin + '/embed-player',
        page_title: 'Embed player',
        page_referrer: '',
        allow_google_signals: false,
        allow_ad_personalization_signals: false
    });
    gtag('event', 'page_view', {send_to: measurementId});
})();
</script>
<?php endif; ?>
