<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/helpers.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Path resolution ────────────────────────────────────────────────────────
// Handles three calling conventions Laragon/Apache may produce:
//   1. PATH_INFO set by Apache AcceptPathInfo / rewrite  → /auth/login
//   2. index.php/auth/login  (no rewrite, script in URI) → strip script filename
//   3. index.php?_path=/auth/login  (query-string fallback)
if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} elseif (!empty($_SERVER['ORIG_PATH_INFO'])) {
    $path = $_SERVER['ORIG_PATH_INFO'];
} elseif (!empty($_GET['_path'])) {
    $path = '/' . trim($_GET['_path'], '/');
} else {
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';   // /php-form-builder/api/index.php
    $scriptDir  = dirname($scriptName);             // /php-form-builder/api

    if ($scriptName !== '' && str_starts_with($requestUri, $scriptName)) {
        // URL contains script filename: /api/index.php/auth/login
        $path = substr($requestUri, strlen($scriptName));
    } else {
        // Rewrite stripped script: /api/auth/login  →  strip /api prefix
        $path = substr($requestUri, strlen($scriptDir));
    }
}

$path   = '/' . trim((string)$path, '/');
$method = strtoupper($_SERVER['REQUEST_METHOD']);

// ── Routes ─────────────────────────────────────────────────────────────────
// Auth
if ($path === '/auth/login'    && $method === 'POST') { require __DIR__ . '/auth/login.php';   exit; }
if ($path === '/auth/logout'   && $method === 'POST') { require __DIR__ . '/auth/logout.php';  exit; }
if ($path === '/auth/refresh'  && $method === 'POST') { require __DIR__ . '/auth/refresh.php'; exit; }
if ($path === '/auth/me'       && $method === 'GET')  { require __DIR__ . '/auth/me.php';      exit; }

// Forms CRUD
if ($path === '/forms'       && $method === 'GET')    { require __DIR__ . '/forms/index.php';   exit; }
if ($path === '/forms'       && $method === 'POST')   { require __DIR__ . '/forms/store.php';   exit; }
if (preg_match('#^/forms/(\d+)$#', $path, $m) && $method === 'GET')               { $_GET['id'] = $m[1]; require __DIR__ . '/forms/show.php';    exit; }
if (preg_match('#^/forms/(\d+)$#', $path, $m) && in_array($method, ['PUT','PATCH'])) { $_GET['id'] = $m[1]; require __DIR__ . '/forms/update.php';  exit; }
if (preg_match('#^/forms/(\d+)$#', $path, $m) && $method === 'DELETE')            { $_GET['id'] = $m[1]; require __DIR__ . '/forms/destroy.php'; exit; }

// Fields CRUD (nested under form)
if (preg_match('#^/forms/(\d+)/fields$#', $path, $m) && $method === 'GET')  { $_GET['form_id'] = $m[1]; require __DIR__ . '/fields/index.php';   exit; }
if (preg_match('#^/forms/(\d+)/fields$#', $path, $m) && $method === 'POST') { $_GET['form_id'] = $m[1]; require __DIR__ . '/fields/store.php';   exit; }
if (preg_match('#^/forms/(\d+)/fields/reorder$#', $path, $m) && $method === 'PUT') { $_GET['form_id'] = $m[1]; require __DIR__ . '/fields/reorder.php'; exit; }
if (preg_match('#^/forms/(\d+)/fields/(\d+)$#', $path, $m) && in_array($method, ['PUT','PATCH'])) { $_GET['form_id'] = $m[1]; $_GET['id'] = $m[2]; require __DIR__ . '/fields/update.php';  exit; }
if (preg_match('#^/forms/(\d+)/fields/(\d+)$#', $path, $m) && $method === 'DELETE') { $_GET['form_id'] = $m[1]; $_GET['id'] = $m[2]; require __DIR__ . '/fields/destroy.php'; exit; }

// Submissions
if (preg_match('#^/forms/(\d+)/submissions/export$#', $path, $m) && $method === 'GET') { $_GET['form_id'] = $m[1]; require __DIR__ . '/submissions/export.php'; exit; }
if (preg_match('#^/forms/(\d+)/submissions$#', $path, $m) && $method === 'GET')  { $_GET['form_id'] = $m[1]; require __DIR__ . '/submissions/index.php';  exit; }
if (preg_match('#^/forms/(\d+)/submissions$#', $path, $m) && $method === 'POST') { $_GET['form_id'] = $m[1]; require __DIR__ . '/submissions/store.php';  exit; }

// Debug helper — remove in production
if ($path === '/debug') {
    echo json_encode([
        'path'           => $path,
        'REQUEST_URI'    => $_SERVER['REQUEST_URI']    ?? null,
        'SCRIPT_NAME'    => $_SERVER['SCRIPT_NAME']    ?? null,
        'PATH_INFO'      => $_SERVER['PATH_INFO']      ?? null,
        'ORIG_PATH_INFO' => $_SERVER['ORIG_PATH_INFO'] ?? null,
    ], JSON_PRETTY_PRINT);
    exit;
}

Response::error('Route not found. path=' . $path, 404);
