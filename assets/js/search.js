// assets/js/search.js
class SearchManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        const searchInput = document.getElementById('globalSearch');
        if(searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.performSearch(searchInput.value);
            }, 300));
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    performSearch(query) {
        if(query.length < 2) {
            this.hideResults();
            return;
        }

        fetch(`../api/search.php?q=${encodeURIComponent(query)}&type=all`)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.displayResults(data.results, query);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    displayResults(results, query) {
        let resultsHTML = '';
        
        // Usuarios
        if(results.users && results.users.length > 0) {
            resultsHTML += '<div class="search-category"><h6>Usuarios</h6>';
            results.users.forEach(user => {
                resultsHTML += `
                    <div class="search-result-item p-2 border-bottom">
                        <div class="d-flex align-items-center">
                            <img src="../assets/uploads/${user.profile_picture}" class="rounded-circle me-3" width="40" height="40" alt="Profile">
                            <div>
                                <h6 class="mb-0">${user.username}</h6>
                                <small class="text-muted">${user.first_name} ${user.last_name}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            resultsHTML += '</div>';
        }

        // Grupos
        if(results.groups && results.groups.length > 0) {
            resultsHTML += '<div class="search-category"><h6>Grupos</h6>';
            results.groups.forEach(group => {
                resultsHTML += `
                    <div class="search-result-item p-2 border-bottom">
                        <div class="d-flex align-items-center">
                            ${group.cover_image ? 
                                `<img src="../assets/uploads/${group.cover_image}" class="rounded me-3" width="40" height="40" alt="Group">` :
                                `<div class="bg-secondary rounded me-3 d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px;">
                                    <i class="fas fa-users"></i>
                                </div>`
                            }
                            <div>
                                <h6 class="mb-0">${group.name}</h6>
                                <small class="text-muted">${group.member_count} miembros</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            resultsHTML += '</div>';
        }

        // Posts
        if(results.posts && results.posts.length > 0) {
            resultsHTML += '<div class="search-category"><h6>Publicaciones</h6>';
            results.posts.forEach(post => {
                const content = post.content.length > 100 ? post.content.substring(0, 100) + '...' : post.content;
                resultsHTML += `
                    <div class="search-result-item p-2 border-bottom">
                        <div class="d-flex align-items-start">
                            <img src="../assets/uploads/${post.profile_picture}" class="rounded-circle me-3" width="32" height="32" alt="Profile">
                            <div>
                                <h6 class="mb-0">${post.username}</h6>
                                <p class="mb-0 text-muted">${content}</p>
                                <small class="text-muted">${new Date(post.created_at).toLocaleDateString()}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            resultsHTML += '</div>';
        }

        if(!resultsHTML) {
            resultsHTML = '<div class="p-3 text-center text-muted">No se encontraron resultados</div>';
        }

        this.showResults(resultsHTML);
    }

    showResults(html) {
        let resultsContainer = document.getElementById('searchResults');
        if(!resultsContainer) {
            resultsContainer = document.createElement('div');
            resultsContainer.id = 'searchResults';
            resultsContainer.className = 'search-results dropdown-menu show';
            document.querySelector('.search-container').appendChild(resultsContainer);
        }
        
        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';
    }

    hideResults() {
        const resultsContainer = document.getElementById('searchResults');
        if(resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }
}

// Inicializar búsqueda
document.addEventListener('DOMContentLoaded', function() {
    window.searchManager = new SearchManager();
    
    // Ocultar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if(!e.target.closest('.search-container')) {
            window.searchManager.hideResults();
        }
    });
});