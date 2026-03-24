<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';

class JWT
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }

    public static function generate(array $payload, bool $isRefresh = false): string
    {
        $secret  = env('JWT_SECRET', 'changeme');
        $expiry  = $isRefresh
            ? (int) env('JWT_REFRESH_EXPIRY', 604800)
            : (int) env('JWT_EXPIRY', 3600);

        $header         = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;
        $payload['type'] = $isRefresh ? 'refresh' : 'access';

        $encodedPayload = self::base64UrlEncode(json_encode($payload));
        $signature      = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$encodedPayload", $secret, true)
        );

        return "$header.$encodedPayload.$signature";
    }

    public static function verify(string $token, bool $isRefresh = false): ?array
    {
        $secret = env('JWT_SECRET', 'changeme');
        $parts  = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$header, $payload, $signature] = $parts;

        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $secret, true)
        );
        if (!hash_equals($expectedSig, $signature)) return null;

        $data = json_decode(self::base64UrlDecode($payload), true);
        if (!$data) return null;
        if (($data['exp'] ?? 0) < time()) return null;

        $expectedType = $isRefresh ? 'refresh' : 'access';
        if (($data['type'] ?? '') !== $expectedType) return null;

        return $data;
    }

    /**
     * Extract Bearer token from the request.
     * Apache often strips Authorization header — check every location it might appear.
     */
    public static function fromHeader(): ?string
    {
        // 1. Standard header (works when Apache passes it through)
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // 2. Apache sets this when you use: RewriteRule .* - [E=HTTP_AUTHORIZATION:...]
        if (!$auth) $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        // 3. Some CGI/FastCGI setups use this
        if (!$auth) $auth = $_SERVER['HTTP_X_AUTHORIZATION'] ?? '';

        // 4. getallheaders() — works on Apache mod_php
        if (!$auth && function_exists('getallheaders')) {
            $headers = getallheaders();
            // Header names are case-insensitive
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    $auth = $value;
                    break;
                }
            }
        }

        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return trim($m[1]);
        }

        // 5. Last resort: token passed as query param (used for CSV export browser tab)
        if (!empty($_GET['token'])) {
            return trim($_GET['token']);
        }

        return null;
    }
}
