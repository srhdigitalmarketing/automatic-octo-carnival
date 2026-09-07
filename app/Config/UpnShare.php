<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * UPNShare API settings.
 *
 * Keep the token in the deployment environment. It must never be committed to
 * source control. Admin provider credentials are stored server-side in the database.
 */
class UpnShare extends BaseConfig
{
    public $baseUrl = 'https://upnshare.com';
    public $apiToken = '';
    public $linkHosts = 'upnshare.com';
    public $healthCacheSeconds = 300;
    public $requestTimeoutSeconds = 8;
    public $failureThreshold = 3;

    public function __construct()
    {
        parent::__construct();

        $this->linkHosts = (string) env('upnshare.linkHosts', $this->linkHosts);
        $baseUrl = env('upnshare.baseUrl');
        $apiToken = env('upnshare.apiToken');

        if (! empty($baseUrl)) {
            $this->baseUrl = rtrim($baseUrl, '/');
        }

        if (! empty($apiToken)) {
            $this->apiToken = $apiToken;
        }
    }
}
