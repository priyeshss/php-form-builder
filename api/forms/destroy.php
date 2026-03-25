<?php
ini_set('display_errors', '0');
error_reporting(0);

// DELETE /api/forms/{id}

$payload = requireAuth();
$id      = (int) ($_GET['id'] ?? 0);
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT id FROM forms WHERE id = ? AND created_by = ?');
$stmt->execute([$id, $payload['sub']]);
if (!$stmt->fetch()) Response::error('Form not found.', 404);

$db->prepare('DELETE FROM forms WHERE id = ?')->execute([$id]);

Response::success(null, 'Form deleted.');
