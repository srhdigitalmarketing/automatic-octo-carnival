# Player CDN

API & R2 Storage → Add CDN Hostname opens Settings → CDN. Enter a hostname such as a.cdn.com without scheme, port or path, select Aktif, and save. The default hostname is oktostream.b-cdn.net. Configure HTTPS and the website root as origin in your CDN service so /themes/pirate/... resolves.

Only local player JavaScript, CSS and the empty-state image use the configured CDN. Nonaktif returns these assets to the website origin. HTML, AJAX, video host iframes and R2 poster URLs keep their existing URLs. Failed CDN asset loads retry the origin once. Preserve query strings in CDN cache keys for asset versions, or purge your CDN after asset updates.

Page caching and clear-cache controls have been removed. Saving CDN settings does not clear internal application data or purge the CDN. The framework writable/cache directory remains necessary for login throttling, API checks and temporary import jobs; do not delete it.

## Upgrade from page cache settings

Finish any running grab/migration jobs before this one-time upgrade cleanup:

```sh
git pull origin main
php spark migrate
php spark cache:clear
```

The migration deletes only the three obsolete page-cache settings. The final command removes previously generated cached HTML and temporary cache entries once, so old embed pages no longer contain old asset URLs. CDN settings are preserved. No page HTML is cached by the application after this update.
