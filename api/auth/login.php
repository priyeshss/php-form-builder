<?php
declare(strict_types=1);
// POST /api/auth/login

$req    = new Request();
$errors = $req->validate(['email' => 'required|email', 'password' => 'required|min:6']);
if ($errors) Response::error('Validation failed.', 422, $errors);

$email    = sanitize($req->get('email'));
$password = $req->get('password');   // raw – only used with password_verify

$db   = Database::getInstance();
$stmt = $db->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    Response::error('Invalid credentials.', 401);
}

$payload = ['sub' => $user['id'], 'email' => $user['email'], 'role' => $user['role']];

$accessToken  = JWT::generate($payload);
$refreshToken = JWT::generate($payload, isRefresh: true);

// Store refresh token
$exp  = date('Y-m-d H:i:s', time() + (int) env('JWT_REFRESH_EXPIRY', 604800));
$stmt = $db->prepare('INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
$stmt->execute([$user['id'], $refreshToken, $exp]);

Response::success([
    'access_token'  => $accessToken,
    'refresh_token' => $refreshToken,
    'token_type'    => 'Bearer',
    'expires_in'    => (int) env('JWT_EXPIRY', 3600),
    'user'          => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
], 'Login successful.');
