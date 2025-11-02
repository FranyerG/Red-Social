<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">MiRedSocial</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Búsqueda -->
            <div class="search-container me-3 position-relative">
                <form class="d-flex">
                    <input class="form-control me-2" type="search" id="globalSearch" 
                           placeholder="Buscar usuarios, grupos..." aria-label="Search">
                </form>
            </div>
            
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php"><i class="fas fa-home"></i> Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php"><i class="fas fa-user"></i> Perfil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="friends.php"><i class="fas fa-users"></i> Amigos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="groups.php"><i class="fas fa-users"></i> Grupos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events.php"><i class="fas fa-calendar"></i> Eventos</a>
                </li>
                <!-- NUEVO BOTÓN DE MENSAJES -->
                <li class="nav-item">
                    <a class="nav-link" href="chat.php"><i class="fas fa-envelope"></i> Mensajes</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <!-- Chat Rápido (modal) -->
                <li class="nav-item">
                    <a class="nav-link" href="#" id="openChat">
                        <i class="fas fa-comments"></i>
                    </a>
                </li>
                
                <!-- Notificaciones -->
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" id="notificationDropdown" 
                       role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                              id="notificationBadge" style="display: none;">0</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                        <li><h6 class="dropdown-header">Notificaciones</h6></li>
                        <li><div class="dropdown-item" id="notificationsList"></div></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#" id="markAllRead">Marcar todas como leídas</a></li>
                    </ul>
                </li>
                
                <!-- Usuario -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['username']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="privacy.php">Privacidad</a></li>
                        <li><a class="dropdown-item" href="settings.php">Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Cerrar Sesión</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>



<script>
// Configuración global del usuario para JavaScript
window.currentUser = {
    id: <?php echo $_SESSION['user_id']; ?>,
    username: '<?php echo $_SESSION['username']; ?>'
};
</script>