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

if($_SERVER['REQUEST_METHOD'] == 'GET') {
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all';
    
    if(empty($query)) {
        echo json_encode(['success' => false, 'message' => 'Término de búsqueda vacío']);
        exit;
    }
    
    $searchTerm = "%$query%";
    $results = [];
    
    // Búsqueda de usuarios
    if($type == 'all' || $type == 'users') {
        $sql = "SELECT id, username, first_name, last_name, profile_picture, bio 
               FROM users 
               WHERE username LIKE :query 
               OR first_name LIKE :query 
               OR last_name LIKE :query 
               AND id != :user_id 
               LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':query', $searchTerm);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $results['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Búsqueda de posts
    if($type == 'all' || $type == 'posts') {
        $sql = "SELECT p.*, u.username, u.profile_picture 
               FROM posts p 
               JOIN users u ON p.user_id = u.id 
               WHERE p.content LIKE :query 
               ORDER BY p.created_at DESC 
               LIMIT 20";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':query', $searchTerm);
        $stmt->execute();
        $results['posts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Búsqueda de grupos
    if($type == 'all' || $type == 'groups') {
        $sql = "SELECT g.*, u.username as created_by_username,
               (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) as member_count
               FROM groups g 
               JOIN users u ON g.created_by = u.id 
               WHERE g.name LIKE :query 
               OR g.description LIKE :query 
               ORDER BY g.created_at DESC 
               LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':query', $searchTerm);
        $stmt->execute();
        $results['groups'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode(['success' => true, 'results' => $results, 'query' => $query]);
}
?>