<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$auth = new Auth();
$db = new Database();
$conn = $db->getConnection();

// Obtener posts
$query = "SELECT p.*, u.username, u.profile_picture 
          FROM posts p 
          JOIN users u ON p.user_id = u.id 
          ORDER BY p.created_at DESC 
          LIMIT 20";
$stmt = $conn->prepare($query);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                            <img src="../assets/uploads/default.jpg" class="rounded-circle mb-3" width="100" height="100" alt="Profile">
                            <h5><?php echo $_SESSION['username']; ?></h5>
                        </div>
                        <hr>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="friends.php" class="text-decoration-none"><i class="fas fa-users"></i> Amigos</a></li>
                            <li class="mb-2"><a href="fotos.php" class="text-decoration-none"><i class="fas fa-images"></i> Fotos</a></li>
                            <li class="mb-2"><a href="settings.php" class="text-decoration-none"><i class="fas fa-cog"></i> Configuración</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal -->
            <div class="col-lg-6">
                <!-- Crear Post -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="postForm">
                            <div class="mb-3">
                                <textarea class="form-control" id="postContent" rows="3" placeholder="¿Qué estás pensando?" required></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <input type="file" id="postImage" accept="image/*" class="d-none">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('postImage').click()">
                                        <i class="fas fa-image"></i> Foto
                                    </button>
                                </div>
                                <button type="submit" class="btn btn-primary">Publicar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Posts -->
                <div id="postsContainer">
                    <?php foreach($posts as $post): ?>
                    <div class="card mb-3 post">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="../assets/uploads/<?php echo $post['profile_picture']; ?>" class="rounded-circle me-3" width="40" height="40" alt="Profile">
                                <div>
                                    <h6 class="mb-0"><?php echo $post['username']; ?></h6>
                                    <small class="text-muted"><?php echo date('d M Y H:i', strtotime($post['created_at'])); ?></small>
                                </div>
                            </div>
                            
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            
                            <?php if($post['image']): ?>
                            <div class="post-image mb-3">
                                <img src="../assets/uploads/<?php echo $post['image']; ?>" class="img-fluid rounded" alt="Post image">
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-primary btn-sm like-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="fas fa-thumbs-up"></i> Me gusta
                                </button>
                                <button class="btn btn-outline-secondary btn-sm comment-btn">
                                    <i class="fas fa-comment"></i> Comentar
                                </button>
                                <button class="btn btn-outline-secondary btn-sm share-btn">
                                    <i class="fas fa-share"></i> Compartir
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar Derecha -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Amigos en línea</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="../assets/uploads/default.jpg" class="rounded-circle me-3" width="40" height="40" alt="Friend">
                            <div>
                                <h6 class="mb-0">Usuario Amigo</h6>
                                <small class="text-success">En línea</small>
                            </div>
                        </div>
                        <!-- Más amigos... -->
                    </div>
                </div>
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
    <script src="../assets/js/main.js"></script>
</body>
</html>