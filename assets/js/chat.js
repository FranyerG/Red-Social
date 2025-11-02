// assets/js/chat.js
class ChatApp {
    constructor() {
        this.ws = null;
        this.isConnected = false;
        this.currentChat = null;
        this.init();
    }

    init() {
        this.connectWebSocket();
        this.setupEventListeners();
    }

    connectWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.hostname}:8080`;
        
        this.ws = new WebSocket(wsUrl);
        
        this.ws.onopen = () => {
            this.isConnected = true;
            this.registerUser();
            console.log('Conectado al chat');
        };
        
        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleMessage(data);
        };
        
        this.ws.onclose = () => {
            this.isConnected = false;
            console.log('Desconectado del chat');
            // Reconectar después de 5 segundos
            setTimeout(() => this.connectWebSocket(), 5000);
        };
    }

    registerUser() {
        if(this.isConnected) {
            this.ws.send(JSON.stringify({
                type: 'register',
                user_id: window.currentUser.id,
                username: window.currentUser.username
            }));
        }
    }

    setupEventListeners() {
        // Abrir modal de chat
        document.getElementById('openChat').addEventListener('click', () => {
            this.openChatModal();
        });

        // Enviar mensaje
        document.getElementById('sendMessage').addEventListener('click', () => {
            this.sendMessage();
        });

        // Enter para enviar mensaje
        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if(e.key === 'Enter') {
                this.sendMessage();
            }
        });
    }

    openChatModal() {
        // Cargar lista de amigos para chat
        this.loadFriendsList();
        $('#chatModal').modal('show');
    }

    loadFriendsList() {
        fetch('../api/friends.php?action=get_friends')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.renderFriendsList(data.friends);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    renderFriendsList(friends) {
        const container = document.getElementById('friendsList');
        container.innerHTML = '';
        
        friends.forEach(friend => {
            const friendElement = this.createFriendElement(friend);
            container.appendChild(friendElement);
        });
    }

    createFriendElement(friend) {
        const div = document.createElement('div');
        div.className = 'friend-item p-3 border-bottom cursor-pointer';
        div.innerHTML = `
            <div class="d-flex align-items-center">
                <img src="../assets/uploads/${friend.profile_picture}" class="rounded-circle me-3" width="40" height="40" alt="Profile">
                <div class="flex-grow-1">
                    <h6 class="mb-0">${friend.username}</h6>
                    <small class="text-muted status-indicator" id="status-${friend.id}">Desconectado</small>
                </div>
            </div>
        `;
        
        div.addEventListener('click', () => {
            this.startChat(friend);
        });
        
        return div;
    }

    startChat(friend) {
        this.currentChat = friend;
        document.getElementById('chatWith').textContent = friend.username;
        document.getElementById('chatMessages').innerHTML = '';
        document.getElementById('messageInput').disabled = false;
        document.getElementById('sendMessage').disabled = false;
        
        // Cargar historial de mensajes
        this.loadChatHistory(friend.id);
    }

    sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if(!message || !this.currentChat) return;
        
        if(this.isConnected) {
            this.ws.send(JSON.stringify({
                type: 'private_message',
                to_user_id: this.currentChat.id,
                message: message
            }));
            
            // Agregar mensaje localmente
            this.addMessageToChat({
                from_user_id: window.currentUser.id,
                from_username: window.currentUser.username,
                message: message,
                timestamp: new Date().toISOString()
            }, true);
            
            input.value = '';
        }
    }

    handleMessage(data) {
        switch(data.type) {
            case 'private_message':
                if(this.currentChat && data.from_user_id == this.currentChat.id) {
                    this.addMessageToChat(data, false);
                } else {
                    // Mostrar notificación
                    this.showNotification(data);
                }
                break;
        }
    }

    addMessageToChat(message, isOwn) {
        const messagesContainer = document.getElementById('chatMessages');
        const messageElement = this.createMessageElement(message, isOwn);
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    createMessageElement(message, isOwn) {
        const div = document.createElement('div');
        div.className = `message ${isOwn ? 'own-message' : 'other-message'} mb-3`;
        div.innerHTML = `
            <div class="message-bubble ${isOwn ? 'bg-primary text-white' : 'bg-light'} p-3 rounded">
                <div class="message-header d-flex justify-content-between align-items-center mb-2">
                    <strong>${isOwn ? 'Tú' : message.from_username}</strong>
                    <small>${new Date(message.timestamp).toLocaleTimeString()}</small>
                </div>
                <div class="message-content">${message.message}</div>
            </div>
        `;
        return div;
    }

    loadChatHistory(friendId) {
        fetch(`../api/chat.php?action=get_messages&friend_id=${friendId}`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                data.messages.forEach(message => {
                    const isOwn = message.from_user_id == window.currentUser.id;
                    this.addMessageToChat(message, isOwn);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    showNotification(message) {
        // Implementar notificaciones push
        if(Notification.permission === 'granted') {
            new Notification(`Nuevo mensaje de ${message.from_username}`, {
                body: message.message,
                icon: '../assets/uploads/default.jpg'
            });
        }
    }
}

// Inicializar chat cuando la página cargue
document.addEventListener('DOMContentLoaded', function() {
    window.chatApp = new ChatApp();
    
    // Solicitar permisos para notificaciones
    if('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});