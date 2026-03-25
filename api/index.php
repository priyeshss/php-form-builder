<?php
// No declare(strict_types=1) — keep compatible with PHP 7.x

// ── Global error → JSON (catches fatal errors on shared hosting) ──────────
set_exception_handler(function ($e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ]);
    exit;
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
});

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/helpers.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Path resolution (PHP 7 compatible) ───────────────────────────────────
if (!empty($_SERVER['PATH_INFO'])) {
    $path = $_SERVER['PATH_INFO'];
} elseif (!empty($_SERVER['ORIG_PATH_INFO'])) {
    $path = $_SERVER['ORIG_PATH_INFO'];
} elseif (!empty($_GET['_path'])) {
    $path = '/' . trim($_GET['_path'], '/');
} else {
    $requestUri = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $scriptDir  = dirname($scriptName);

    // str_starts_with polyfill inline
    if ($scriptName !== '' && strncmp($requestUri, $scriptName, strlen($scriptName)) === 0) {
        $path = substr($requestUri, strlen($scriptName));
    } else {
        $path = substr($requestUri, strlen($scriptDir));
    }
}

$path   = '/' . trim((string)$path, '/');
$method = strtoupper($_SERVER['REQUEST_METHOD']);

// ── Routes ────────────────────────────────────────────────────────────────
if ($path === '/auth/login'   && $method === 'POST') { require __DIR__ . '/auth/login.php';   exit; }
if ($path === '/auth/logout'  && $method === 'POST') { require __DIR__ . '/auth/logout.php';  exit; }
if ($path === '/auth/refresh' && $method === 'POST') { require __DIR__ . '/auth/refresh.php'; exit; }
if ($path === '/auth/me'      && $method === 'GET')  { require __DIR__ . '/auth/me.php';      exit; }

if ($path === '/forms'        && $method === 'GET')  { require __DIR__ . '/forms/index.php';  exit; }
if ($path === '/forms'        && $method === 'POST') { require __DIR__ . '/forms/store.php';  exit; }
if (preg_match('#^/forms/(\d+)$#', $path, $m) && $method === 'GET')                      { $_GET['id'] = $m[1]; require __DIR__ . '/forms/show.php';    exit; }
if (preg_match('#^/forms/(\d+)$#', $path, $m) && in_array($method, array('PUT','PATCH'))) { $_GET['id'] = $m[1]; require __DIR__ . '/forms/update.php';  exit; }
if (preg_match('#^/forms/(\d+)$#', $path, $m) && $method === 'DELETE')                   { $_GET['id'] = $m[1]; require __DIR__ . '/forms/destroy.php'; exit; }

if (preg_match('#^/forms/(\d+)/fields$#', $path, $m) && $method === 'GET')               { $_GET['form_id'] = $m[1]; require __DIR__ . '/fields/index.php';   exit; }
if (preg_match('#^/forms/(\d+)/fields$#', $path, $m) && $method === 'POST')              { $_GET['form_id'] = $m[1]; require __DIR__ . '/fields/store.php';   exit; }
if (preg_match('#^/forms/(\d+)/fields/reorder$#', $path, $m) && $method === 'PUT')       { $_GET['form_id'] = $m[1]; require __DIR__ . '/fields/reorder.php'; exit; }
if (preg_match('#^/forms/(\d+)/fields/(\d+)$#', $path, $m) && in_array($method, array('PUT','PATCH'))) { $_GET['form_id'] = $m[1]; $_GET['id'] = $m[2]; require __DIR__ . '/fields/update.php';  exit; }
if (preg_match('#^/forms/(\d+)/fields/(\d+)$#', $path, $m) && $method === 'DELETE')      { $_GET['form_id'] = $m[1]; $_GET['id'] = $m[2]; require __DIR__ . '/fields/destroy.php'; exit; }

if (preg_match('#^/forms/(\d+)/submissions/export$#', $path, $m) && $method === 'GET')   { $_GET['form_id'] = $m[1]; require __DIR__ . '/submissions/export.php'; exit; }
if (preg_match('#^/forms/(\d+)/submissions$#', $path, $m) && $method === 'GET')          { $_GET['form_id'] = $m[1]; require __DIR__ . '/submissions/index.php';  exit; }
if (preg_match('#^/forms/(\d+)/submissions$#', $path, $m) && $method === 'POST')         { $_GET['form_id'] = $m[1]; require __DIR__ . '/submissions/store.php';  exit; }

// Debug
if ($path === '/debug') {
    echo json_encode(array(
        'path'           => $path,
        'method'         => $method,
        'REQUEST_URI'    => isset($_SERVER['REQUEST_URI'])    ? $_SERVER['REQUEST_URI']    : null,
        'SCRIPT_NAME'    => isset($_SERVER['SCRIPT_NAME'])    ? $_SERVER['SCRIPT_NAME']    : null,
        'PATH_INFO'      => isset($_SERVER['PATH_INFO'])      ? $_SERVER['PATH_INFO']      : null,
        'ORIG_PATH_INFO' => isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : null,
        'php_version'    => PHP_VERSION,
        'auth_header'    => isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : 'NOT SET'),
    ), JSON_PRETTY_PRINT);
    exit;
}

Response::error('Route not found: ' . $path, 404);
