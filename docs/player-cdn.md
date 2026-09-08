# Player assets via Bunny

Embed uses https://oktostream.b-cdn.net for local theme JavaScript, CSS and the empty-state image. Keep the Pull Zone origin pointed at the website root so /themes/pirate/... resolves. Existing third-party libraries keep their current CDN URLs. Relative assets inside theme CSS resolve through Bunny as well.

HTML, BASE_URL, AJAX, captcha, video host iframes, and R2 poster URLs remain unchanged. CDN script/CSS/image load errors retry the corresponding origin URL once. This does not detect a successful but stale CDN response. Preserve query strings in the cache key for asset versions, or purge Bunny after deployment when changing these files. Clearing php spark cache:clear clears application cache, not Bunny edge cache.

JavaScript and main CSS responded HTTP 200 from Bunny during setup. Live traffic latency and geographic performance have not been benchmarked.
