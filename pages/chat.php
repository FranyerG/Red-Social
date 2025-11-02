<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Obtener amigos para el chat - CORREGIDO
$query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_picture,
                 (SELECT COUNT(*) FROM messages m WHERE m.from_user_id = u.id AND m.to_user_id = :current_user_id AND m.is_read = FALSE) as unread_count
          FROM users u 
          WHERE u.id IN (
              SELECT CASE 
                         WHEN f.user_id = :current_user_id_2 THEN f.friend_id 
                         ELSE f.user_id 
                     END as friend_id
              FROM friendships f 
              WHERE (f.user_id = :current_user_id_3 OR f.friend_id = :current_user_id_4) 
                AND f.status = 'accepted'
          )
          ORDER BY u.username";
$stmt = $conn->prepare($query);
$stmt->bindParam(':current_user_id', $user_id);
$stmt->bindParam(':current_user_id_2', $user_id);
$stmt->bindParam(':current_user_id_3', $user_id);
$stmt->bindParam(':current_user_id_4', $user_id);
$stmt->execute();
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener conversaciones recientes - CORREGIDO
$query = "SELECT m.*, 
                 u.username as other_username,
                 u.profile_picture as other_profile_picture,
                 u.first_name as other_first_name,
                 u.last_name as other_last_name
          FROM messages m
          INNER JOIN users u ON (
              (m.from_user_id = u.id AND m.to_user_id = :current_user_id_5) 
              OR (m.to_user_id = u.id AND m.from_user_id = :current_user_id_6)
          )
          WHERE m.id IN (
              SELECT MAX(m2.id) 
              FROM messages m2 
              WHERE (m2.from_user_id = :current_user_id_7 OR m2.to_user_id = :current_user_id_8)
              GROUP BY 
                  CASE 
                      WHEN m2.from_user_id = :current_user_id_9 THEN m2.to_user_id 
                      ELSE m2.from_user_id 
                  END
          )
          ORDER BY m.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':current_user_id_5', $user_id);
$stmt->bindParam(':current_user_id_6', $user_id);
$stmt->bindParam(':current_user_id_7', $user_id);
$stmt->bindParam(':current_user_id_8', $user_id);
$stmt->bindParam(':current_user_id_9', $user_id);
$stmt->execute();
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Mi Red Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .chat-container {
            height: calc(100vh - 200px);
            max-height: 600px;
        }
        
        .friends-list {
            height: 100%;
            overflow-y: auto;
            border-right: 1px solid #dee2e6;
        }
        
        .chat-messages {
            height: calc(100% - 120px);
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        
        .chat-input {
            height: 60px;
            border-top: 1px solid #dee2e6;
        }
        
        .message {
            margin-bottom: 15px;
            animation: fadeIn 0.3s ease-in;
        }
        
        .own-message {
            text-align: right;
        }
        
        .other-message {
            text-align: left;
        }
        
        .message-bubble {
            display: inline-block;
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .own-message .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .other-message .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #dee2e6;
            border-bottom-left-radius: 5px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .friend-item {
            cursor: pointer;
            transition: background-color 0.2s;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        
        .friend-item:hover, .friend-item.active {
            background-color: #e9ecef;
        }
        
        .friend-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .unread-badge {
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .conversation-item {
            cursor: pointer;
            transition: background-color 0.2s;
            border-radius: 8px;
            margin-bottom: 5px;
        }
        
        .conversation-item:hover, .conversation-item.active {
            background-color: #e9ecef;
        }
        
        .last-message {
            font-size: 0.85rem;
            color: #6c757d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        .online-indicator {
            width: 10px;
            height: 10px;
            background-color: #28a745;
            border-radius: 50%;
            border: 2px solid white;
            position: absolute;
            bottom: 0;
            right: 0;
        }
        
        .typing-indicator {
            font-size: 0.8rem;
            color: #6c757d;
            font-style: italic;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .chat-container {
                height: calc(100vh - 150px);
            }
            
            .friends-list {
                border-right: none;
                border-bottom: 1px solid #dee2e6;
                height: auto;
                max-height: 200px;
            }
            
            .message-bubble {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-comments me-2"></i>Chat</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="row g-0 chat-container">
                            <!-- Columna de amigos y conversaciones -->
                            <div class="col-md-4">
                                <div class="friends-list p-3">
                                    <!-- Pestañas -->
                                    <ul class="nav nav-pills mb-3" id="chatTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="friends-tab" data-bs-toggle="tab" data-bs-target="#friends" type="button">
                                                <i class="fas fa-users me-1"></i> Amigos
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="conversations-tab" data-bs-toggle="tab" data-bs-target="#conversations" type="button">
                                                <i class="fas fa-comment me-1"></i> Conversaciones
                                            </button>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content">
                                        <!-- Tab: Amigos -->
                                        <div class="tab-pane fade show active" id="friends" role="tabpanel">
                                            <?php if(count($friends) > 0): ?>
                                                <?php foreach($friends as $friend): ?>
                                                    <div class="friend-item p-2 d-flex align-items-center" 
                                                         onclick="startChat(<?php echo $friend['id']; ?>, '<?php echo htmlspecialchars($friend['username']); ?>')">
                                                        <div class="position-relative me-3">
                                                            <img src="../assets/uploads/<?php echo $friend['profile_picture'] ?: 'default.jpg'; ?>" 
                                                                 class="friend-avatar"
                                                                 alt="<?php echo htmlspecialchars($friend['username']); ?>"
                                                                 onerror="this.src='../assets/uploads/default.jpg'">
                                                            <div class="online-indicator" title="En línea"></div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($friend['first_name'] . ' ' . $friend['last_name']); ?></h6>
                                                            <small class="text-muted">@<?php echo htmlspecialchars($friend['username']); ?></small>
                                                        </div>
                                                        <?php if($friend['unread_count'] > 0): ?>
                                                            <span class="unread-badge"><?php echo $friend['unread_count']; ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="text-center py-4 text-muted">
                                                    <i class="fas fa-users fa-2x mb-3"></i>
                                                    <p>No tienes amigos agregados</p>
                                                    <a href="friends.php" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-user-plus me-1"></i> Buscar amigos
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Tab: Conversaciones -->
                                        <div class="tab-pane fade" id="conversations" role="tabpanel">
                                            <?php if(count($conversations) > 0): ?>
                                                <?php foreach($conversations as $conversation): ?>
                                                    <div class="conversation-item p-2 d-flex align-items-center" 
                                                         onclick="startChat(<?php echo $conversation['from_user_id'] == $user_id ? $conversation['to_user_id'] : $conversation['from_user_id']; ?>, '<?php echo htmlspecialchars($conversation['other_username']); ?>')">
                                                        <div class="position-relative me-3">
                                                            <img src="../assets/uploads/<?php echo $conversation['other_profile_picture'] ?: 'default.jpg'; ?>" 
                                                                 class="friend-avatar"
                                                                 alt="<?php echo htmlspecialchars($conversation['other_username']); ?>"
                                                                 onerror="this.src='../assets/uploads/default.jpg'">
                                                            <div class="online-indicator" title="En línea"></div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0"><?php echo htmlspecialchars($conversation['other_first_name'] . ' ' . $conversation['other_last_name']); ?></h6>
                                                            <div class="last-message">
                                                                <?php 
                                                                $message = $conversation['message'];
                                                                if(strlen($message) > 30) {
                                                                    $message = substr($message, 0, 30) . '...';
                                                                }
                                                                echo htmlspecialchars($message);
                                                                ?>
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <small class="text-muted"><?php echo formatDate($conversation['created_at']); ?></small>
                                                            <?php if(!$conversation['is_read'] && $conversation['to_user_id'] == $user_id): ?>
                                                                <span class="unread-badge d-block mt-1">1</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="text-center py-4 text-muted">
                                                    <i class="fas fa-comment fa-2x mb-3"></i>
                                                    <p>No hay conversaciones</p>
                                                    <p class="small">Inicia una conversación con tus amigos</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Área de chat -->
                            <div class="col-md-8">
                                <div class="d-flex flex-column h-100">
                                    <!-- Encabezado del chat -->
                                    <div class="chat-header p-3 border-bottom d-flex align-items-center" id="chatHeader" style="display: none !important;">
                                        <div class="position-relative me-3">
                                            <img src="" class="friend-avatar" id="currentChatAvatar" alt="Avatar">
                                            <div class="online-indicator" id="currentChatOnline"></div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0" id="currentChatName">Selecciona un amigo</h6>
                                            <small class="text-muted" id="currentChatStatus">Desconectado</small>
                                        </div>
                                        <div class="chat-actions">
                                            <button class="btn btn-sm btn-outline-secondary" title="Llamar">
                                                <i class="fas fa-phone"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" title="Video llamada">
                                                <i class="fas fa-video"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" title="Más opciones">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Mensajes -->
                                    <div class="chat-messages p-3" id="chatMessages">
                                        <div class="text-center py-5 text-muted" id="emptyChatState">
                                            <i class="fas fa-comments fa-3x mb-3"></i>
                                            <h5>Bienvenido al Chat</h5>
                                            <p>Selecciona un amigo para comenzar a chatear</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Indicador de typing -->
                                    <div class="typing-indicator px-3 pb-2" id="typingIndicator" style="display: none;">
                                        <i class="fas fa-pencil-alt me-1"></i>
                                        <span id="typingUser"></span> está escribiendo...
                                    </div>
                                    
                                    <!-- Input para enviar mensajes -->
                                    <div class="chat-input p-3" id="chatInput" style="display: none;">
                                        <form id="messageForm" class="d-flex">
                                            <div class="flex-grow-1 me-2">
                                                <div class="input-group">
                                                    <button type="button" class="btn btn-outline-secondary" title="Adjuntar archivo">
                                                        <i class="fas fa-paperclip"></i>
                                                    </button>
                                                    <input type="text" 
                                                           class="form-control" 
                                                           id="messageInput" 
                                                           placeholder="Escribe un mensaje..."
                                                           autocomplete="off">
                                                    <button type="button" class="btn btn-outline-secondary" title="Emojis">
                                                        <i class="fas fa-smile"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary" id="sendMessageBtn">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuración global
        const currentUser = {
            id: <?php echo $user_id; ?>,
            username: '<?php echo $_SESSION['username']; ?>',
            profile_picture: '<?php echo $_SESSION['profile_picture'] ?? 'default.jpg'; ?>'
        };
        
        let currentChat = null;
        let ws = null;
        let isConnected = false;
        let typingTimer = null;

        // Inicializar WebSocket
        function connectWebSocket() {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.hostname}:8080`;
            
            try {
                ws = new WebSocket(wsUrl);
                
                ws.onopen = function() {
                    isConnected = true;
                    console.log('Conectado al servidor de chat');
                    registerUser();
                };
                
                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    handleWebSocketMessage(data);
                };
                
                ws.onclose = function() {
                    isConnected = false;
                    console.log('Desconectado del servidor de chat');
                    // Intentar reconectar después de 5 segundos
                    setTimeout(connectWebSocket, 5000);
                };
                
                ws.onerror = function(error) {
                    console.error('Error en WebSocket:', error);
                };
                
            } catch (error) {
                console.error('Error al conectar WebSocket:', error);
                showError('No se pudo conectar al chat en tiempo real');
            }
        }

        function registerUser() {
            if(isConnected && ws) {
                ws.send(JSON.stringify({
                    type: 'register',
                    user_id: currentUser.id,
                    username: currentUser.username
                }));
            }
        }

        function handleWebSocketMessage(data) {
            switch(data.type) {
                case 'private_message':
                    if(currentChat && data.from_user_id == currentChat.id) {
                        addMessageToChat(data, false);
                        markMessageAsRead(data.message_id);
                    } else {
                        // Mostrar notificación
                        showMessageNotification(data);
                    }
                    break;
                    
                case 'message_read':
                    // Actualizar estado de mensajes como leídos
                    updateMessageReadStatus(data.message_ids);
                    break;
                    
                case 'user_typing':
                    if(currentChat && data.user_id == currentChat.id) {
                        showTypingIndicator(data.username);
                    }
                    break;
                    
                case 'user_online':
                    updateUserOnlineStatus(data.user_id, true);
                    break;
                    
                case 'user_offline':
                    updateUserOnlineStatus(data.user_id, false);
                    break;
            }
        }

        // Iniciar chat con un amigo
        function startChat(friendId, friendUsername) {
            // Obtener información del amigo
            fetch(`../api/chat.php?action=get_friend_info&friend_id=${friendId}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    currentChat = {
                        id: friendId,
                        username: friendUsername,
                        ...data.friend
                    };
                    
                    updateChatUI();
                    loadChatHistory(friendId);
                    
                    // Marcar como activo en la lista
                    document.querySelectorAll('.friend-item, .conversation-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    event.currentTarget.classList.add('active');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error al cargar información del amigo');
            });
        }

        function updateChatUI() {
            // Mostrar elementos del chat
            document.getElementById('chatHeader').style.display = 'flex';
            document.getElementById('chatInput').style.display = 'block';
            document.getElementById('emptyChatState').style.display = 'none';
            
            // Actualizar información del chat
            document.getElementById('currentChatName').textContent = 
                `${currentChat.first_name} ${currentChat.last_name}`;
            document.getElementById('currentChatAvatar').src = 
                `../assets/uploads/${currentChat.profile_picture || 'default.jpg'}`;
            document.getElementById('currentChatStatus').textContent = 'En línea';
            document.getElementById('currentChatOnline').style.display = 'block';
            
            // Enfocar el input
            document.getElementById('messageInput').focus();
        }

        function loadChatHistory(friendId) {
            fetch(`../api/chat.php?action=get_messages&friend_id=${friendId}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    renderChatHistory(data.messages);
                    // Marcar mensajes como leídos
                    markConversationAsRead(friendId);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error al cargar historial de chat');
            });
        }

        function renderChatHistory(messages) {
            const container = document.getElementById('chatMessages');
            container.innerHTML = '';
            
            if(messages.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-comment-slash fa-2x mb-3"></i>
                        <p>No hay mensajes aún</p>
                        <p class="small">¡Envía el primer mensaje!</p>
                    </div>
                `;
                return;
            }
            
            messages.forEach(message => {
                const isOwn = message.from_user_id == currentUser.id;
                addMessageToChat(message, isOwn);
            });
            
            // Scroll al final
            container.scrollTop = container.scrollHeight;
        }

        function addMessageToChat(message, isOwn) {
            const container = document.getElementById('chatMessages');
            
            // Si está vacío, limpiar el estado vacío
            if(container.querySelector('.text-center')) {
                container.innerHTML = '';
            }
            
            const messageElement = createMessageElement(message, isOwn);
            container.appendChild(messageElement);
            
            // Scroll al nuevo mensaje
            container.scrollTop = container.scrollHeight;
        }

        function createMessageElement(message, isOwn) {
            const div = document.createElement('div');
            div.className = `message ${isOwn ? 'own-message' : 'other-message'}`;
            
            const time = new Date(message.created_at).toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            div.innerHTML = `
                <div class="message-bubble">
                    <div class="message-content">${escapeHtml(message.message)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            
            return div;
        }

        // Enviar mensaje
        document.getElementById('messageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if(!message || !currentChat) return;
            
            if(isConnected && ws) {
                // Enviar por WebSocket
                ws.send(JSON.stringify({
                    type: 'private_message',
                    to_user_id: currentChat.id,
                    message: message
                }));
                
                // Agregar mensaje localmente (optimista)
                const tempMessage = {
                    from_user_id: currentUser.id,
                    message: message,
                    created_at: new Date().toISOString()
                };
                addMessageToChat(tempMessage, true);
                
                input.value = '';
                
            } else {
                // Fallback a AJAX
                fetch('../api/chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'send_message',
                        to_user_id: currentChat.id,
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        addMessageToChat(data.message, true);
                        input.value = '';
                    } else {
                        showError('Error al enviar mensaje');
                        // Remover mensaje optimista si falla
                        const messages = document.querySelectorAll('.message');
                        if(messages.length > 0) {
                            messages[messages.length - 1].remove();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Error de conexión');
                    // Remover mensaje optimista
                    const messages = document.querySelectorAll('.message');
                    if(messages.length > 0) {
                        messages[messages.length - 1].remove();
                    }
                });
            }
        }

        // Indicador de typing
        document.getElementById('messageInput').addEventListener('input', function() {
            if(!currentChat || !isConnected) return;
            
            // Enviar evento de typing
            if(isConnected && ws) {
                ws.send(JSON.stringify({
                    type: 'user_typing',
                    to_user_id: currentChat.id,
                    username: currentUser.username
                }));
            }
            
            // Limpiar timer anterior
            clearTimeout(typingTimer);
            
            // Configurar nuevo timer para detener typing
            typingTimer = setTimeout(() => {
                if(isConnected && ws) {
                    ws.send(JSON.stringify({
                        type: 'user_stop_typing',
                        to_user_id: currentChat.id
                    }));
                }
            }, 1000);
        });

        function showTypingIndicator(username) {
            const indicator = document.getElementById('typingIndicator');
            document.getElementById('typingUser').textContent = username;
            indicator.style.display = 'block';
            
            // Ocultar después de 3 segundos
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }

        function markConversationAsRead(friendId) {
            fetch('../api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_conversation_read',
                    friend_id: friendId
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Actualizar UI - quitar badges de mensajes no leídos
                    updateUnreadCounts();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function markMessageAsRead(messageId) {
            if(isConnected && ws) {
                ws.send(JSON.stringify({
                    type: 'message_read',
                    message_id: messageId,
                    from_user_id: currentChat.id
                }));
            }
        }

        function updateMessageReadStatus(messageIds) {
            // Actualizar UI para mostrar mensajes como leídos
            messageIds.forEach(messageId => {
                const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                if(messageElement) {
                    messageElement.classList.add('read');
                }
            });
        }

        function updateUserOnlineStatus(userId, isOnline) {
            const indicators = document.querySelectorAll(`[data-user-id="${userId}"] .online-indicator`);
            indicators.forEach(indicator => {
                indicator.style.backgroundColor = isOnline ? '#28a745' : '#6c757d';
                indicator.title = isOnline ? 'En línea' : 'Desconectado';
            });
            
            if(currentChat && currentChat.id == userId) {
                document.getElementById('currentChatStatus').textContent = 
                    isOnline ? 'En línea' : 'Desconectado';
                document.getElementById('currentChatOnline').style.backgroundColor = 
                    isOnline ? '#28a745' : '#6c757d';
            }
        }

        function updateUnreadCounts() {
            // Actualizar badges de mensajes no leídos
            fetch('../api/chat.php?action=get_unread_counts')
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    data.unread_counts.forEach(item => {
                        const badge = document.querySelector(`[data-user-id="${item.friend_id}"] .unread-badge`);
                        if(badge) {
                            if(item.unread_count > 0) {
                                badge.textContent = item.unread_count;
                                badge.style.display = 'flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function showMessageNotification(message) {
            // Mostrar notificación del sistema
            if(Notification.permission === 'granted') {
                new Notification(`Nuevo mensaje de ${message.from_username}`, {
                    body: message.message,
                    icon: '../assets/uploads/default.jpg'
                });
            }
            
            // Actualizar badge en el navbar
            updateNavbarNotificationBadge();
        }

        function updateNavbarNotificationBadge() {
            // Actualizar badge global de notificaciones
            const badge = document.getElementById('notificationBadge');
            if(badge) {
                const currentCount = parseInt(badge.textContent) || 0;
                badge.textContent = currentCount + 1;
                badge.style.display = 'inline-block';
            }
        }

        // Utilidades
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showError(message) {
            // Crear notificación toast de error
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-danger border-0';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            const toastContainer = document.getElementById('toastContainer') || createToastContainer();
            toastContainer.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
            return container;
        }

        // Solicitar permisos para notificaciones
        if('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // Inicializar cuando cargue la página
        document.addEventListener('DOMContentLoaded', function() {
            connectWebSocket();
            
            // Configurar event listeners
            document.getElementById('messageInput').addEventListener('keypress', function(e) {
                if(e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        });
    </script>
</body>
</html>