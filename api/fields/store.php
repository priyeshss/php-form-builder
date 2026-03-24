<?php
declare(strict_types=1);
// POST /api/forms/{form_id}/fields

$payload = requireAuth();
$form_id = (int) ($_GET['form_id'] ?? 0);
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$form_id, $payload['sub']]);
if (!$stmt->fetch()) Response::error('Form not found.', 404);

$req    = new Request();
$errors = $req->validate([
    'label'      => 'required|max:200',
    'field_type' => 'required',
]);
if ($errors) Response::error('Validation failed.', 422, $errors);

$allowed_types = ['text','email','number','textarea','dropdown','radio','checkbox','file'];
$field_type    = $req->get('field_type');
if (!in_array($field_type, $allowed_types, true)) {
    Response::error('Invalid field type.', 422);
}

$label       = sanitize($req->get('label'));
$placeholder = sanitize($req->get('placeholder', ''));
$is_required = (int)(bool)$req->get('is_required', 0);
$options     = $req->get('options');   // array or null
$field_name  = 'field_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $label)) . '_' . time();

// Get next sort order
$maxStmt = $db->prepare('SELECT COALESCE(MAX(sort_order),0)+1 AS next FROM fields WHERE form_id = ?');
$maxStmt->execute([$form_id]);
$sort_order = (int)$maxStmt->fetchColumn();

$optionsJson = null;
if (in_array($field_type, ['dropdown','radio','checkbox'], true) && is_array($options)) {
    $optionsJson = json_encode(array_values(array_filter(array_map('trim', $options))));
}

$stmt = $db->prepare('
    INSERT INTO fields (form_id, field_name, field_type, label, placeholder, options, is_required, sort_order)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
');
$stmt->execute([$form_id, $field_name, $field_type, $label, $placeholder, $optionsJson, $is_required, $sort_order]);
$id = (int)$db->lastInsertId();

$row = $db->prepare('SELECT * FROM fields WHERE id = ?');
$row->execute([$id]);
$field = $row->fetch();
if ($field['options']) $field['options'] = json_decode($field['options'], true);

Response::success($field, 'Field created.', 201);
