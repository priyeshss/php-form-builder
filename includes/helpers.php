<?php
// PHP 7.x compatible — no str_starts_with / str_contains / str_ends_with

class Response
{
    public static function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success($data = null, $message = 'Success', $code = 200)
    {
        self::json(array(
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ), $code);
    }

    public static function error($message, $code = 400, $errors = null)
    {
        $body = array('success' => false, 'message' => $message);
        if ($errors !== null) $body['errors'] = $errors;
        self::json($body, $code);
    }
}

class Request
{
    private $body;
    private $query;

    public function __construct()
    {
        $raw = file_get_contents('php://input');
        $ct  = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        // str_contains polyfill
        if (strpos($ct, 'application/json') !== false) {
            $decoded    = json_decode($raw ? $raw : '{}', true);
            $this->body = is_array($decoded) ? $decoded : array();
        } else {
            $this->body = $_POST;
        }
        $this->query = $_GET;
    }

    public function get($key, $default = null)
    {
        if (isset($this->body[$key]))  return $this->body[$key];
        if (isset($this->query[$key])) return $this->query[$key];
        return $default;
    }

    public function all()
    {
        return array_merge($this->query, $this->body);
    }

    public function only($keys)
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    public function method()
    {
        return strtoupper(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
    }

    public function validate($rules)
    {
        $errors = array();
        foreach ($rules as $field => $rule) {
            $value    = $this->get($field);
            $ruleList = explode('|', $rule);
            foreach ($ruleList as $r) {
                if ($r === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = "$field is required.";
                }
                // str_starts_with polyfill
                if (strncmp($r, 'min:', 4) === 0) {
                    $min = (int) substr($r, 4);
                    if (strlen((string)($value !== null ? $value : '')) < $min)
                        $errors[$field][] = "$field must be at least $min characters.";
                }
                if (strncmp($r, 'max:', 4) === 0) {
                    $max = (int) substr($r, 4);
                    if (strlen((string)($value !== null ? $value : '')) > $max)
                        $errors[$field][] = "$field must not exceed $max characters.";
                }
                if ($r === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "$field must be a valid email.";
                }
            }
        }
        return $errors;
    }
}

function sanitize($value)
{
    if (is_string($value)) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    return $value;
}

function requireAuth()
{
    $token = JWT::fromHeader();
    if (!$token && isset($_GET['token'])) $token = trim($_GET['token']);
    if (!$token) Response::error('Unauthorized: missing token.', 401);

    $payload = JWT::verify($token);
    if (!$payload) Response::error('Unauthorized: invalid or expired token.', 401);

    return $payload;
}

function generateUUID()
{
    $data    = function_exists('random_bytes') ? random_bytes(16) : openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
