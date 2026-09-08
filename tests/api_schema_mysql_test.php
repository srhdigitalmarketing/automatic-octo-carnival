<?php
// Run only against an explicitly supplied disposable MySQL server.
if (!isset($argv[1])) { echo "SKIP: supply disposable MySQL port.\n"; exit(0); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli('127.0.0.1', 'root', '', '', (int) $argv[1]);
$name = 'schema_test_' . bin2hex(random_bytes(6));
$db->query("CREATE DATABASE `$name`");
$db->select_db($name);
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
function applySchema($db) {
    $db->multi_query(file_get_contents(__DIR__ . '/../database-update-api-schema.sql'));
    do { if ($result = $db->store_result()) { $result->free(); } }
    while ($db->more_results() && $db->next_result());
}
try {
    $db->query('CREATE TABLE third_party_apis (id INT PRIMARY KEY, name VARCHAR(128), api_token TEXT)');
    $db->query("INSERT INTO third_party_apis VALUES (7, 'Existing API', 'fixture-token')");
    foreach (['movies', 'links', 'popup_ad_units'] as $table) {
        $db->query("CREATE TABLE `$table` (id INT PRIMARY KEY, value TEXT)");
        $db->query("INSERT INTO `$table` VALUES (1, 'unchanged fixture')");
    }
    applySchema($db);
    applySchema($db);
    $row = $db->query('SELECT id, name, api_token FROM third_party_apis')->fetch_assoc();
    check($row === ['id'=>'7', 'name'=>'Existing API', 'api_token'=>'fixture-token'], 'Existing API row changed');
    $column = $db->query("SHOW COLUMNS FROM third_party_apis LIKE 'api_token'")->fetch_assoc();
    check($column['Type'] === 'text', 'Existing column type changed');
    foreach (['provider', 'api_base_url', 'embed_domains', 'r2_account_id', 'status', 'updated_at'] as $field) {
        check($db->query("SHOW COLUMNS FROM third_party_apis LIKE '$field'")->num_rows === 1, 'Missing field: ' . $field);
    }
    foreach (['movies', 'links', 'popup_ad_units'] as $table) {
        check($db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC) === [['id'=>'1', 'value'=>'unchanged fixture']], 'Content changed: ' . $table);
    }
    $db->query('DROP TABLE third_party_apis');
    applySchema($db);
    check((int) $db->query('SELECT COUNT(*) FROM third_party_apis')->fetch_row()[0] === 0, 'New table should be empty');
    echo "PASS: schema creation, missing columns, repeated application and existing API/content preservation.\n";
} finally {
    $db->query("DROP DATABASE `$name`");
    $db->close();
}
