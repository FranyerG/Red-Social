// Funcionalidad para posts
document.addEventListener('DOMContentLoaded', function() {
    const postForm = document.getElementById('postForm');
    const postsContainer = document.getElementById('postsContainer');
    
    if(postForm) {
        postForm.addEventListener('submit', function(e) {
            e.preventDefault();
            createPost();
        });
    }
    
    // Cargar posts al iniciar
    loadPosts();
});

function createPost() {
    const content = document.getElementById('postContent').value;
    const imageInput = document.getElementById('postImage');
    const formData = new FormData();
    
    if (!content.trim()) {
        alert('El post no puede estar vacío');
        return;
    }
    
    formData.append('content', content);
    formData.append('action', 'create_post');
    
    if(imageInput.files[0]) {
        formData.append('image', imageInput.files[0]);
    }
    
    fetch('../api/posts.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            document.getElementById('postContent').value = '';
            imageInput.value = '';
            loadPosts(); // Recargar todos los posts
            showNotification('Post publicado exitosamente', 'success');
        } else {
            showNotification('Error al crear el post: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al crear el post', 'error');
    });
}

function likePost(postId, button) {
    fetch('../api/posts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'like_post',
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Actualizar apariencia del botón
            const isLiked = data.liked;
            button.classList.toggle('btn-outline-primary', !isLiked);
            button.classList.toggle('btn-primary', isLiked);
            
            // Actualizar texto del botón
            const likeText = isLiked ? 'Te gusta' : 'Me gusta';
            const likeCount = data.like_count || 0;
            
            button.innerHTML = `
                <i class="fas fa-thumbs-up${isLiked ? ' text-white' : ''}"></i> 
                ${likeText} ${likeCount > 0 ? `(${likeCount})` : ''}
            `;
            
            // Actualizar contador de likes si existe
            const likeCountElement = document.getElementById(`like-count-${postId}`);
            if (likeCountElement) {
                likeCountElement.textContent = likeCount;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al dar like', 'error');
    });
}

function loadPosts() {
    fetch('../api/posts.php?action=get_posts')
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            renderPosts(data.posts);
        } else {
            showNotification('Error al cargar posts', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
}

function renderPosts(posts) {
    const container = document.getElementById('postsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (posts.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="fas fa-newspaper fa-3x mb-3"></i>
                <p>No hay posts disponibles. ¡Sé el primero en publicar!</p>
            </div>
        `;
        return;
    }
    
    posts.forEach(post => {
        const postElement = createPostElement(post);
        container.appendChild(postElement);
    });
}

function createPostElement(post) {
    const div = document.createElement('div');
    div.className = 'card mb-4 post fade-in';
    div.innerHTML = `
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <img src="../assets/uploads/${post.profile_picture}" 
                     class="rounded-circle me-3" 
                     width="45" 
                     height="45" 
                     alt="${post.username}"
                     onerror="this.src='../assets/uploads/default.jpg'">
                <div class="flex-grow-1">
                    <h6 class="mb-0">${post.username}</h6>
                    <small class="text-muted">${formatDate(post.created_at)}</small>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                            type="button" 
                            data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-flag me-2"></i>Reportar</a></li>
                        ${post.user_id === window.currentUser.id ? 
                            `<li><a class="dropdown-item text-danger" href="#" onclick="deletePost(${post.id})">
                                <i class="fas fa-trash me-2"></i>Eliminar
                            </a></li>` : ''
                        }
                    </ul>
                </div>
            </div>
            
            <p class="card-text">${post.content.replace(/\n/g, '<br>')}</p>
            
            ${post.image ? `
            <div class="post-image mb-3">
                <img src="../assets/uploads/${post.image}" 
                     class="img-fluid rounded" 
                     alt="Post image"
                     style="max-height: 500px; object-fit: cover; cursor: pointer;"
                     onclick="viewImage('${post.image}')">
            </div>
            ` : ''}
            
            <div class="post-stats mb-2">
                <small class="text-muted">
                    <span id="like-count-${post.id}">${post.like_count || 0}</span> me gusta • 
                    <span id="comment-count-${post.id}">${post.comment_count || 0}</span> comentarios
                </small>
            </div>
            
            <div class="post-actions border-top border-bottom py-2 mb-3">
                <div class="d-flex justify-content-around">
                    <button class="btn btn-outline-primary btn-sm like-btn ${post.user_liked ? 'btn-primary' : ''}" 
                            data-post-id="${post.id}">
                        <i class="fas fa-thumbs-up${post.user_liked ? ' text-white' : ''}"></i> 
                        ${post.user_liked ? 'Te gusta' : 'Me gusta'}
                    </button>
                    <button class="btn btn-outline-secondary btn-sm comment-toggle-btn" 
                            data-post-id="${post.id}">
                        <i class="fas fa-comment"></i> Comentar
                    </button>
                    <button class="btn btn-outline-secondary btn-sm share-btn" 
                            data-post-id="${post.id}">
                        <i class="fas fa-share"></i> Compartir
                    </button>
                </div>
            </div>
            
            <!-- Sección de comentarios (oculta inicialmente) -->
            <div class="comments-section" id="comments-${post.id}" style="display: none;">
                <div class="comment-form mb-3">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control" 
                               id="comment-input-${post.id}" 
                               placeholder="Escribe un comentario..."
                               onkeypress="if(event.key === 'Enter') addComment(${post.id})">
                        <button class="btn btn-primary" 
                                type="button" 
                                onclick="addComment(${post.id})">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                <div class="comments-list" id="comments-list-${post.id}">
                    <!-- Los comentarios se cargarán aquí -->
                </div>
            </div>
        </div>
    `;
    
    // Agregar eventos
    const likeBtn = div.querySelector('.like-btn');
    likeBtn.addEventListener('click', function() {
        likePost(post.id, this);
    });
    
    const commentToggleBtn = div.querySelector('.comment-toggle-btn');
    commentToggleBtn.addEventListener('click', function() {
        toggleComments(post.id);
    });
    
    // Cargar comentarios si el post tiene comentarios
    if (post.comment_count > 0) {
        setTimeout(() => loadComments(post.id), 100);
    }
    
    return div;
}

// FUNCIONALIDAD DE COMENTARIOS

function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    const isHidden = commentsSection.style.display === 'none' || !commentsSection.style.display;
    
    if (isHidden) {
        commentsSection.style.display = 'block';
        loadComments(postId);
        
        // Enfocar el input de comentario
        setTimeout(() => {
            const commentInput = document.getElementById(`comment-input-${postId}`);
            if (commentInput) commentInput.focus();
        }, 300);
    } else {
        commentsSection.style.display = 'none';
    }
}

function loadComments(postId) {
    fetch('../api/comments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_comments',
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            renderComments(postId, data.comments);
            updateCommentCount(postId, data.comments.length);
        }
    })
    .catch(error => {
        console.error('Error al cargar comentarios:', error);
    });
}

function renderComments(postId, comments) {
    const container = document.getElementById(`comments-list-${postId}`);
    if (!container) return;
    
    container.innerHTML = '';
    
    if (comments.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-3">
                <small>No hay comentarios aún. ¡Sé el primero en comentar!</small>
            </div>
        `;
        return;
    }
    
    comments.forEach(comment => {
        const commentElement = createCommentElement(comment);
        container.appendChild(commentElement);
    });
    
    // Scroll al final de los comentarios
    container.scrollTop = container.scrollHeight;
}

function createCommentElement(comment) {
    const div = document.createElement('div');
    div.className = 'comment mb-3 p-3 bg-light rounded';
    div.id = `comment-${comment.id}`;
    
    const isOwner = comment.user_id === window.currentUser.id;
    const deleteButton = isOwner ? 
        `<button class="btn btn-sm btn-outline-danger ms-2 delete-comment" 
                data-comment-id="${comment.id}"
                title="Eliminar comentario">
            <i class="fas fa-trash"></i>
        </button>` : '';
    
    div.innerHTML = `
        <div class="d-flex align-items-start">
            <img src="../assets/uploads/${comment.profile_picture}" 
                 class="rounded-circle me-3" 
                 width="32" 
                 height="32" 
                 alt="${comment.username}"
                 onerror="this.src='../assets/uploads/default.jpg'">
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <h6 class="mb-0 d-inline-block">${comment.username}</h6>
                        <small class="text-muted ms-2">${formatDate(comment.created_at)}</small>
                    </div>
                    ${deleteButton}
                </div>
                <p class="mb-0">${comment.content}</p>
            </div>
        </div>
    `;
    
    // Agregar evento para eliminar comentario
    const deleteBtn = div.querySelector('.delete-comment');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            deleteComment(comment.id, div);
        });
    }
    
    return div;
}

function addComment(postId) {
    const input = document.getElementById(`comment-input-${postId}`);
    const content = input.value.trim();
    
    if (!content) {
        showNotification('El comentario no puede estar vacío', 'warning');
        return;
    }
    
    fetch('../api/comments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add_comment',
            post_id: postId,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            input.value = '';
            const commentsList = document.getElementById(`comments-list-${postId}`);
            const commentElement = createCommentElement(data.comment);
            commentsList.appendChild(commentElement);
            
            // Actualizar contador de comentarios
            updateCommentCount(postId, 'increment');
            
            // Scroll al nuevo comentario
            commentsList.scrollTop = commentsList.scrollHeight;
            
            showNotification('Comentario agregado', 'success');
        } else {
            showNotification('Error al agregar comentario: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al agregar comentario', 'error');
    });
}

function deleteComment(commentId, commentElement) {
    if (!confirm('¿Estás seguro de que quieres eliminar este comentario?')) {
        return;
    }
    
    fetch('../api/comments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete_comment',
            comment_id: commentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            commentElement.remove();
            
            // Actualizar contador de comentarios
            const postId = getPostIdFromComment(commentId);
            if (postId) {
                updateCommentCount(postId, 'decrement');
            }
            
            showNotification('Comentario eliminado', 'success');
        } else {
            showNotification('Error al eliminar comentario: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al eliminar comentario', 'error');
    });
}

function updateCommentCount(postId, change) {
    const countElement = document.getElementById(`comment-count-${postId}`);
    if (!countElement) return;
    
    let currentCount = parseInt(countElement.textContent) || 0;
    
    if (change === 'increment') {
        currentCount++;
    } else if (change === 'decrement') {
        currentCount = Math.max(0, currentCount - 1);
    } else if (typeof change === 'number') {
        currentCount = change;
    }
    
    countElement.textContent = currentCount;
}

function getPostIdFromComment(commentId) {
    const commentElement = document.getElementById(`comment-${commentId}`);
    if (!commentElement) return null;
    
    const commentsSection = commentElement.closest('.comments-section');
    if (!commentsSection) return null;
    
    const idMatch = commentsSection.id.match(/comments-(\d+)/);
    return idMatch ? parseInt(idMatch[1]) : null;
}

// FUNCIONES AUXILIARES

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Ahora mismo';
    if (diffMins < 60) return `Hace ${diffMins} min`;
    if (diffHours < 24) return `Hace ${diffHours} h`;
    if (diffDays < 7) return `Hace ${diffDays} d`;
    
    return date.toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}

function showNotification(message, type = 'info') {
    // Crear notificación toast
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
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
    
    // Remover el toast después de que se oculte
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

function deletePost(postId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este post? Esta acción no se puede deshacer.')) {
        return;
    }
    
    fetch('../api/posts.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'delete_post',
            post_id: postId
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const postElement = document.querySelector(`[data-post-id="${postId}"]`)?.closest('.post');
            if (postElement) {
                postElement.remove();
            }
            showNotification('Post eliminado', 'success');
        } else {
            showNotification('Error al eliminar post: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al eliminar post', 'error');
    });
}

function viewImage(imageUrl) {
    // Implementar visor de imagen modal
    const modalHTML = `
        <div class="modal fade" id="imageModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark">
                    <div class="modal-header border-0">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="../assets/uploads/${imageUrl}" class="img-fluid" style="max-height: 80vh;">
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
    imageModal.show();
    
    // Limpiar cuando se cierre el modal
    document.getElementById('imageModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Funcionalidad para búsqueda en tiempo real
const searchInput = document.getElementById('searchInput');
if(searchInput) {
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        filterPosts(searchTerm);
    });
}

function filterPosts(searchTerm) {
    const posts = document.querySelectorAll('.post');
    
    posts.forEach(post => {
        const content = post.querySelector('.card-text').textContent.toLowerCase();
        const username = post.querySelector('h6').textContent.toLowerCase();
        
        if(content.includes(searchTerm) || username.includes(searchTerm)) {
            post.style.display = 'block';
        } else {
            post.style.display = 'none';
        }
    });
}

// Inicializar tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});