<?php
declare(strict_types=1);
// POST /api/auth/logout

$payload = requireAuth();
$db      = Database::getInstance();

// Delete all refresh tokens for this user
$stmt = $db->prepare('DELETE FROM refresh_tokens WHERE user_id = ?');
$stmt->execute([$payload['sub']]);

Response::success(null, 'Logged out successfully.');
