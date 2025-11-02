<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-3">
                <?php include '../includes/sidebar.php'; ?>
            </div>
            
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Grupos</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                        <i class="fas fa-plus"></i> Crear Grupo
                    </button>
                </div>

                <!-- Mis Grupos -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Mis Grupos</h5>
                    </div>
                    <div class="card-body">
                        <div id="myGroups" class="row">
                            <!-- Los grupos se cargarán aquí -->
                        </div>
                    </div>
                </div>

                <!-- Grupos Recomendados -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Grupos Recomendados</h5>
                    </div>
                    <div class="card-body">
                        <div id="suggestedGroups" class="row">
                            <!-- Los grupos sugeridos se cargarán aquí -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Grupo -->
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createGroupForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="groupName" class="form-label">Nombre del Grupo</label>
                            <input type="text" class="form-control" id="groupName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="groupDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="groupDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="groupCover" class="form-label">Imagen de Portada</label>
                            <input type="file" class="form-control" id="groupCover" name="cover_image" accept="image/*">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="groupPublic" name="is_public" checked>
                            <label class="form-check-label" for="groupPublic">
                                Grupo Público
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
                // Definir currentUser para que esté disponible en JavaScript
        window.currentUser = {
            id: <?php echo $_SESSION['user_id']; ?>,
            username: '<?php echo $_SESSION['username']; ?>'
        };

    // Cargar grupos al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        loadMyGroups();
        loadSuggestedGroups();
        
        // Formulario crear grupo
        document.getElementById('createGroupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            createGroup();
        });
    });

    function loadMyGroups() {
        fetch('../api/groups.php?action=get_my_groups')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderMyGroups(data.groups);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function loadSuggestedGroups() {
        fetch('../api/groups.php?action=get_groups')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                renderSuggestedGroups(data.groups);
            }
        })
        .catch(error => console.error('Error:', error));
    }

    function renderMyGroups(groups) {
        const container = document.getElementById('myGroups');
        if(groups.length === 0) {
            container.innerHTML = '<p class="text-muted">No perteneces a ningún grupo todavía.</p>';
            return;
        }

        container.innerHTML = groups.map(group => `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card group-card h-100">
                    ${group.cover_image ? 
                        `<img src="../assets/uploads/${group.cover_image}" class="card-img-top" alt="${group.name}" style="height: 150px; object-fit: cover;">` :
                        `<div class="card-img-top bg-secondary d-flex align-items-center justify-content-center text-white" style="height: 150px;">
                            <i class="fas fa-users fa-3x"></i>
                        </div>`
                    }
                    <div class="card-body">
                        <h5 class="card-title">${group.name}</h5>
                        <p class="card-text text-muted small">${group.description || 'Sin descripción'}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">${group.member_count} miembros</small>
                            <span class="badge bg-primary">${group.role}</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function renderSuggestedGroups(groups) {
        const container = document.getElementById('suggestedGroups');
        if(groups.length === 0) {
            container.innerHTML = '<p class="text-muted">No hay grupos sugeridos.</p>';
            return;
        }

        container.innerHTML = groups.map(group => `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card group-card h-100">
                    ${group.cover_image ? 
                        `<img src="../assets/uploads/${group.cover_image}" class="card-img-top" alt="${group.name}" style="height: 150px; object-fit: cover;">` :
                        `<div class="card-img-top bg-secondary d-flex align-items-center justify-content-center text-white" style="height: 150px;">
                            <i class="fas fa-users fa-3x"></i>
                        </div>`
                    }
                    <div class="card-body">
                        <h5 class="card-title">${group.name}</h5>
                        <p class="card-text text-muted small">${group.description || 'Sin descripción'}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">${group.member_count} miembros</small>
                            ${group.is_member ? 
                                '<span class="badge bg-success">Miembro</span>' :
                                `<button class="btn btn-sm btn-primary" onclick="joinGroup(${group.id})">Unirse</button>`
                            }
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function createGroup() {
        const formData = new FormData(document.getElementById('createGroupForm'));
        formData.append('action', 'create_group');

        fetch('../api/groups.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                $('#createGroupModal').modal('hide');
                document.getElementById('createGroupForm').reset();
                loadMyGroups();
                loadSuggestedGroups();
            } else {
                alert('Error al crear grupo: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al crear grupo');
        });
    }

    function joinGroup(groupId) {
        fetch('../api/groups.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'join_group',
                group_id: groupId
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                loadSuggestedGroups();
            } else {
                alert('Error al unirse al grupo: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al unirse al grupo');
        });
    }
    </script>
</body>
</html>