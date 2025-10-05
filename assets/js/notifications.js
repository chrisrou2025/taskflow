export function initNotificationSystem(config) {

    if (!config || !config.unreadCountUrl) {
        console.warn('Configuration des notifications invalide, notifications désactivées.');
        return;
    }

    // Éléments DOM
    const notificationCountEl = document.getElementById('notification-count');
    const notificationCountElDesktop = document.getElementById('notification-count-desktop');
    const notificationListElDesktop = document.getElementById('notification-list-desktop');
    const notificationModal = document.getElementById('notificationModal');
    const notificationModalBody = document.getElementById('notification-list-mobile'); // AJOUT
    const notificationDropdownDesktop = document.getElementById('notificationDropdownDesktop');

    // URLs des API depuis la configuration passée en paramètre
    const unreadCountUrl = config.unreadCountUrl;
    const recentNotificationsUrl = config.recentNotificationsUrl;
    const notificationIndexUrl = config.notificationIndexUrl;

    // Variables pour gérer les intervalles et la visibilité
    let updateInterval = null;
    let isDocumentVisible = !document.hidden;
    let activeControllers = new Set();

    // Fonction utilitaire : fetch avec timeout amélioré
    async function fetchWithTimeout(url, options = {}, timeout = 5000) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        activeControllers.add(controller);

        try {
            const response = await fetch(url, { ...options, signal: controller.signal });
            clearTimeout(timeoutId);
            return response;
        } finally {
            activeControllers.delete(controller);
        }
    }

    // Fonction utilitaire : parse JSON avec gestion d'erreurs
    async function safeJson(response) {
        try {
            return await response.json();
        } catch (error) {
            console.warn("Réponse non JSON reçue");
            return null;
        }
    }

    // Fonction utilitaire : gestion des erreurs réseau améliorée
    function handleNetworkError(error) {
        if (error.name !== 'AbortError' && !error.message.includes('NetworkError')) {
            console.warn('Erreur notifications:', error.message);
        }
    }

    // Fonction de nettoyage des ressources
    function cleanup() {
        activeControllers.forEach(controller => controller.abort());
        activeControllers.clear();
        if (updateInterval) {
            clearInterval(updateInterval);
        }
    }

    // Masquer les badges de notifications
    function hideNotificationBadges() {
        if (notificationCountEl) notificationCountEl.style.display = 'none';
        if (notificationCountElDesktop) notificationCountElDesktop.style.display = 'none';
    }

    // Mettre à jour les badges de notifications
    function updateNotificationBadges(count) {
        const countText = count > 9 ? '9+' : count;
        if (count > 0) {
            if (notificationCountEl) {
                notificationCountEl.innerText = countText;
                notificationCountEl.style.display = 'flex';
            }
            if (notificationCountElDesktop) {
                notificationCountElDesktop.innerText = countText;
                notificationCountElDesktop.style.display = 'flex';
            }
        } else {
            hideNotificationBadges();
        }
    }

    // Met à jour le compteur de notifications
    const updateUnreadCount = async () => {
        if (!isDocumentVisible) return;
        try {
            const response = await fetchWithTimeout(unreadCountUrl, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await safeJson(response);
            if (!data || typeof data.count === 'undefined') {
                hideNotificationBadges();
                return;
            }
            updateNotificationBadges(data.count);
        } catch (error) {
            handleNetworkError(error);
        }
    };

    // Charge les notifications récentes
    const loadRecentNotifications = async (containerElement, isModal = false) => {
        if (!containerElement) return;
        containerElement.innerHTML = '<p class="text-center text-muted my-4">Chargement...</p>';
        try {
            const response = await fetchWithTimeout(recentNotificationsUrl, { headers: { 'Accept': 'application/json' } }, 8000);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await safeJson(response);
            containerElement.innerHTML = '';
            if (!data) {
                containerElement.innerHTML = '<p class="text-center text-warning my-4">Données indisponibles</p>';
                return;
            }
            if (data.length === 0) {
                containerElement.innerHTML = '<p class="text-center text-muted my-4">Aucune nouvelle notification</p>';
            } else {
                const listGroup = document.createElement('div');
                listGroup.className = isModal ? 'list-group list-group-flush' : 'list-group';
                data.forEach(notif => {
                    const item = document.createElement('a');
                    item.href = notif.read_url || "#";
                    item.className = `list-group-item list-group-item-action d-flex align-items-start ${!notif.is_read ? 'unread' : ''}`;
                    item.innerHTML = `
                        <i class="${notif.icon || 'fas fa-bell'} ${notif.color_class || 'text-primary'} mt-1 me-3"></i>
                        <div>
                            <div class="small">${notif.message || 'Notification'}</div>
                            <small class="text-muted">${notif.time_ago || 'À l\'instant'}</small>
                        </div>
                    `;
                    listGroup.appendChild(item);
                });
                containerElement.appendChild(listGroup);
            }
            if (!isModal) {
                containerElement.insertAdjacentHTML('beforeend', `<li><hr class="dropdown-divider"></li><li><a href="${notificationIndexUrl}" class="dropdown-item text-center">Voir toutes les notifications</a></li>`);
            }
        } catch (error) {
            handleNetworkError(error);
            containerElement.innerHTML = '<p class="text-center text-warning my-4">Connexion indisponible</p>';
        }
    };

    // Gestion de la visibilité de la page
    function handleVisibilityChange() {
        isDocumentVisible = !document.hidden;
        if (isDocumentVisible) {
            if (!updateInterval) {
                updateUnreadCount();
                updateInterval = setInterval(updateUnreadCount, 120000);
            }
        } else {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
            }
        }
    }

    // --- INITIALISATION ---

    // Écouteurs d'événements
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    // Modal mobile
    if (notificationModal && notificationModalBody) {
        notificationModal.addEventListener('show.bs.modal', () => loadRecentNotifications(notificationModalBody, true));
    }
    
    // Dropdown desktop
    if (notificationDropdownDesktop) {
        notificationDropdownDesktop.addEventListener('show.bs.dropdown', () => loadRecentNotifications(notificationListElDesktop, false));
    }

    // Démarrage initial
    updateUnreadCount();
    if (isDocumentVisible) {
        updateInterval = setInterval(updateUnreadCount, 120000);
    }

    // Nettoyage à la fermeture
    window.addEventListener('pagehide', cleanup);
    window.addEventListener('beforeunload', cleanup);
}