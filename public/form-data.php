<?php
ini_set('display_errors', '0');
error_reporting(0);

// GET /public/form-data.php?id={form_id}
// Public endpoint — returns form + fields for rendering. No auth required.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if (!$id) Response::error('Form ID required.', 400);

$db   = Database::getInstance();
$stmt = $db->prepare('SELECT id, uuid, name, description FROM forms WHERE id = ? AND is_active = 1');
$stmt->execute([$id]);
$form = $stmt->fetch();

if (!$form) Response::error('Form not found or inactive.', 404);

$fStmt = $db->prepare('
    SELECT id, field_name, field_type, label, placeholder, options, is_required, sort_order
    FROM   fields
    WHERE  form_id = ?
    ORDER  BY sort_order ASC, id ASC
');
$fStmt->execute([$id]);
$fields = $fStmt->fetchAll();

foreach ($fields as &$f) {
    $f['is_required'] = (bool) $f['is_required'];
    if ($f['options']) $f['options'] = json_decode($f['options'], true);
}

$form['fields'] = $fields;

Response::success($form);
