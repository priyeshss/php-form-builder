<?php
declare(strict_types=1);
// GET /api/forms/{form_id}/submissions

$payload = requireAuth();
$form_id = (int)($_GET['form_id'] ?? 0);
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$form_id, $payload['sub']]);
if (!$stmt->fetch()) Response::error('Form not found.', 404);

// Fetch all submissions with their values
$stmt = $db->prepare('
    SELECT s.id, s.submitted_at, s.ip_address
    FROM   submissions s
    WHERE  s.form_id = ?
    ORDER  BY s.submitted_at DESC
');
$stmt->execute([$form_id]);
$submissions = $stmt->fetchAll();

// Attach values to each submission
$valStmt = $db->prepare('
    SELECT sv.field_id, sv.field_label, sv.value
    FROM   submission_values sv
    WHERE  sv.submission_id = ?
');

foreach ($submissions as &$sub) {
    $valStmt->execute([$sub['id']]);
    $sub['values'] = $valStmt->fetchAll();
}

Response::success($submissions);
