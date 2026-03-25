<?php
ini_set('display_errors', '0');
error_reporting(0);

// POST /api/auth/refresh

$req   = new Request();
$token = $req->get('refresh_token');
if (!$token) Response::error('Refresh token required.', 400);

$payload = JWT::verify($token, true);
if (!$payload) Response::error('Invalid or expired refresh token.', 401);

$db   = Database::getInstance();
$stmt = $db->prepare('SELECT id FROM refresh_tokens WHERE token = ? AND expires_at > NOW() LIMIT 1');
$stmt->execute([$token]);
if (!$stmt->fetch()) Response::error('Refresh token revoked or expired.', 401);

// Rotate: delete old, issue new
$db->prepare('DELETE FROM refresh_tokens WHERE token = ?')->execute([$token]);

$newPayload      = ['sub' => $payload['sub'], 'email' => $payload['email'], 'role' => $payload['role']];
$accessToken     = JWT::generate($newPayload);
$newRefreshToken = JWT::generate($newPayload, true);

$exp  = date('Y-m-d H:i:s', time() + (int) env('JWT_REFRESH_EXPIRY', 604800));
$stmt = $db->prepare('INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
$stmt->execute([$payload['sub'], $newRefreshToken, $exp]);

Response::success([
    'access_token'  => $accessToken,
    'refresh_token' => $newRefreshToken,
    'token_type'    => 'Bearer',
    'expires_in'    => (int) env('JWT_EXPIRY', 3600),
], 'Token refreshed.');
