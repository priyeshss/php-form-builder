<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host    = env('DB_HOST', 'localhost');
            $port    = env('DB_PORT', '3306');
            $dbname  = env('DB_DATABASE', 'php_form_builder');
            $user    = env('DB_USERNAME', 'root');
            $pass    = env('DB_PASSWORD', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed.']);
                exit;
            }
        }
        return self::$instance;
    }
}
