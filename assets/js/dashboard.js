// public/js/dashboard.js

export function initDashboard(config) {
    if (!config || !config.recentTasksUrl) {
        console.warn('Configuration du dashboard invalide');
        return;
    }

    const recentTasksContainerDesktop = document.querySelector('.col-lg-4.d-none.d-lg-block .card-body');
    const recentTasksContainerMobile = document.querySelector('.col-12.d-lg-none.mt-4 .card-body');

    let updateInterval = null;
    let isDocumentVisible = !document.hidden;
    let activeController = null;

    // Fonction de fetch avec timeout
    async function fetchWithTimeout(url, options = {}, timeout = 5000) {
        const controller = new AbortController();
        activeController = controller;
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        try {
            const response = await fetch(url, { ...options, signal: controller.signal });
            clearTimeout(timeoutId);
            return response;
        } finally {
            activeController = null;
        }
    }

    // Fonction pour formater une date en format "dd/mm HH:MM"
    function formatDate(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month} ${hours}:${minutes}`;
    }

    // Fonction pour déterminer la classe du badge de statut
    function getStatusBadgeClass(status) {
        if (status === 'todo') return 'badge-todo';
        if (status === 'in_progress' || status === 'en_cours') return 'badge-progress';
        if (status === 'completed') return 'badge-completed';
        return 'badge-secondary';
    }

    // Fonction pour déterminer la classe du badge de priorité
    function getPriorityBadgeClass(priority) {
        if (priority === 'high') return 'bg-danger';
        if (priority === 'medium') return 'bg-warning';
        return 'bg-secondary';
    }

    // Fonction pour créer le HTML d'une tâche
    function createTaskHTML(task) {
        // Utiliser updated_at si disponible, sinon created_at
        const displayDate = task.updated_at || task.created_at;

        return `
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${task.title}</h6>
                    <div class="d-flex flex-column">
                        <small class="text-muted mb-1">
                            <span class="fw-bold">Projet:</span>
                            ${task.project_title}
                        </small>
                        <div class="d-flex align-items-center mb-1">
                            <span class="me-1 small text-muted">Statut:</span>
                            <span class="badge ${getStatusBadgeClass(task.status)} badge-sm">
                                ${task.status_label}
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="me-1 small text-muted">Priorité:</span>
                            <span class="badge ${getPriorityBadgeClass(task.priority)} badge-sm">
                                ${task.priority_label}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <small class="text-muted">${formatDate(displayDate)}</small>
                </div>
            </div>
        </div>
    `;
    }

    // Fonction pour mettre à jour la liste des tâches récentes
    async function updateRecentTasks() {
        if (!isDocumentVisible) return;

        try {
            const response = await fetchWithTimeout(config.recentTasksUrl, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            // Mettre à jour les deux versions (desktop et mobile)
            [recentTasksContainerDesktop, recentTasksContainerMobile].forEach(container => {
                if (!container) return;

                if (data.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-tasks fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Aucune tâche récente</p>
                        </div>
                    `;
                } else {
                    const listGroup = document.createElement('div');
                    listGroup.className = 'list-group list-group-flush';

                    data.forEach(task => {
                        listGroup.innerHTML += createTaskHTML(task);
                    });

                    container.innerHTML = '';
                    container.appendChild(listGroup);
                }
            });

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.warn('Erreur lors de la mise à jour des tâches récentes:', error.message);
            }
        }
    }

    // Gestion de la visibilité de la page
    function handleVisibilityChange() {
        isDocumentVisible = !document.hidden;

        if (isDocumentVisible) {
            if (!updateInterval) {
                updateRecentTasks();
                // Mise à jour toutes les 2 minutes
                updateInterval = setInterval(updateRecentTasks, 120000);
            }
        } else {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
            }
        }
    }

    // Nettoyage des ressources
    function cleanup() {
        if (activeController) {
            activeController.abort();
        }
        if (updateInterval) {
            clearInterval(updateInterval);
        }
    }

    // Initialisation
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('pagehide', cleanup);
    window.addEventListener('beforeunload', cleanup);

    // Démarrage
    if (isDocumentVisible) {
        updateInterval = setInterval(updateRecentTasks, 120000);
    }
}