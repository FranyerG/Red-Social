// Funcionalidad para stories
class StoriesManager {
    constructor() {
        this.currentStoryIndex = 0;
        this.currentUserIndex = 0;
        this.stories = [];
        this.init();
    }

    init() {
        this.loadStories();
        this.setupEventListeners();
    }

    loadStories() {
        fetch('../api/stories.php?action=get_stories')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.stories = data.stories;
                this.renderStories();
            }
        })
        .catch(error => console.error('Error:', error));
    }

    renderStories() {
        const container = document.getElementById('storiesContainer');
        if(!container) return;

        if(this.stories.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-camera fa-3x mb-3"></i>
                    <p>No hay stories disponibles</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.stories.map(userStories => `
            <div class="story-item" onclick="storiesManager.openStories(${userStories.user.id})">
                <div class="story-avatar">
                    <img src="../assets/uploads/${userStories.user.profile_picture}" alt="${userStories.user.username}">
                </div>
                <div class="story-username">${userStories.user.username}</div>
            </div>
        `).join('');
    }

    openStories(userId) {
        const userStories = this.stories.find(s => s.user.id == userId);
        if(!userStories) return;

        this.currentUserStories = userStories;
        this.currentStoryIndex = 0;
        this.showStoryModal();
    }

    showStoryModal() {
        // Crear modal de stories
        const modalHTML = `
            <div class="modal fade" id="storyModal" tabindex="-1">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content bg-dark">
                        <div class="modal-header border-0">
                            <div class="d-flex align-items-center w-100">
                                <div class="progress flex-grow-1 me-3">
                                    ${this.currentUserStories.stories.map((_, index) => `
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: ${100/this.currentUserStories.stories.length}%"
                                             id="storyProgress-${index}"></div>
                                    `).join('')}
                                </div>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                        </div>
                        <div class="modal-body d-flex align-items-center justify-content-center">
                            <div class="story-content text-center text-white">
                                <!-- El contenido de la story se cargará aquí -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const storyModal = new bootstrap.Modal(document.getElementById('storyModal'));
        storyModal.show();

        this.showCurrentStory();
        this.startStoryTimer();

        // Event listeners para navegación
        document.getElementById('storyModal').addEventListener('click', (e) => {
            const rect = e.target.getBoundingClientRect();
            const x = e.clientX - rect.left;
            if(x < rect.width / 2) {
                this.previousStory();
            } else {
                this.nextStory();
            }
        });

        // Limpiar cuando se cierre el modal
        document.getElementById('storyModal').addEventListener('hidden.bs.modal', () => {
            document.getElementById('storyModal').remove();
            clearInterval(this.storyTimer);
        });
    }

    showCurrentStory() {
        const story = this.currentUserStories.stories[this.currentStoryIndex];
        const storyContent = document.querySelector('#storyModal .story-content');
        
        let contentHTML = '';
        if(story.image) {
            contentHTML = `<img src="../assets/uploads/${story.image}" class="img-fluid rounded" style="max-height: 80vh;">`;
        } else if(story.video) {
            contentHTML = `<video src="../assets/uploads/${story.video}" class="img-fluid rounded" style="max-height: 80vh;" controls autoplay></video>`;
        } else {
            contentHTML = `
                <div class="story-text p-5 rounded" style="background: ${story.background_color}; color: ${story.text_color}; max-width: 500px;">
                    <h3>${story.content}</h3>
                </div>
            `;
        }

        storyContent.innerHTML = contentHTML;

        // Marcar como vista
        if(!story.viewed) {
            this.markStoryAsViewed(story.id);
        }

        // Actualizar progress bars
        this.updateProgressBars();
    }

    startStoryTimer() {
        clearInterval(this.storyTimer);
        this.storyTimer = setInterval(() => {
            this.nextStory();
        }, 5000); // 5 segundos por story
    }

    nextStory() {
        if(this.currentStoryIndex < this.currentUserStories.stories.length - 1) {
            this.currentStoryIndex++;
            this.showCurrentStory();
            this.startStoryTimer();
        } else {
            // Cerrar modal cuando se acaben las stories
            bootstrap.Modal.getInstance(document.getElementById('storyModal')).hide();
        }
    }

    previousStory() {
        if(this.currentStoryIndex > 0) {
            this.currentStoryIndex--;
            this.showCurrentStory();
            this.startStoryTimer();
        }
    }

    updateProgressBars() {
        this.currentUserStories.stories.forEach((_, index) => {
            const progressBar = document.getElementById(`storyProgress-${index}`);
            if(progressBar) {
                if(index < this.currentStoryIndex) {
                    progressBar.classList.add('bg-white');
                    progressBar.style.width = '100%';
                } else if(index === this.currentStoryIndex) {
                    progressBar.classList.add('bg-white');
                    progressBar.style.width = '100%';
                    // Aquí podrías añadir animación de progreso
                } else {
                    progressBar.classList.remove('bg-white');
                    progressBar.style.width = '0%';
                }
            }
        });
    }

    markStoryAsViewed(storyId) {
        fetch('../api/stories.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'view_story',
                story_id: storyId
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Actualizar localmente
                const story = this.currentUserStories.stories.find(s => s.id == storyId);
                if(story) story.viewed = true;
            }
        })
        .catch(error => console.error('Error:', error));
    }

    setupEventListeners() {
        // Crear story
        const createStoryBtn = document.getElementById('createStoryBtn');
        if(createStoryBtn) {
            createStoryBtn.addEventListener('click', () => {
                this.openCreateStoryModal();
            });
        }
    }

    openCreateStoryModal() {
        // Implementar modal para crear story
        console.log('Abrir modal para crear story');
    }
}

// Inicializar stories manager
document.addEventListener('DOMContentLoaded', function() {
    window.storiesManager = new StoriesManager();
});