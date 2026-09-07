<?php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class GoogleAnalytics extends BaseConfig
{
    public $measurementId = 'G-X6BCET0FDZ';
    public $propertyId = '15734353311';
    public $credentialsPath = '';
    public $timezone = 'Asia/Jakarta';

    public function __construct()
    {
        parent::__construct();
        $this->measurementId = (string) env('ga4.measurementId', $this->measurementId);
        $this->propertyId = (string) env('ga4.propertyId', $this->propertyId);
        $this->credentialsPath = (string) env('ga4.credentialsPath', WRITEPATH . 'credentials/ga4-service-account.json');
        $this->timezone = (string) env('ga4.timezone', $this->timezone);
    }
}
