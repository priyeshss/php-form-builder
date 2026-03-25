<?php
// PHP 7.x compatible

require_once __DIR__ . '/env.php';

class Database
{
    private static $instance = null; // PHP 7: no typed static properties

    private function __construct() {}

    public static function getInstance()
    {
        if (self::$instance === null) {
            $host   = env('DB_HOST',     'localhost');
            $port   = env('DB_PORT',     '3306');
            $dbname = env('DB_DATABASE', 'php_form_builder');
            $user   = env('DB_USERNAME', 'root');
            $pass   = env('DB_PASSWORD', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, array(
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ));
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ));
                exit;
            }
        }
        return self::$instance;
    }
}
