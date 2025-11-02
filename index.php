<?php
require_once 'includes/functions.php';

// Si el usuario ya está logueado, redirigir al dashboard
if(isLoggedIn()) {
    redirect('pages/dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiRedSocial - Conecta con tus amigos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: white;
        }
        
        .hero-content {
            padding: 4rem 0;
        }
        
        .features-section {
            padding: 4rem 0;
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="display-4 fw-bold mb-4">Bienvenido a MiRedSocial</h1>
                        <p class="lead mb-4">Conecta con amigos, comparte momentos y descubre nuevas experiencias en nuestra plataforma social.</p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="pages/register.php" class="btn btn-light btn-lg px-4">
                                <i class="fas fa-user-plus me-2"></i>Crear Cuenta
                            </a>
                            <a href="pages/login.php" class="btn btn-outline-light btn-lg px-4">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="auth-card p-4">
                        <!-- Login Form -->
                        <div class="text-center mb-4">
                            <h3>Iniciar Sesión</h3>
                            <p class="text-muted">Accede a tu cuenta</p>
                        </div>
                        
                        <?php
                        // Mostrar mensajes de éxito/error
                        if(isset($_GET['registered'])) {
                            echo '<div class="alert alert-success">¡Cuenta creada exitosamente! Ya puedes iniciar sesión.</div>';
                        }
                        if(isset($_GET['error'])) {
                            echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($_GET['error']) . '</div>';
                        }
                        if(isset($_GET['logout'])) {
                            echo '<div class="alert alert-info">Sesión cerrada correctamente.</div>';
                        }
                        ?>
                        
                        <form action="pages/login.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario o Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Recordarme</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="pages/register.php" class="text-decoration-none">¿No tienes cuenta? Regístrate aquí</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Características Principales</h2>
                <p class="lead text-muted">Descubre todo lo que puedes hacer en MiRedSocial</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Conecta con Amigos</h4>
                        <p class="text-muted">Encuentra y conecta con amigos, familiares y personas con intereses similares.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <h4>Comparte Momentos</h4>
                        <p class="text-muted">Comparte fotos, videos, pensamientos y experiencias con tu red social.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4>Chat en Tiempo Real</h4>
                        <p class="text-muted">Conversa con tus amigos mediante nuestro sistema de mensajería instantánea.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h4>Eventos y Grupos</h4>
                        <p class="text-muted">Crea y únete a grupos de interés y organiza eventos sociales.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h4>Notificaciones</h4>
                        <p class="text-muted">Mantente informado sobre las actividades de tus amigos y grupos.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>Totalmente Responsive</h4>
                        <p class="text-muted">Disfruta de la experiencia en cualquier dispositivo, móvil o desktop.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>MiRedSocial</h5>
                    <p class="mb-0">Conectando personas alrededor del mundo.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; 2024 MiRedSocial. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>