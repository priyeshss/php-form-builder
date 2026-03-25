<?php
// Temporary debug file - DELETE after use
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Check MySQL version
    $version = $db->query('SELECT VERSION() as v')->fetch();
    
    // Check if forms table exists
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    // Check fields table structure
    $cols = [];
    if (in_array('fields', $tables)) {
        $cols = $db->query("DESCRIBE fields")->fetchAll();
    }
    
    echo json_encode([
        'mysql_version' => $version['v'],
        'tables'        => $tables,
        'fields_cols'   => $cols,
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
