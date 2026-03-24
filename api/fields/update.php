<?php
declare(strict_types=1);
// PUT/PATCH /api/forms/{form_id}/fields/{id}

$payload = requireAuth();
$form_id = (int)($_GET['form_id'] ?? 0);
$id      = (int)($_GET['id'] ?? 0);
$db      = Database::getInstance();

// Verify ownership
$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$form_id, $payload['sub']]);
if (!$stmt->fetch()) Response::error('Form not found.', 404);

$stmt = $db->prepare('SELECT id FROM fields WHERE id = ? AND form_id = ?');
$stmt->execute([$id, $form_id]);
if (!$stmt->fetch()) Response::error('Field not found.', 404);

$req    = new Request();
$errors = $req->validate(['label' => 'required|max:200', 'field_type' => 'required']);
if ($errors) Response::error('Validation failed.', 422, $errors);

$allowed_types = ['text','email','number','textarea','dropdown','radio','checkbox','file'];
$field_type    = $req->get('field_type');
if (!in_array($field_type, $allowed_types, true)) Response::error('Invalid field type.', 422);

$label       = sanitize($req->get('label'));
$placeholder = sanitize($req->get('placeholder', ''));
$is_required = (int)(bool)$req->get('is_required', 0);
$options     = $req->get('options');

$optionsJson = null;
if (in_array($field_type, ['dropdown','radio','checkbox'], true) && is_array($options)) {
    $optionsJson = json_encode(array_values(array_filter(array_map('trim', $options))));
}

$db->prepare('
    UPDATE fields SET field_type=?, label=?, placeholder=?, options=?, is_required=? WHERE id=?
')->execute([$field_type, $label, $placeholder, $optionsJson, $is_required, $id]);

$row = $db->prepare('SELECT * FROM fields WHERE id = ?');
$row->execute([$id]);
$field = $row->fetch();
if ($field['options']) $field['options'] = json_decode($field['options'], true);

Response::success($field, 'Field updated.');
