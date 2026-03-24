<?php
declare(strict_types=1);
// GET /api/auth/me

$payload = requireAuth();
$db      = Database::getInstance();

$stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
$stmt->execute([$payload['sub']]);
$user = $stmt->fetch();

if (!$user) Response::error('User not found.', 404);

Response::success($user);
