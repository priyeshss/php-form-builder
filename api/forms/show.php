<?php
ini_set('display_errors', '0');
error_reporting(0);

// GET /api/forms/{id}

$payload = requireAuth();
$id      = (int) ($_GET['id'] ?? 0);
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT * FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$id, $payload['sub']]);
$form = $stmt->fetch();

if (!$form) Response::error('Form not found.', 404);

$fStmt = $db->prepare('SELECT * FROM fields WHERE form_id = ? ORDER BY sort_order ASC, id ASC');
$fStmt->execute([$id]);
$fields = $fStmt->fetchAll();

foreach ($fields as &$f) {
    if ($f['options']) $f['options'] = json_decode($f['options'], true);
}

$form['fields'] = $fields;

Response::success($form);
