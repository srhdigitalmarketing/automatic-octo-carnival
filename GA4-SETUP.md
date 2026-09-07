# Google Analytics 4 audience setup

The embed player uses Measurement ID `G-X6BCET0FDZ`. Dashboard reports use Property ID `15734353311`.
Confirm the numeric ID in GA4 **Admin > Property details**; a web stream ID is a different identifier.

## Required server setup

1. In Google Cloud Console, select/create a project and enable **Google Analytics Data API**.
2. Create a service account. It does not need project Owner or Editor permissions.
3. In GA4 **Admin > Property access management**, add the service account email with the **Viewer** role for the property above.
4. Create/download the service account JSON key and transfer it privately to the application server. Do not send the key in chat or commit it.
5. Store it outside `public/`, preferably outside the web root, readable only by the deployment user/PHP process. Set `.env`:

```ini
ga4.measurementId = G-X6BCET0FDZ
ga4.propertyId = 15734353311
ga4.timezone = Asia/Jakarta
ga4.credentialsPath = /absolute/private/path/ga4-service-account.json
```

If `ga4.credentialsPath` is omitted, the default is `writable/credentials/ga4-service-account.json` (ignored by Git). Serve only the `public/` directory.
Match `ga4.timezone` to the property's reporting timezone.

6. Deploy the code. Open an embed player with stream links, then check GA4 Realtime. The tracked page is `/embed-player` with the title `Embed player`.
7. Open the application dashboard. It reads three reports in one Data API batch: daily users, users by device, and the unique 30-day total. Reports may take 24–48 hours to finish processing.

Use one tracker for this property. If a Google tag was also pasted into custom header/footer code or Tag Manager, remove the duplicate. Turn off Enhanced Measurement for this dedicated stream if only the basic audience statistics are needed. The built-in tracker supplies a generic page URL/title and an empty referrer instead of video titles and query strings.

## Storage and behavior

- `traffic_daily_visitors` receives no new rows and is no longer read by the dashboard. Existing rows are not deleted by deployment.
- Audience Overview and Devices use GA4 only; there is no local visitor-table fallback.
- Daily Player Analytics reuses GA4 daily users. Its impression/play counters and the short-lived `live_traffic` table remain local.
- Only a compact report cache is stored in `writable/cache/ga4-audience-*.json`. OAuth tokens are used in memory only. Successful reports cache for 5 minutes; failures for 1 minute.
- Missing credentials/API permissions show a setup notice, not fabricated analytics.
- GA4 measures users differently from the old anonymous browser keys. Historical local records do not transfer automatically. Device totals and summed daily users can differ from the unique 30-day total.
- Blocking analytics scripts or browser storage can prevent tracking, particularly in embedded frames. Test a real deployed embed.

Old visitor rows can be archived and cleaned up separately after verifying GA4. No destructive database cleanup is included.

## Verification

```sh
php tests/ga4_audience_test.php
```

The test uses generated temporary credentials and simulated Google responses. It does not access the database or send analytics to Google.

References:
- https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart
- https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/properties/batchRunReports
- https://developers.google.com/identity/protocols/oauth2/service-account
- https://support.google.com/analytics/answer/11198161
