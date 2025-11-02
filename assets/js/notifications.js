// assets/js/notifications.js
class NotificationManager {
    constructor() {
        this.notificationCount = 0;
        this.init();
    }

    init() {
        this.loadNotifications();
        this.setupEventListeners();
        // Actualizar notificaciones cada 30 segundos
        setInterval(() => this.loadNotifications(), 30000);
    }

    loadNotifications() {
        fetch('../api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_notifications'
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.updateNotificationBadge(data.notifications);
                this.renderNotifications(data.notifications);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    updateNotificationBadge(notifications) {
        const unreadCount = notifications.filter(n => !n.is_read).length;
        this.notificationCount = unreadCount;
        
        // Actualizar badge en la interfaz
        const badge = document.getElementById('notificationBadge');
        if(badge) {
            badge.textContent = unreadCount;
            badge.style.display = unreadCount > 0 ? 'inline-block' : 'none';
        }
    }

    renderNotifications(notifications) {
        const container = document.getElementById('notificationsList');
        if(!container) return;
        
        container.innerHTML = '';
        
        notifications.forEach(notification => {
            const notificationElement = this.createNotificationElement(notification);
            container.appendChild(notificationElement);
        });
    }

    createNotificationElement(notification) {
        const div = document.createElement('div');
        div.className = `notification-item p-3 border-bottom ${notification.is_read ? '' : 'bg-light'}`;
        div.innerHTML = `
            <div class="d-flex align-items-start">
                ${notification.from_user_id ? 
                    `<img src="../assets/uploads/${notification.profile_picture}" class="rounded-circle me-3" width="40" height="40" alt="Profile">` : 
                    `<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="fas fa-bell"></i>
                    </div>`
                }
                <div class="flex-grow-1">
                    <p class="mb-1">${notification.message}</p>
                    <small class="text-muted">${new Date(notification.created_at).toLocaleString()}</small>
                </div>
                ${!notification.is_read ? 
                    `<button class="btn btn-sm btn-outline-primary mark-as-read" data-id="${notification.id}">
                        <i class="fas fa-check"></i>
                    </button>` : ''
                }
            </div>
        `;
        
        const markAsReadBtn = div.querySelector('.mark-as-read');
        if(markAsReadBtn) {
            markAsReadBtn.addEventListener('click', () => {
                this.markAsRead(notification.id, div);
            });
        }
        
        return div;
    }

    markAsRead(notificationId, element) {
        fetch('../api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_as_read',
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                element.classList.remove('bg-light');
                this.notificationCount--;
                this.updateNotificationBadge([]); // Forzar actualización
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    markAllAsRead() {
        fetch('../api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_all_as_read'
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.loadNotifications();
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    setupEventListeners() {
        const markAllReadBtn = document.getElementById('markAllRead');
        if(markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllAsRead();
            });
        }
    }
}

// Inicializar manager de notificaciones
document.addEventListener('DOMContentLoaded', function() {
    window.notificationManager = new NotificationManager();
});