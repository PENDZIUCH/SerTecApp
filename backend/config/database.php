<?php
// SerTecApp - Database Configuration

require_once __DIR__ . '/env.php';

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        Env::load();
        
        require_once __DIR__ . '/railway.php';
        $config = RailwayConfig::getDatabaseConfig();
        
        $host = $config['host'];
        $dbName = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $port = $config['port'];
        $charset = Env::get('DB_CHARSET', 'utf8mb4');
        
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
            
            $this->conn = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}"
            ]);
            
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            
            http_response_code(503);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed',
                'error' => Env::getBool('APP_DEBUG') ? $e->getMessage() : 'Service temporarily unavailable'
            ]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function execute($sql, $params = []) {
        return $this->query($sql, $params)->rowCount();
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollBack();
    }
}
