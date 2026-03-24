<?php
declare(strict_types=1);
// PUT /api/forms/{form_id}/fields/reorder
// Body: { "order": [3, 1, 5, 2] }  — array of field IDs in new order

$payload = requireAuth();
$form_id = (int)($_GET['form_id'] ?? 0);
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$form_id, $payload['sub']]);
if (!$stmt->fetch()) Response::error('Form not found.', 404);

$req   = new Request();
$order = $req->get('order');

if (!is_array($order) || empty($order)) {
    Response::error('order must be a non-empty array of field IDs.', 422);
}

$stmt = $db->prepare('UPDATE fields SET sort_order = ? WHERE id = ? AND form_id = ?');
foreach ($order as $index => $fieldId) {
    $stmt->execute([(int)$index, (int)$fieldId, $form_id]);
}

Response::success(null, 'Fields reordered.');
