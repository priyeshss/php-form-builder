<?php
declare(strict_types=1);

class Response
{
    public static function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Success', int $code = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    public static function error(string $message, int $code = 400, mixed $errors = null): void
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) $body['errors'] = $errors;
        self::json($body, $code);
    }
}

class Request
{
    private array $body;
    private array $query;

    public function __construct()
    {
        $raw = file_get_contents('php://input');
        $ct  = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($ct, 'application/json')) {
            $this->body = (array) (json_decode($raw ?: '{}', true) ?? []);
        } else {
            $this->body = $_POST;
        }
        $this->query = $_GET;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array { return array_merge($this->query, $this->body); }

    public function only(array $keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    public function method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }

    public function validate(array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $this->get($field);
            $ruleList = explode('|', $rule);
            foreach ($ruleList as $r) {
                if ($r === 'required' && ($value === null || $value === '')) {
                    $errors[$field][] = "$field is required.";
                }
                if (str_starts_with($r, 'min:')) {
                    $min = (int) substr($r, 4);
                    if (strlen((string)($value ?? '')) < $min)
                        $errors[$field][] = "$field must be at least $min characters.";
                }
                if (str_starts_with($r, 'max:')) {
                    $max = (int) substr($r, 4);
                    if (strlen((string)($value ?? '')) > $max)
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

function sanitize(mixed $value): mixed
{
    if (is_string($value)) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    return $value;
}

function requireAuth(): array
{
    // Accept token from Authorization header OR ?token= query param (for browser downloads)
    $token = JWT::fromHeader() ?? ($_GET['token'] ?? null);
    if (!$token) Response::error('Unauthorized: missing token.', 401);

    $payload = JWT::verify($token);
    if (!$payload) Response::error('Unauthorized: invalid or expired token.', 401);

    return $payload;
}

function generateUUID(): string
{
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
