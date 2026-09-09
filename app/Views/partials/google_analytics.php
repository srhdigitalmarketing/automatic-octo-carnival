<?php
$ga4Id = strtoupper(trim((string) get_config('ga4_measurement_id')));
$ga4Scope = get_config('ga4_scope') === 'public' ? 'public' : 'embed';
if (get_config('ga4_enabled') && preg_match('/^G-[A-Z0-9]{4,20}$/D', $ga4Id)
    && (($ga4Context ?? '') === 'embed' || ($ga4Scope === 'public' && ($ga4Context ?? '') === 'public'))): ?>
<script defer src="<?= esc(site_url('/js/google-analytics.js?v=20260909-1'), 'attr') ?>" data-ga4-id="<?= esc($ga4Id, 'attr') ?>" data-ga4-page="<?= ($ga4Context ?? '') === 'embed' ? 'Embed player' : 'Public page' ?>"></script>
<?php endif ?>
