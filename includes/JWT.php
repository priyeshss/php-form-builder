<?php
// PHP 7.x compatible

require_once __DIR__ . '/../config/env.php';

class JWT
{
    private static $instance = null; // PHP 7: no typed static properties

    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }

    public static function generate($payload, $isRefresh = false)
    {
        $secret = env('JWT_SECRET', 'changeme');
        $expiry = $isRefresh
            ? (int) env('JWT_REFRESH_EXPIRY', 604800)
            : (int) env('JWT_EXPIRY', 3600);

        $header          = self::base64UrlEncode(json_encode(array('alg' => 'HS256', 'typ' => 'JWT')));
        $payload['iat']  = time();
        $payload['exp']  = time() + $expiry;
        $payload['type'] = $isRefresh ? 'refresh' : 'access';

        $encodedPayload = self::base64UrlEncode(json_encode($payload));
        $signature      = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$encodedPayload", $secret, true)
        );

        return "$header.$encodedPayload.$signature";
    }

    public static function verify($token, $isRefresh = false)
    {
        $secret = env('JWT_SECRET', 'changeme');
        $parts  = explode('.', $token);
        if (count($parts) !== 3) return null;

        list($header, $payload, $signature) = $parts;

        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $secret, true)
        );
        if (!hash_equals($expectedSig, $signature)) return null;

        $data = json_decode(self::base64UrlDecode($payload), true);
        if (!$data) return null;
        if ((isset($data['exp']) ? $data['exp'] : 0) < time()) return null;

        $expectedType = $isRefresh ? 'refresh' : 'access';
        if ((isset($data['type']) ? $data['type'] : '') !== $expectedType) return null;

        return $data;
    }

    public static function fromHeader()
    {
        // 1. Standard header
        $auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

        // 2. Apache RewriteRule E=HTTP_AUTHORIZATION
        if (!$auth) $auth = isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : '';

        // 3. getallheaders() fallback
        if (!$auth && function_exists('getallheaders')) {
            $headers = getallheaders();
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

        // 4. Query param fallback (CSV export)
        if (!empty($_GET['token'])) {
            return trim($_GET['token']);
        }

        return null;
    }
}
