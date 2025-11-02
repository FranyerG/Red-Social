<?php
require_once '../config/database.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        
        // Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($username, $email, $password, $first_name, $last_name) {
        // Validaciones adicionales
        if(strlen($username) < 3) {
            return false;
        }
        
        if(strlen($password) < 6) {
            return false;
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Verificar si el usuario existe
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return false;
        }

        // Crear usuario
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password, first_name, last_name) 
                  VALUES (:username, :email, :password, :first_name, :last_name)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":first_name", $first_name);
        $stmt->bindParam(":last_name", $last_name);

        if($stmt->execute()) {
            // Crear configuración de privacidad por defecto
            $user_id = $this->conn->lastInsertId();
            $query = "INSERT INTO privacy_settings (user_id) VALUES (:user_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            return true;
        }
        return false;
    }

    public function login($username, $password) {
    $query = "SELECT id, username, password, profile_picture FROM users WHERE username = :username OR email = :email";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $username); // Mismo valor para ambos
    $stmt->execute();

    if($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if(password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_picture'] = $user['profile_picture'];
            return true;
        }
    }
    return false;
}

    public function logout() {
        // Destruir todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
        
        // Redirigir al login
        header('Location: login.php');
        exit;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser() {
        if($this->isLoggedIn()) {
            $query = "SELECT id, username, email, first_name, last_name, profile_picture, bio FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->execute();
            
            if($stmt->rowCount() == 1) {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        return null;
    }

    public function updateProfile($user_id, $first_name, $last_name, $bio, $profile_picture = null, $cover_picture = null) {
        if($profile_picture) {
            $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, bio = :bio, profile_picture = :profile_picture, cover_picture = :cover_picture WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":profile_picture", $profile_picture);
            $stmt->bindParam(":cover_picture", $cover_picture);
        } else {
            $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, bio = :bio WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->bindParam(":first_name", $first_name);
        $stmt->bindParam(":last_name", $last_name);
        $stmt->bindParam(":bio", $bio);
        $stmt->bindParam(":user_id", $user_id);
        
        if($stmt->execute()) {
            // Actualizar sesión si es el usuario actual
            if($user_id == $_SESSION['user_id']) {
                $_SESSION['profile_picture'] = $profile_picture ?: $_SESSION['profile_picture'];
            }
            return true;
        }
        return false;
    }

    public function changePassword($user_id, $current_password, $new_password) {
        // Verificar contraseña actual
        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($current_password, $user['password'])) {
                // Actualizar contraseña
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = :password WHERE id = :user_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":password", $hashed_password);
                $stmt->bindParam(":user_id", $user_id);
                
                return $stmt->execute();
            }
        }
        return false;
    }

    public function userExists($username) {
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    public function emailExists($email) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>