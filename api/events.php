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
        case 'create_event':
            $title = sanitize($_POST['title']);
            $description = sanitize($_POST['description']);
            $location = sanitize($_POST['location']);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'] ?? null;
            $is_public = isset($_POST['is_public']) ? 1 : 0;
            $max_attendees = $_POST['max_attendees'] ?? null;
            $cover_image = null;
            
            if(isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                $cover_image = uploadImage($_FILES['cover_image'], '../assets/uploads/');
            }
            
            $query = "INSERT INTO events (user_id, title, description, location, start_date, end_date, is_public, max_attendees, cover_image) 
                     VALUES (:user_id, :title, :description, :location, :start_date, :end_date, :is_public, :max_attendees, :cover_image)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':is_public', $is_public);
            $stmt->bindParam(':max_attendees', $max_attendees);
            $stmt->bindParam(':cover_image', $cover_image);
            
            if($stmt->execute()) {
                $event_id = $conn->lastInsertId();
                
                // El creador automáticamente va al evento
                $query = "INSERT INTO event_attendees (event_id, user_id, status) VALUES (:event_id, :user_id, 'going')";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':event_id', $event_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'event_id' => $event_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear evento']);
            }
            break;
            
        case 'rsvp_event':
            $event_id = $input['event_id'];
            $status = $input['status'];
            
            // Verificar si ya respondió
            $query = "SELECT id FROM event_attendees WHERE event_id = :event_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':event_id', $event_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $query = "UPDATE event_attendees SET status = :status WHERE event_id = :event_id AND user_id = :user_id";
            } else {
                $query = "INSERT INTO event_attendees (event_id, user_id, status) VALUES (:event_id, :user_id, :status)";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':event_id', $event_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            break;
    }
} elseif($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'get_events':
            $query = "SELECT e.*, u.username, u.profile_picture,
                     (SELECT COUNT(*) FROM event_attendees ea WHERE ea.event_id = e.id AND ea.status = 'going') as going_count,
                     (SELECT COUNT(*) FROM event_attendees ea WHERE ea.event_id = e.id AND ea.status = 'interested') as interested_count,
                     EXISTS(SELECT 1 FROM event_attendees ea WHERE ea.event_id = e.id AND ea.user_id = :user_id) as has_responded,
                     (SELECT status FROM event_attendees ea WHERE ea.event_id = e.id AND ea.user_id = :user_id) as user_status
                     FROM events e
                     JOIN users u ON e.user_id = u.id
                     WHERE e.start_date > NOW()
                     AND (e.is_public = TRUE OR e.user_id = :user_id 
                         OR e.id IN (SELECT event_id FROM event_invites WHERE to_user_id = :user_id AND status = 'accepted')
                     )
                     ORDER BY e.start_date ASC
                     LIMIT 20";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'events' => $events]);
            break;
            
        case 'get_calendar_events':
            $start = $_GET['start'] ?? date('Y-m-01');
            $end = $_GET['end'] ?? date('Y-m-t');
            
            $query = "SELECT e.id, e.title, e.start_date as start, 
                     COALESCE(e.end_date, e.start_date) as end,
                     e.location,
                     (SELECT status FROM event_attendees ea WHERE ea.event_id = e.id AND ea.user_id = :user_id) as user_status
                     FROM events e
                     WHERE e.start_date BETWEEN :start AND :end
                     AND (e.is_public = TRUE OR e.user_id = :user_id 
                         OR e.id IN (SELECT event_id FROM event_attendees WHERE user_id = :user_id)
                     )
                     ORDER BY e.start_date";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':start', $start);
            $stmt->bindParam(':end', $end);
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'events' => $events]);
            break;
    }
}
?>