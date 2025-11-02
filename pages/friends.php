<?php
session_start();
require_once '../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$db = new Database();
$conn = $db->getConnection();

// Obtener parámetros
$tab = $_GET['tab'] ?? 'all';
$search = $_GET['search'] ?? '';
$profile_user_id = $_GET['user_id'] ?? $user_id;
$is_own_profile = ($profile_user_id == $user_id);

// Obtener información del usuario del perfil
$stmt = $conn->prepare("SELECT username, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$profile_user_id]);
$profile_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile_user) {
    die("Usuario no encontrado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amigos - <?php echo htmlspecialchars($profile_user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .friend-card {
            transition: transform 0.2s;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
        }
        
        .friend-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .friend-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
        }
        
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .search-box {
            max-width: 400px;
        }
        
        .action-buttons {
            min-width: 120px;
        }
        
        .toast-container {
            z-index: 9999;
        }
        
        .mutual-count {
            background-color: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
   <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Encabezado -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">
                    <?php if($is_own_profile): ?>
                        Mis Amigos
                    <?php else: ?>
                        Amigos de <?php echo htmlspecialchars($profile_user['first_name'] . ' ' . $profile_user['last_name']); ?>
                    <?php endif; ?>
                </h2>
                <p class="text-muted mb-0" id="friendsCount">Cargando...</p>
            </div>
            
            <a href="profile.php<?php echo !$is_own_profile ? '?user_id=' . $profile_user_id : ''; ?>" 
               class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Perfil
            </a>
        </div>

        <!-- Pestañas -->
        <ul class="nav nav-tabs mb-4" id="friendsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'all' ? 'active' : ''; ?>" 
                        id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                    <i class="fas fa-users me-1"></i> Todos
                </button>
            </li>
            
            <?php if($is_own_profile): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'requests' ? 'active' : ''; ?>" 
                        id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button">
                    <i class="fas fa-user-clock me-1"></i> Solicitudes
                    <span class="badge bg-danger ms-1" id="requestsBadge">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'sent' ? 'active' : ''; ?>" 
                        id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button">
                    <i class="fas fa-paper-plane me-1"></i> Enviadas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'suggested' ? 'active' : ''; ?>" 
                        id="suggested-tab" data-bs-toggle="tab" data-bs-target="#suggested" type="button">
                    <i class="fas fa-user-plus me-1"></i> Sugerencias
                </button>
            </li>
            <?php endif; ?>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tab === 'search' ? 'active' : ''; ?>" 
                        id="search-tab" data-bs-toggle="tab" data-bs-target="#search" type="button">
                    <i class="fas fa-search me-1"></i> Buscar
                </button>
            </li>
        </ul>

        <div class="tab-content" id="friendsTabsContent">
            <!-- Tab: Todos los amigos -->
            <div class="tab-pane fade <?php echo $tab === 'all' ? 'show active' : ''; ?>" id="all" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Lista de Amigos</h5>
                    <div class="search-box">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchFriends" 
                                   placeholder="Buscar entre amigos...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div id="friendsList" class="row">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando amigos...</p>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button class="btn btn-outline-primary" id="loadMoreFriends">
                        <i class="fas fa-plus me-1"></i> Cargar más amigos
                    </button>
                </div>
            </div>

            <?php if($is_own_profile): ?>
            <!-- Tab: Solicitudes recibidas -->
            <div class="tab-pane fade <?php echo $tab === 'requests' ? 'show active' : ''; ?>" id="requests" role="tabpanel">
                <h5 class="mb-3">Solicitudes de Amistad Recibidas</h5>
                <div id="friendRequestsList">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando solicitudes...</p>
                    </div>
                </div>
            </div>

            <!-- Tab: Solicitudes enviadas -->
            <div class="tab-pane fade <?php echo $tab === 'sent' ? 'show active' : ''; ?>" id="sent" role="tabpanel">
                <h5 class="mb-3">Solicitudes de Amistad Enviadas</h5>
                <div id="sentRequestsList">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando solicitudes enviadas...</p>
                    </div>
                </div>
            </div>

            <!-- Tab: Sugerencias -->
            <div class="tab-pane fade <?php echo $tab === 'suggested' ? 'show active' : ''; ?>" id="suggested" role="tabpanel">
                <h5 class="mb-3">Personas que quizás conozcas</h5>
                <div id="suggestedFriendsList" class="row">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Buscando sugerencias...</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tab: Buscar -->
            <div class="tab-pane fade <?php echo $tab === 'search' ? 'show active' : ''; ?>" id="search" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-3">Buscar Usuarios</h5>
                        
                        <div class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchUsersInput" 
                                       placeholder="Buscar por nombre o usuario...">
                                <button class="btn btn-primary" type="button" id="searchUsersBtn">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                        
                        <div id="searchResults">
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-3x mb-3"></i>
                                <p>Ingresa un nombre o usuario para buscar</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor para notificaciones toast -->
    <div class="toast-container position-fixed top-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuración
        const currentUserId = <?php echo $user_id; ?>;
        const profileUserId = <?php echo $profile_user_id; ?>;
        const isOwnProfile = <?php echo $is_own_profile ? 'true' : 'false'; ?>;
        
        let friendsOffset = 0;
        const friendsLimit = 12;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadFriendsCount();
            loadFriends();
            
            if(isOwnProfile) {
                loadFriendRequests();
                loadSentRequests();
                loadSuggestedFriends();
            }
            
            // Event listeners
            document.getElementById('loadMoreFriends').addEventListener('click', loadMoreFriends);
            document.getElementById('searchUsersBtn').addEventListener('click', searchUsers);
            document.getElementById('searchUsersInput').addEventListener('keypress', function(e) {
                if(e.key === 'Enter') searchUsers();
            });
            document.getElementById('searchFriends').addEventListener('input', filterFriends);
        });

        // Funciones de carga de datos
        async function loadFriendsCount() {
            try {
                const response = await fetch(`../api/friends_api.php?action=get_friends_count&user_id=${profileUserId}`);
                const data = await response.json();
                
                if(data.success) {
                    document.getElementById('friendsCount').textContent = `${data.count} amigos`;
                }
            } catch(error) {
                console.error('Error:', error);
                document.getElementById('friendsCount').textContent = 'Error al cargar';
            }
        }

        async function loadFriends() {
            try {
                const response = await fetch(
                    `../api/friends_api.php?action=get_friends&user_id=${profileUserId}&limit=${friendsLimit}&offset=${friendsOffset}`
                );
                const data = await response.json();
                
                if(data.success) {
                    renderFriends(data.friends);
                    friendsOffset += data.friends.length;
                    
                    if(data.friends.length < friendsLimit) {
                        document.getElementById('loadMoreFriends').style.display = 'none';
                    }
                } else {
                    showError('Error al cargar amigos');
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error de conexión');
            }
        }

        function loadMoreFriends() {
            loadFriends();
        }

        <?php if($is_own_profile): ?>
        async function loadFriendRequests() {
            try {
                const response = await fetch('../api/friends_api.php?action=get_friend_requests&type=received');
                const data = await response.json();
                
                if(data.success) {
                    renderFriendRequests(data.requests);
                    document.getElementById('requestsBadge').textContent = data.requests.length;
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al cargar solicitudes');
            }
        }

        async function loadSentRequests() {
            try {
                const response = await fetch('../api/friends_api.php?action=get_friend_requests&type=sent');
                const data = await response.json();
                
                if(data.success) {
                    renderSentRequests(data.requests);
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al cargar solicitudes enviadas');
            }
        }

        async function loadSuggestedFriends() {
            try {
                const response = await fetch('../api/friends_api.php?action=get_suggested_friends&limit=12');
                const data = await response.json();
                
                if(data.success) {
                    renderSuggestedFriends(data.suggested);
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al cargar sugerencias');
            }
        }
        <?php endif; ?>

        // Funciones de renderizado
        function renderFriends(friends) {
            const container = document.getElementById('friendsList');
            
            if(friends.length === 0 && friendsOffset === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">${isOwnProfile ? 'Aún no tienes amigos.' : 'Este usuario no tiene amigos.'}</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            friends.forEach(friend => {
                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card friend-card h-100">
                            <div class="card-body text-center">
                                <img src="../assets/uploads/${friend.profile_picture || 'default-avatar.jpg'}" 
                                     class="friend-avatar mb-3"
                                     alt="${friend.username}"
                                     onerror="this.src='../assets/uploads/default-avatar.jpg'">
                                <h5 class="card-title mb-1">${friend.first_name} ${friend.last_name}</h5>
                                <p class="card-text text-muted mb-2">@${friend.username}</p>
                                ${friend.bio ? `<p class="card-text small text-muted">${friend.bio.substring(0, 100)}${friend.bio.length > 100 ? '...' : ''}</p>` : ''}
                                <div class="mutual-count mb-3 d-inline-block">
                                    <small>Amigos desde ${new Date(friend.friends_since).toLocaleDateString()}</small>
                                </div>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="profile.php?user_id=${friend.id}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i> Ver Perfil
                                    </a>
                                    ${isOwnProfile ? `
                                    <button class="btn btn-outline-danger btn-sm" onclick="removeFriend(${friend.id})">
                                        <i class="fas fa-user-times me-1"></i> Eliminar
                                    </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            if(friendsOffset === 0) {
                container.innerHTML = html;
            } else {
                container.innerHTML += html;
            }
        }

        <?php if($is_own_profile): ?>
        function renderFriendRequests(requests) {
            const container = document.getElementById('friendRequestsList');
            
            if(requests.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No tienes solicitudes de amistad pendientes</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            requests.forEach(request => {
                html += `
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <img src="../assets/uploads/${request.profile_picture || 'default-avatar.jpg'}" 
                                     class="rounded-circle me-3"
                                     width="60"
                                     height="60"
                                     alt="${request.username}"
                                     onerror="this.src='../assets/uploads/default-avatar.jpg'">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${request.first_name} ${request.last_name}</h6>
                                    <p class="text-muted mb-1">@${request.username}</p>
                                    <small class="text-muted">Solicitud enviada ${new Date(request.created_at).toLocaleDateString()}</small>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-success btn-sm me-1" onclick="acceptFriendRequest(${request.friendship_id})">
                                        <i class="fas fa-check"></i> Aceptar
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectFriendRequest(${request.friendship_id})">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function renderSentRequests(requests) {
            const container = document.getElementById('sentRequestsList');
            
            if(requests.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-paper-plane fa-3x mb-3"></i>
                        <p>No has enviado solicitudes de amistad</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            requests.forEach(request => {
                html += `
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <img src="../assets/uploads/${request.profile_picture || 'default-avatar.jpg'}" 
                                     class="rounded-circle me-3"
                                     width="60"
                                     height="60"
                                     alt="${request.username}"
                                     onerror="this.src='../assets/uploads/default-avatar.jpg'">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${request.first_name} ${request.last_name}</h6>
                                    <p class="text-muted mb-1">@${request.username}</p>
                                    <small class="text-muted">Solicitud enviada ${new Date(request.created_at).toLocaleDateString()}</small>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-warning btn-sm" onclick="cancelFriendRequest(${request.friendship_id})">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function renderSuggestedFriends(suggested) {
            const container = document.getElementById('suggestedFriendsList');
            
            if(suggested.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fas fa-user-plus fa-3x mb-3"></i>
                        <p>No hay sugerencias de amigos en este momento</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            suggested.forEach(user => {
                html += `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card friend-card h-100">
                            <div class="card-body text-center">
                                <img src="../assets/uploads/${user.profile_picture || 'default-avatar.jpg'}" 
                                     class="friend-avatar mb-3"
                                     alt="${user.username}"
                                     onerror="this.src='../assets/uploads/default-avatar.jpg'">
                                <h5 class="card-title mb-1">${user.first_name} ${user.last_name}</h5>
                                <p class="card-text text-muted mb-2">@${user.username}</p>
                                ${user.bio ? `<p class="card-text small text-muted">${user.bio.substring(0, 100)}${user.bio.length > 100 ? '...' : ''}</p>` : ''}
                                ${user.mutual_friends > 0 ? `
                                <div class="mutual-count text-primary mb-3">
                                    <small><i class="fas fa-users me-1"></i> ${user.mutual_friends} amigos en común</small>
                                </div>
                                ` : ''}
                                <button class="btn btn-primary btn-sm" onclick="sendFriendRequest(${user.id})">
                                    <i class="fas fa-user-plus me-1"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        <?php endif; ?>

        // Búsqueda de usuarios
        async function searchUsers() {
            const searchTerm = document.getElementById('searchUsersInput').value.trim();
            
            if(!searchTerm) {
                showError('Ingresa un término de búsqueda');
                return;
            }
            
            try {
                const response = await fetch('../api/friends_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'search_users',
                        search_term: searchTerm,
                        limit: 20
                    })
                });
                const data = await response.json();
                
                if(data.success) {
                    renderSearchResults(data.users);
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error de conexión');
            }
        }

        function renderSearchResults(users) {
            const container = document.getElementById('searchResults');
            
            if(users.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <p>No se encontraron usuarios</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            users.forEach(user => {
                let buttonHtml = '';
                
                if(user.is_friend) {
                    buttonHtml = `<span class="badge bg-success">Amigos</span>`;
                } else if(user.request_sent) {
                    buttonHtml = `<span class="badge bg-warning">Solicitud enviada</span>`;
                } else if(user.request_received) {
                    buttonHtml = `
                        <button class="btn btn-success btn-sm me-1" onclick="acceptFriendRequestFromSearch(${user.id})">
                            <i class="fas fa-check"></i> Aceptar
                        </button>
                    `;
                } else {
                    buttonHtml = `<button class="btn btn-primary btn-sm" onclick="sendFriendRequest(${user.id})">
                                    <i class="fas fa-user-plus"></i> Agregar
                                </button>`;
                }
                
                html += `
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <img src="../assets/uploads/${user.profile_picture || 'default-avatar.jpg'}" 
                                     class="rounded-circle me-3"
                                     width="60"
                                     height="60"
                                     alt="${user.username}"
                                     onerror="this.src='../assets/uploads/default-avatar.jpg'">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">${user.first_name} ${user.last_name}</h6>
                                    <p class="text-muted mb-0">@${user.username}</p>
                                    ${user.bio ? `<small class="text-muted">${user.bio.substring(0, 80)}${user.bio.length > 80 ? '...' : ''}</small>` : ''}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    ${buttonHtml}
                                    <a href="profile.php?user_id=${user.id}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        // Filtrar amigos en la lista
        function filterFriends() {
            const searchTerm = document.getElementById('searchFriends').value.toLowerCase();
            const friendCards = document.querySelectorAll('#friendsList .card');
            
            friendCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.parentElement.style.display = text.includes(searchTerm) ? 'block' : 'none';
            });
        }

        // Funciones de acciones
        async function sendFriendRequest(friendId) {
            try {
                const response = await fetch('../api/friends_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'send_friend_request',
                        friend_id: friendId
                    })
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    if(isOwnProfile) {
                        loadSentRequests();
                        loadSuggestedFriends();
                    }
                    if(document.getElementById('searchResults').innerHTML !== '') {
                        searchUsers();
                    }
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al enviar solicitud');
            }
        }

        async function acceptFriendRequest(friendshipId) {
            try {
                const response = await fetch('../api/friends_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'accept_friend_request',
                        friendship_id: friendshipId
                    })
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadFriendRequests();
                    loadFriendsCount();
                    loadFriends();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al aceptar solicitud');
            }
        }

        async function rejectFriendRequest(friendshipId) {
            if(!confirm('¿Estás seguro de que quieres rechazar esta solicitud de amistad?')) return;
            
            try {
                const response = await fetch('../api/friends_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'reject_friend_request',
                        friendship_id: friendshipId
                    })
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadFriendRequests();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al rechazar solicitud');
            }
        }

        async function cancelFriendRequest(friendshipId) {
            if(!confirm('¿Estás seguro de que quieres cancelar esta solicitud de amistad?')) return;
            
            try {
                const response = await fetch('../api/friends_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'cancel_friend_request',
                        friendship_id: friendshipId
                    })
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadSentRequests();
                    loadSuggestedFriends();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al cancelar solicitud');
            }
        }

        async function removeFriend(friendId) {
            if(!confirm('¿Estás seguro de que quieres eliminar a este amigo?')) return;
            
            try {
                const response = await fetch('../api/friends_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'remove_friend',
                        friend_id: friendId
                    })
                });
                const data = await response.json();
                
                if(data.success) {
                    showSuccess(data.message);
                    loadFriendsCount();
                    loadFriends();
                } else {
                    showError(data.message);
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al eliminar amigo');
            }
        }

        async function acceptFriendRequestFromSearch(userId) {
            try {
                const response = await fetch('../api/friends_api.php?action=get_friend_requests&type=received');
                const data = await response.json();
                
                if(data.success) {
                    const request = data.requests.find(req => req.id === userId);
                    if(request) {
                        acceptFriendRequest(request.friendship_id);
                    } else {
                        showError('Solicitud no encontrada');
                    }
                }
            } catch(error) {
                console.error('Error:', error);
                showError('Error al aceptar solicitud');
            }
        }

        // Funciones de utilidad
        function showSuccess(message) {
            showToast(message, 'success');
        }

        function showError(message) {
            showToast(message, 'danger');
        }

        function showToast(message, type) {
            const toastContainer = document.querySelector('.toast-container');
            const toastId = 'toast-' + Date.now();
            
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
    </script>
</body>
</html>