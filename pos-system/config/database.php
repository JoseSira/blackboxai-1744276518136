<?php
class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            // Database configuration
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbname = getenv('DB_NAME') ?: 'pos_system';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') ?: '';
            $charset = 'utf8mb4';

            // DSN (Data Source Name)
            $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

            // PDO options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            // Create PDO instance
            $this->conn = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new Exception("Connection failed: " . $e->getMessage());
        }
    }

    // Get database instance (Singleton pattern)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get database connection
    public function getConnection() {
        return $this->conn;
    }

    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }

    // Rollback transaction
    public function rollBack() {
        return $this->conn->rollBack();
    }

    // Execute a query
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

    // Get last inserted ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    // Prevent cloning of the instance (Singleton pattern)
    private function __clone() {}

    // Prevent unserializing of the instance (Singleton pattern)
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }

    // Helper method to build WHERE clause from conditions array
    public function buildWhereClause($conditions) {
        if (empty($conditions)) {
            return ['where' => '', 'params' => []];
        }

        $where = [];
        $params = [];

        foreach ($conditions as $key => $value) {
            if (is_numeric($key)) {
                // Raw condition (e.g., "column > :param")
                $where[] = $value;
            } else {
                // Simple equality condition
                $paramKey = str_replace('.', '_', $key);
                $where[] = "$key = :$paramKey";
                $params[$paramKey] = $value;
            }
        }

        return [
            'where' => 'WHERE ' . implode(' AND ', $where),
            'params' => $params
        ];
    }

    // Helper method to build SET clause for UPDATE queries
    public function buildSetClause($data) {
        $set = [];
        $params = [];

        foreach ($data as $key => $value) {
            $paramKey = str_replace('.', '_', $key);
            $set[] = "$key = :$paramKey";
            $params[$paramKey] = $value;
        }

        return [
            'set' => implode(', ', $set),
            'params' => $params
        ];
    }

    // Helper method to build INSERT query
    public function buildInsertQuery($table, $data) {
        $columns = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));

        return "INSERT INTO $table ($columns) VALUES ($values)";
    }

    // Helper method to build UPDATE query
    public function buildUpdateQuery($table, $data, $conditions) {
        $set = $this->buildSetClause($data);
        $where = $this->buildWhereClause($conditions);

        $sql = "UPDATE $table SET {$set['set']} {$where['where']}";
        $params = array_merge($set['params'], $where['params']);

        return ['sql' => $sql, 'params' => $params];
    }

    // Helper method to build SELECT query
    public function buildSelectQuery($table, $columns = '*', $conditions = [], $orderBy = '', $limit = '', $offset = '') {
        $where = $this->buildWhereClause($conditions);
        
        $sql = "SELECT $columns FROM $table {$where['where']}";
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
            if ($offset) {
                $sql .= " OFFSET $offset";
            }
        }

        return ['sql' => $sql, 'params' => $where['params']];
    }

    // Helper method to build DELETE query
    public function buildDeleteQuery($table, $conditions) {
        $where = $this->buildWhereClause($conditions);
        return ['sql' => "DELETE FROM $table {$where['where']}", 'params' => $where['params']];
    }

    // Helper method to check if a record exists
    public function recordExists($table, $conditions) {
        $where = $this->buildWhereClause($conditions);
        $sql = "SELECT EXISTS(SELECT 1 FROM $table {$where['where']}) as exist";
        $stmt = $this->query($sql, $where['params']);
        return (bool)$stmt->fetch()['exist'];
    }

    // Helper method to count records
    public function countRecords($table, $conditions = []) {
        $where = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as count FROM $table {$where['where']}";
        $stmt = $this->query($sql, $where['params']);
        return $stmt->fetch()['count'];
    }

    // Helper method to validate table name
    public function validateTableName($table) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception("Invalid table name");
        }
        return $table;
    }

    // Helper method to validate column names
    public function validateColumnNames($columns) {
        if (is_array($columns)) {
            foreach ($columns as $column) {
                if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $column)) {
                    throw new Exception("Invalid column name: $column");
                }
            }
            return implode(', ', $columns);
        }
        return $columns === '*' ? '*' : throw new Exception("Invalid columns parameter");
    }
}
?>
