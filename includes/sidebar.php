<?php
// Sidebar para el dashboard
?>
<div class="card mb-4">
    <div class="card-body text-center">
        <img src="../assets/uploads/<?php echo $_SESSION['profile_picture'] ?? 'default.jpg'; ?>" 
             class="rounded-circle mb-3" width="80" height="80" alt="Profile">
        <h5><?php echo $_SESSION['username']; ?></h5>
        <p class="text-muted small"><?php echo $_SESSION['bio'] ?? 'Bienvenido a MiRedSocial'; ?></p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h6 class="card-title">Menú Principal</h6>
        <ul class="list-unstyled mb-0">
            <li class="mb-2">
                <a href="dashboard.php" class="text-decoration-none d-flex align-items-center">
                    <i class="fas fa-home me-2"></i> Inicio
                </a>
            </li>
            <li class="mb-2">
                <a href="profile.php" class="text-decoration-none d-flex align-items-center">
                    <i class="fas fa-user me-2"></i> Mi Perfil
                </a>
            </li>
            <li class="mb-2">
                <a href="friends.php" class="text-decoration-none d-flex align-items-center">
                    <i class="fas fa-users me-2"></i> Amigos
                </a>
            </li>
            <li class="mb-2">
                <a href="groups.php" class="text-decoration-none d-flex align-items-center">
                    <i class="fas fa-users me-2"></i> Grupos
                </a>
            </li>
            <li class="mb-2">
                <a href="events.php" class="text-decoration-none d-flex align-items-center">
                    <i class="fas fa-calendar me-2"></i> Eventos
                </a>
            </li>
            <li class="mb-2">
                <a href="privacy.php" class="text-decoration-none d-flex align-items-center">
                    <i class="fas fa-shield-alt me-2"></i> Privacidad
                </a>
            </li>
            <li class="mb-2">
                <a href="settings.php" class="text-decoration-none d-flex align-items-center">
                    <i class="fas fa-cog me-2"></i> Configuración
                </a>
            </li>
        </ul>
    </div>
</div>