<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

// Obtener fotos del usuario actual
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM photos WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$user_id]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener foto de perfil actual
$profile_picture = $_SESSION['profile_picture'] ?? 'default.jpg';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fotos - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .photo-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .photo-card:hover {
            transform: translateY(-5px);
        }
        .photo-actions {
            opacity: 0;
            transition: opacity 0.3s;
        }
        .photo-card:hover .photo-actions {
            opacity: 1;
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        .upload-area:hover {
            border-color: #0d6efd;
        }
        .profile-picture-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">
                            <img src="../assets/uploads/<?php echo $profile_picture; ?>" 
                                 class="rounded-circle mb-3" width="100" height="100" alt="Profile">
                            <h5><?php echo $_SESSION['username']; ?></h5>
                        </div>
                        <hr>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="dashboard.php" class="text-decoration-none"><i class="fas fa-home"></i> Inicio</a></li>
                            <li class="mb-2"><a href="friends.php" class="text-decoration-none"><i class="fas fa-users"></i> Amigos</a></li>
                            <li class="mb-2"><a href="fotos.php" class="text-decoration-none"><i class="fas fa-images"></i> Fotos</a></li>
                            <li class="mb-2"><a href="settings.php" class="text-decoration-none"><i class="fas fa-cog"></i> Configuración</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-images"></i> Mis Fotos</h4>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-plus"></i> Subir Foto
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Mostrar mensajes de éxito/error -->
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($photos)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-images fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No tienes fotos aún</h5>
                                <p class="text-muted">Comparte tus mejores momentos subiendo una foto.</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($photos as $photo): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card photo-card position-relative">
                                            <?php if ($photo['filename'] === $profile_picture): ?>
                                                <span class="profile-picture-indicator">
                                                    <i class="fas fa-user-circle"></i> Perfil
                                                </span>
                                            <?php endif; ?>
                                            <img src="../assets/uploads/photos/<?php echo $photo['filename']; ?>" 
                                                 class="card-img-top" alt="Foto" style="height: 200px; object-fit: cover;">
                                            <div class="card-body">
                                                <p class="card-text small text-muted">
                                                    <?php echo date('d/m/Y', strtotime($photo['created_at'])); ?>
                                                </p>
                                                <?php if ($photo['description']): ?>
                                                    <p class="card-text"><?php echo htmlspecialchars($photo['description']); ?></p>
                                                <?php endif; ?>
                                                <div class="photo-actions">
                                                    <?php if ($photo['filename'] !== $profile_picture): ?>
                                                        <button class="btn btn-sm btn-outline-primary set-profile-picture" 
                                                                data-photo-id="<?php echo $photo['id']; ?>">
                                                            <i class="fas fa-user-circle"></i> Usar como perfil
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-danger delete-photo" 
                                                            data-photo-id="<?php echo $photo['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para subir foto -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subir Nueva Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="../api/upload_photo.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="upload-area" onclick="document.getElementById('photoInput').click()">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <p>Haz clic o arrastra una imagen aquí</p>
                            <input type="file" id="photoInput" name="photo" accept="image/*" style="display: none;" required>
                        </div>
                        <div id="previewContainer" class="mt-3 text-center" style="display: none;">
                            <img id="previewImage" src="#" alt="Vista previa" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                        <div class="mb-3 mt-3">
                            <label for="description" class="form-label">Descripción (opcional)</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Subir Foto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Definir currentUser para que esté disponible en JavaScript
        window.currentUser = {
            id: <?php echo $_SESSION['user_id']; ?>,
            username: '<?php echo $_SESSION['username']; ?>'
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Vista previa de la imagen
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                    document.getElementById('previewContainer').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop
        const uploadArea = document.querySelector('.upload-area');
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#0d6efd';
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '#dee2e6';
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#dee2e6';
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                document.getElementById('photoInput').files = e.dataTransfer.files;
                const event = new Event('change');
                document.getElementById('photoInput').dispatchEvent(event);
            }
        });

        // Establecer como foto de perfil
        document.querySelectorAll('.set-profile-picture').forEach(button => {
            button.addEventListener('click', function() {
                const photoId = this.getAttribute('data-photo-id');
                if (confirm('¿Estás seguro de que quieres usar esta foto como perfil?')) {
                    window.location.href = `../api/set_profile_picture.php?photo_id=${photoId}`;
                }
            });
        });

        // Eliminar foto
        document.querySelectorAll('.delete-photo').forEach(button => {
            button.addEventListener('click', function() {
                const photoId = this.getAttribute('data-photo-id');
                if (confirm('¿Estás seguro de que quieres eliminar esta foto?')) {
                    window.location.href = `../api/delete_photo.php?photo_id=${photoId}`;
                }
            });
        });
    </script>
</body>
</html>