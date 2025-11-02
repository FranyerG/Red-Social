<?php
class Database {
    private $host = "localhost";
    private $db_name = "red_social";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
        } catch(PDOException $exception) {
            // Log del error
            error_log("Error de conexión a la base de datos: " . $exception->getMessage());
            
            // Mostrar mensaje apropiado según el entorno
            if ($this->isDevelopmentEnvironment()) {
                die("Error de conexión a la base de datos: " . $exception->getMessage());
            } else {
                die("Error de conexión con la base de datos. Por favor, intente más tarde.");
            }
        }
        return $this->conn;
    }

    private function isDevelopmentEnvironment() {
        // Verificar si estamos en localhost o entorno de desarrollo
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return strpos($host, 'localhost') !== false || 
               strpos($host, '127.0.0.1') !== false ||
               strpos($host, '.local') !== false;
    }

    // Método para verificar si la conexión está activa
    public function isConnected() {
        try {
            if ($this->conn !== null) {
                $this->conn->query('SELECT 1');
                return true;
            }
        } catch (PDOException $e) {
            return false;
        }
        return false;
    }

    // Método para reconectar si es necesario
    public function reconnect() {
        $this->conn = null;
        return $this->getConnection();
    }

    // Método helper para ejecutar consultas preparadas de forma segura
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error en consulta: " . $e->getMessage() . " - SQL: " . $sql);
            throw $e;
        }
    }

    // Método para obtener un solo resultado
    public function fetchOne($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetch();
    }

    // Método para obtener múltiples resultados
    public function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll();
    }

    // Método para insertar y obtener el último ID
    public function insert($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $this->conn->lastInsertId();
    }

    // Método para actualizar
    public function update($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }

    // Método para eliminar
    public function delete($sql, $params = []) {
        return $this->update($sql, $params);
    }

    // Método para verificar si una tabla existe
    public function tableExists($tableName) {
        try {
            $sql = "SHOW TABLES LIKE ?";
            $stmt = $this->executeQuery($sql, [$tableName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Método para obtener información de la base de datos
    public function getDatabaseInfo() {
        try {
            $info = [];
            
            // Obtener versión de MySQL
            $stmt = $this->executeQuery("SELECT VERSION() as version");
            $info['version'] = $stmt->fetch()['version'];
            
            // Obtener estadísticas de tablas
            $tables = ['users', 'posts', 'comments', 'likes', 'friendships', 'notifications', 'groups', 'events', 'stories'];
            $info['tables'] = [];
            
            foreach ($tables as $table) {
                if ($this->tableExists($table)) {
                    $stmt = $this->executeQuery("SELECT COUNT(*) as count FROM $table");
                    $info['tables'][$table] = $stmt->fetch()['count'];
                }
            }
            
            return $info;
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Método para hacer backup básico (solo estructura en este ejemplo)
    public function backupDatabase($backupPath = '../backups/') {
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $filename = $backupPath . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $tables = $this->fetchAll("SHOW TABLES");
        
        $backupContent = "-- Backup de la base de datos: " . $this->db_name . "\n";
        $backupContent .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $tableName = $table['Tables_in_' . $this->db_name];
            
            // Obtener estructura de la tabla
            $createTable = $this->fetchOne("SHOW CREATE TABLE `$tableName`");
            $backupContent .= "-- Estructura para tabla: $tableName\n";
            $backupContent .= $createTable['Create Table'] . ";\n\n";
            
            // Obtener datos de la tabla
            $rows = $this->fetchAll("SELECT * FROM `$tableName`");
            if (count($rows) > 0) {
                $backupContent .= "-- Volcado de datos para tabla: $tableName\n";
                
                foreach ($rows as $row) {
                    $columns = implode('`, `', array_keys($row));
                    $values = implode("', '", array_map(function($value) {
                        return str_replace("'", "\\'", $value);
                    }, $row));
                    
                    $backupContent .= "INSERT INTO `$tableName` (`$columns`) VALUES ('$values');\n";
                }
                $backupContent .= "\n";
            }
        }
        
        if (file_put_contents($filename, $backupContent)) {
            return $filename;
        }
        
        return false;
    }

    // Método para optimizar tablas
    public function optimizeTables() {
        try {
            $tables = $this->fetchAll("SHOW TABLES");
            $optimized = [];
            
            foreach ($tables as $table) {
                $tableName = $table['Tables_in_' . $this->db_name];
                $this->executeQuery("OPTIMIZE TABLE `$tableName`");
                $optimized[] = $tableName;
            }
            
            return $optimized;
        } catch (PDOException $e) {
            error_log("Error optimizando tablas: " . $e->getMessage());
            return false;
        }
    }

    // Destructor para cerrar la conexión
    public function __destruct() {
        $this->conn = null;
    }
}

// Función global helper para obtener instancia de la base de datos
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->getConnection();
}

// Función para verificar requisitos del sistema
function checkSystemRequirements() {
    $requirements = [
        'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
        'json' => extension_loaded('json'),
        'session' => extension_loaded('session'),
    ];
    
    return $requirements;
}
?>