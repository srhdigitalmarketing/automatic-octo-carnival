<?php
$values = [];
function get_config($key) { return $GLOBALS['values'][$key] ?? null; }
function esc($value, $context = null) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function site_url($path) { return 'https://example.test' . $path; }
function renderTag($context) { $ga4Context = $context; ob_start(); include __DIR__.'/../app/Views/partials/google_analytics.php'; return ob_get_clean(); }
function check($value, $message) { if (!$value) throw new RuntimeException($message); }
check(trim(renderTag('embed')) === '', 'Unconfigured page loaded analytics');
$values = ['ga4_enabled'=>true, 'ga4_measurement_id'=>'G-TEST12345', 'ga4_scope'=>'embed'];
check(strpos(renderTag('embed'), 'defer') !== false, 'Embed tag missing');
check(trim(renderTag('public')) === '', 'Embed-only scope leaked onto homepage');
$values['ga4_scope']='public';
check(strpos(renderTag('public'), 'G-TEST12345') !== false, 'Public scope missing');
check(trim(renderTag('admin')) === '', 'Admin context tracked');
$values['ga4_enabled']=false; check(trim(renderTag('embed')) === '', 'Disabled analytics loaded');
$values['ga4_enabled']=true; $values['ga4_measurement_id']='G-X\" onload=alert(1)';
check(trim(renderTag('embed')) === '', 'Malformed ID emitted a script');
echo "PASS: default-off, embed/public scope, admin exclusion, deferred tag and invalid ID protection.\n";
