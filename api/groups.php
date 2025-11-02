<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_POST['action'] ?? $input['action'] ?? '';
    
    switch($action) {
        case 'create_group':
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            $is_public = isset($_POST['is_public']) ? 1 : 0;
            $cover_image = null;
            
            if(isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                $cover_image = uploadImage($_FILES['cover_image'], '../assets/uploads/');
            }
            
            $query = "INSERT INTO groups (name, description, cover_image, created_by, is_public) 
                     VALUES (:name, :description, :cover_image, :created_by, :is_public)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':cover_image', $cover_image);
            $stmt->bindParam(':created_by', $user_id);
            $stmt->bindParam(':is_public', $is_public);
            
            if($stmt->execute()) {
                $group_id = $conn->lastInsertId();
                
                // Agregar creador como admin
                $query = "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, 'admin')";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':group_id', $group_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'group_id' => $group_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear grupo']);
            }
            break;
            
        case 'join_group':
            $group_id = $input['group_id'];
            
            // Verificar si ya es miembro
            $query = "SELECT id FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':group_id', $group_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() == 0) {
                $query = "INSERT INTO group_members (group_id, user_id) VALUES (:group_id, :user_id)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':group_id', $group_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Ya eres miembro de este grupo']);
            }
            break;
    }
} elseif($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'get_groups':
            $query = "SELECT g.*, u.username as created_by_username, 
                     (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) as member_count,
                     EXISTS(SELECT 1 FROM group_members gm WHERE gm.group_id = g.id AND gm.user_id = :user_id) as is_member
                     FROM groups g 
                     JOIN users u ON g.created_by = u.id 
                     WHERE g.is_public = TRUE 
                     ORDER BY g.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'groups' => $groups]);
            break;
            
        case 'get_my_groups':
            $query = "SELECT g.*, u.username as created_by_username, gm.role,
                     (SELECT COUNT(*) FROM group_members gm2 WHERE gm2.group_id = g.id) as member_count
                     FROM groups g 
                     JOIN group_members gm ON g.id = gm.group_id 
                     JOIN users u ON g.created_by = u.id 
                     WHERE gm.user_id = :user_id 
                     ORDER BY g.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'groups' => $groups]);
            break;
    }
}
?>