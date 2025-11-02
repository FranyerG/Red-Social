<?php

require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Si el usuario ya está logueado, redirigir al dashboard
if(isLoggedIn()) {
    redirect('dashboard.php');
}

$auth = new Auth();
$error = '';

// Procesar formulario de login
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if(empty($username) || empty($password)) {
        $error = "Por favor, completa todos los campos";
    } else {
        if($auth->login($username, $password)) {
            // Si marcó "Recordarme", establecer cookie
            if($remember) {
                setcookie('remember_user', $_SESSION['user_id'], time() + (30 * 24 * 60 * 60), '/'); // 30 días
            }
            
            // Redirigir a la página que intentaba acceder o al dashboard
            $redirect_url = $_SESSION['redirect_url'] ?? 'dashboard.php';
            unset($_SESSION['redirect_url']);
            redirect($redirect_url);
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    }
}

// Si viene de otra página, guardar la URL para redirigir después del login
if(isset($_SERVER['HTTP_REFERER']) && !str_contains($_SERVER['HTTP_REFERER'], 'login.php')) {
    $_SESSION['redirect_url'] = $_SERVER['HTTP_REFERER'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .login-body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
            color: white;
            margin: -1px;
        }
    </style>
</head>
<body class="login-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <h2 class="mb-0"><i class="fas fa-users me-2"></i>MiRedSocial</h2>
                        <p class="mb-0 mt-2">Conecta con tu comunidad</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Iniciar Sesión</h4>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php
                        // Mostrar mensajes de éxito/error
                        if(isset($_GET['registered'])) {
                            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    ¡Cuenta creada exitosamente! Ya puedes iniciar sesión.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
                        }
                        if(isset($_GET['logout'])) {
                            echo '<div class="alert alert-info alert-dismissible fade show" role="alert">
                                    Sesión cerrada correctamente.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
                        }
                        if(isset($_GET['session_expired'])) {
                            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    Tu sesión ha expirado. Por favor, inicia sesión nuevamente.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
                        }
                        ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario o Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control border-start-0" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                           placeholder="Ingresa tu usuario o email"
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control border-start-0" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Ingresa tu contraseña"
                                           required>
                                    <button type="button" 
                                            class="btn btn-outline-secondary border-start-0" 
                                            onclick="togglePassword()">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember" name="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="remember">Recordarme</label>
                                </div>
                                <a href="forgot-password.php" class="text-decoration-none small">¿Olvidaste tu contraseña?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                            
                            <div class="text-center">
                                <p class="mb-0">¿No tienes cuenta? 
                                    <a href="register.php" class="text-decoration-none fw-bold">Regístrate aquí</a>
                                </p>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="text-muted small mb-3">O inicia sesión con</p>
                            <div class="d-flex justify-content-center gap-3">
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="fab fa-google me-2"></i>Google
                                </button>
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="fab fa-facebook me-2"></i>Facebook
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-white mb-0">
                        &copy; 2024 MiRedSocial. 
                        <a href="../index.php" class="text-white text-decoration-none">Volver al inicio</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        // Auto-focus en el campo de usuario
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Prevenir reenvío del formulario
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>