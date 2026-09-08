# StreamHG integration

Official documentation: https://streamhg.com/api.html
API root: https://streamhgapi.com/api

Deploy with git pull origin main and php spark migrate. The migration removes VidHide/EarnVids API credentials and detaches their API associations, preserving existing video links. Add StreamHG in API & R2 Storage with a new StreamHG API key and the embed hostnames used by your videos. Old provider credentials are not reused or transmitted to StreamHG.

Title search uses GET file/list with key and title, then a batched GET file/info with key and file_code. Only playable matching records are offered. Selecting a result adds its provider-returned player URL without changing metadata or existing links. File status and connection checks use file/info and account/info. Keys stay on the server. Existing weekly health-check scheduling is unchanged.

Local fixture tests cover parsing, health status and provider matching; no live StreamHG account has been tested.
