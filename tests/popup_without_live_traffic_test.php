<?php
namespace App\Models {
    class PopupAdUnitModel {
        public function select($fields) { return $this; }
        public function where($field, $value) { return $this; }
        public function findAll() { return [['id'=>1], ['id'=>2]]; }
    }
}
namespace App\Libraries {
    class AdRevenueToday { public function latestSummary() { return ['unit_metrics'=>[1=>['impressions'=>2,'ecpm'=>1],2=>['impressions'=>300,'ecpm'=>2]]]; } }
    function db_connect() { throw new \RuntimeException('Traffic database must not be queried'); }
}
namespace {
require __DIR__.'/../app/Libraries/PopupAdSelector.php';
if ((new App\Libraries\PopupAdSelector())->selectId() !== 1) throw new RuntimeException('Ad selection regression');
echo "PASS: multiple ad networks can be selected without LiveTrafficModel or traffic database queries.\n";
}
