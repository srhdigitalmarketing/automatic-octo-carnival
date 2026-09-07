# UPNShare integration

The application uses UPNShare API v1 only for optional health checks of links
that have an `upnshare_video_id`. The player URL itself remains in `links.link`,
so it can be rotated with other video hosts.

Add these values to the server environment (not to Git):

```ini
upnshare.baseUrl = "https://upnshare.com"
upnshare.apiToken = "your-active-upnshare-token"
```

UPNShare documents `api-token` as the authentication header. When a video ID is
present, the application calls `GET /api/v1/video/manage/{id}` before selecting
that host. A 200 response with a video ID is treated as available; failed checks
advance to the next host. Use the normal link editor to enter the host playback
URL, its priority, and the optional UPNShare video ID.
