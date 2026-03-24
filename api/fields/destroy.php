<?php
declare(strict_types=1);
// DELETE /api/forms/{form_id}/fields/{id}

$payload = requireAuth();
$form_id = (int)($_GET['form_id'] ?? 0);
$id      = (int)($_GET['id'] ?? 0);
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$form_id, $payload['sub']]);
if (!$stmt->fetch()) Response::error('Form not found.', 404);

$stmt = $db->prepare('SELECT id FROM fields WHERE id = ? AND form_id = ?');
$stmt->execute([$id, $form_id]);
if (!$stmt->fetch()) Response::error('Field not found.', 404);

$db->prepare('DELETE FROM fields WHERE id = ?')->execute([$id]);

Response::success(null, 'Field deleted.');
