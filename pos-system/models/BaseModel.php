<?php
require_once __DIR__ . '/../config/database.php';

class BaseModel {
    protected $conn;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function findById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
            $stmt = $this->query($sql, ['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to find record: " . $e->getMessage());
        }
    }

    public function findOne($conditions) {
        try {
            $where = $this->buildWhereClause($conditions);
            $sql = "SELECT * FROM {$this->table} {$where['where']} LIMIT 1";
            $stmt = $this->query($sql, $where['params']);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to find record: " . $e->getMessage());
        }
    }

    public function findAll($conditions = [], $orderBy = '', $limit = null, $offset = null) {
        try {
            $where = $this->buildWhereClause($conditions);
            $sql = "SELECT * FROM {$this->table} {$where['where']}";
            
            if ($orderBy) {
                $sql .= " ORDER BY $orderBy";
            }
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                $where['params']['limit'] = $limit;
                
                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                    $where['params']['offset'] = $offset;
                }
            }

            $stmt = $this->query($sql, $where['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Failed to find records: " . $e->getMessage());
        }
    }

    public function create($data) {
        try {
            $this->conn->beginTransaction();

            $columns = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
            $stmt = $this->query($sql, $data);
            
            $id = $this->conn->lastInsertId();
            
            $this->conn->commit();
            return $id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to create record: " . $e->getMessage());
        }
    }

    public function update($id, $data) {
        try {
            $this->conn->beginTransaction();

            $set = [];
            foreach ($data as $key => $value) {
                $set[] = "$key = :$key";
            }
            $set = implode(', ', $set);
            
            $sql = "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = :id";
            $data['id'] = $id;
            
            $stmt = $this->query($sql, $data);
            
            $this->conn->commit();
            return $stmt->rowCount();
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to update record: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $this->conn->beginTransaction();

            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->query($sql, ['id' => $id]);
            
            $this->conn->commit();
            return $stmt->rowCount();
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to delete record: " . $e->getMessage());
        }
    }

    public function count($conditions = []) {
        try {
            $where = $this->buildWhereClause($conditions);
            $sql = "SELECT COUNT(*) as count FROM {$this->table} {$where['where']}";
            $stmt = $this->query($sql, $where['params']);
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {
            throw new Exception("Failed to count records: " . $e->getMessage());
        }
    }

    public function exists($conditions) {
        try {
            $where = $this->buildWhereClause($conditions);
            $sql = "SELECT EXISTS(SELECT 1 FROM {$this->table} {$where['where']}) as exist";
            $stmt = $this->query($sql, $where['params']);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC)['exist'];
        } catch (Exception $e) {
            throw new Exception("Failed to check existence: " . $e->getMessage());
        }
    }

    protected function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: " . $e->getMessage());
        }
    }

    protected function buildWhereClause($conditions) {
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

    protected function buildSetClause($data) {
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

    protected function validateTableName($table) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new Exception("Invalid table name");
        }
        return $table;
    }

    protected function validateColumnNames($columns) {
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

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function rollBack() {
        return $this->conn->rollBack();
    }

    protected function now() {
        return date('Y-m-d H:i:s');
    }

    protected function validateData($data, $rules) {
        $errors = [];

        foreach ($rules as $field => $rule) {
            // Skip if field is not required and not present
            if (!isset($data[$field]) && !in_array('required', $rule)) {
                continue;
            }

            // Check required fields
            if (in_array('required', $rule) && empty($data[$field])) {
                $errors[$field] = "Field is required";
                continue;
            }

            // Skip remaining validations if field is empty
            if (empty($data[$field])) {
                continue;
            }

            // Validate field based on rules
            foreach ($rule as $validation) {
                switch ($validation) {
                    case 'email':
                        if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Invalid email format";
                        }
                        break;

                    case 'numeric':
                        if (!is_numeric($data[$field])) {
                            $errors[$field] = "Must be numeric";
                        }
                        break;

                    case 'integer':
                        if (!filter_var($data[$field], FILTER_VALIDATE_INT)) {
                            $errors[$field] = "Must be an integer";
                        }
                        break;

                    case 'positive':
                        if ($data[$field] <= 0) {
                            $errors[$field] = "Must be positive";
                        }
                        break;

                    default:
                        // Handle custom validation rules
                        if (is_array($validation) && isset($validation[0])) {
                            switch ($validation[0]) {
                                case 'min':
                                    if (strlen($data[$field]) < $validation[1]) {
                                        $errors[$field] = "Minimum length is {$validation[1]}";
                                    }
                                    break;

                                case 'max':
                                    if (strlen($data[$field]) > $validation[1]) {
                                        $errors[$field] = "Maximum length is {$validation[1]}";
                                    }
                                    break;

                                case 'between':
                                    if ($data[$field] < $validation[1] || $data[$field] > $validation[2]) {
                                        $errors[$field] = "Must be between {$validation[1]} and {$validation[2]}";
                                    }
                                    break;

                                case 'in':
                                    if (!in_array($data[$field], array_slice($validation, 1))) {
                                        $errors[$field] = "Invalid value";
                                    }
                                    break;
                            }
                        }
                        break;
                }

                if (isset($errors[$field])) {
                    break;
                }
            }
        }

        return $errors;
    }
}
?>
