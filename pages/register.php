<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Si el usuario ya está logueado, redirigir al dashboard
if(isLoggedIn()) {
    redirect('dashboard.php');
}

$auth = new Auth();
$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);

    // Validaciones
    if(empty($username) || empty($email) || empty($password) || empty($first_name)) {
        $error = "Todos los campos obligatorios deben ser completados";
    } elseif($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } elseif(strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del email no es válido";
    } elseif(strlen($username) < 3) {
        $error = "El usuario debe tener al menos 3 caracteres";
    } else {
        if($auth->register($username, $email, $password, $first_name, $last_name)) {
            $success = "¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.";
            
            // Limpiar el formulario
            $_POST = array();
        } else {
            $error = "El usuario o email ya existen";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .register-body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .register-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px 15px 0 0;
            padding: 2rem;
            text-align: center;
            color: white;
            margin: -1px;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-fair { background-color: #ffc107; width: 50%; }
        .strength-good { background-color: #28a745; width: 75%; }
        .strength-strong { background-color: #20c997; width: 100%; }
    </style>
</head>
<body class="register-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="register-card">
                    <div class="register-header">
                        <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Crear Cuenta</h2>
                        <p class="mb-0 mt-2">Únete a nuestra comunidad</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                <div class="mt-2">
                                    <a href="login.php" class="btn btn-success btn-sm">Iniciar Sesión</a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="registerForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-user text-muted"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control border-start-0" 
                                                   id="first_name" 
                                                   name="first_name" 
                                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                                   placeholder="Tu nombre"
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Apellido</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-user text-muted"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control border-start-0" 
                                                   id="last_name" 
                                                   name="last_name" 
                                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                                   placeholder="Tu apellido">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-at text-muted"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control border-start-0" 
                                           id="username" 
                                           name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                           placeholder="Elige un nombre de usuario"
                                           required
                                           minlength="3">
                                    <div class="invalid-feedback" id="username-feedback">
                                        El usuario debe tener al menos 3 caracteres
                                    </div>
                                </div>
                                <small class="form-text text-muted">Mínimo 3 caracteres. Letras, números y guiones bajos.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control border-start-0" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                           placeholder="tu@email.com"
                                           required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-lock text-muted"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control border-start-0" 
                                                   id="password" 
                                                   name="password" 
                                                   placeholder="Mínimo 6 caracteres"
                                                   required
                                                   minlength="6">
                                            <button type="button" 
                                                    class="btn btn-outline-secondary border-start-0" 
                                                    onclick="togglePassword('password')">
                                                <i class="fas fa-eye" id="password-icon"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength" id="password-strength"></div>
                                        <small class="form-text text-muted" id="password-hint"></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-lock text-muted"></i>
                                            </span>
                                            <input type="password" 
                                                   class="form-control border-start-0" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   placeholder="Repite tu contraseña"
                                                   required
                                                   minlength="6">
                                            <button type="button" 
                                                    class="btn btn-outline-secondary border-start-0" 
                                                    onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye" id="confirm-password-icon"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback" id="confirm-password-feedback">
                                            Las contraseñas no coinciden
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    Acepto los <a href="#" class="text-decoration-none">términos y condiciones</a> y la <a href="#" class="text-decoration-none">política de privacidad</a>
                                </label>
                                <div class="invalid-feedback">
                                    Debes aceptar los términos y condiciones
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Crear Cuenta
                            </button>
                            
                            <div class="text-center">
                                <p class="mb-0">¿Ya tienes cuenta? 
                                    <a href="login.php" class="text-decoration-none fw-bold">Inicia sesión aquí</a>
                                </p>
                            </div>
                        </form>
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
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const iconId = fieldId === 'password' ? 'password-icon' : 'confirm-password-icon';
            const passwordIcon = document.getElementById(iconId);
            
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
        
        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthBar = document.getElementById('password-strength');
            const hint = document.getElementById('password-hint');
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength';
            
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.classList.add('strength-weak');
                    hint.textContent = 'Contraseña débil';
                    hint.className = 'form-text text-danger';
                    break;
                case 2:
                    strengthBar.classList.add('strength-fair');
                    hint.textContent = 'Contraseña aceptable';
                    hint.className = 'form-text text-warning';
                    break;
                case 3:
                case 4:
                    strengthBar.classList.add('strength-good');
                    hint.textContent = 'Contraseña buena';
                    hint.className = 'form-text text-info';
                    break;
                case 5:
                    strengthBar.classList.add('strength-strong');
                    hint.textContent = 'Contraseña fuerte';
                    hint.className = 'form-text text-success';
                    break;
            }
        }
        
        function validateForm() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const confirmFeedback = document.getElementById('confirm-password-feedback');
            
            if (password !== confirmPassword) {
                document.getElementById('confirm_password').classList.add('is-invalid');
                return false;
            } else {
                document.getElementById('confirm_password').classList.remove('is-invalid');
            }
            
            return true;
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const form = document.getElementById('registerForm');
            
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                
                // Validar coincidencia en tiempo real
                if (confirmPasswordInput.value) {
                    validateForm();
                }
            });
            
            confirmPasswordInput.addEventListener('input', validateForm);
            
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
            
            // Auto-focus en el primer campo
            document.getElementById('first_name').focus();
        });
        
        // Prevenir reenvío del formulario
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>