<?php
// ============================================================
// Database Connection — MySQL via PDO
// Bangladesh Railway Management System
// ============================================================
// OCI8 (Oracle) requires special Oracle Instant Client install.
// This version uses MySQL which ships with XAMPP by default.
// ============================================================

define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'railway_db');  // Create this DB in phpMyAdmin
define('DB_USER',    'root');        // Default XAMPP MySQL user
define('DB_PASS',    '');            // Default XAMPP MySQL password (empty)
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT
             . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log("DB connection failed: " . $e->getMessage());
            die(json_encode([
                'error' => 'Database connection failed. Check includes/db.php settings. '
                         . 'Make sure MySQL is running and database "railway_db" exists.'
            ]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    // Fetch all rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Fetch single row
    public function fetchOne($sql, $params = []) {
        $rows = $this->fetchAll($sql, $params);
        return $rows[0] ?? null;
    }

    // Execute INSERT / UPDATE / DELETE
    public function execute($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // Count rows in a table
    public function count($table, $where = '') {
        $sql = "SELECT COUNT(*) AS cnt FROM `$table`" . ($where ? " WHERE $where" : '');
        $row = $this->fetchOne($sql);
        return (int)($row['cnt'] ?? 0);
    }

    // Last inserted ID
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}

function db() {
    return Database::getInstance();
}
