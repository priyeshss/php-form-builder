<?php
declare(strict_types=1);
// GET /api/forms/{form_id}/fields

$payload = requireAuth();
$form_id = (int) ($_GET['form_id'] ?? 0);
$db      = Database::getInstance();

// Verify form ownership
$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$form_id, $payload['sub']]);
if (!$stmt->fetch()) Response::error('Form not found.', 404);

$stmt = $db->prepare('SELECT * FROM fields WHERE form_id = ? ORDER BY sort_order ASC, id ASC');
$stmt->execute([$form_id]);
$fields = $stmt->fetchAll();

foreach ($fields as &$f) {
    if ($f['options']) $f['options'] = json_decode($f['options'], true);
}

Response::success($fields);
