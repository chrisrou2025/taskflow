// Vérification de la disponibilité de la configuration
if (typeof window.NotificationConfig === 'undefined') {
    console.warn('Configuration des notifications non disponible');
}

document.addEventListener('DOMContentLoaded', function () {
    // Vérification de la présence de la configuration
    if (!window.NotificationConfig) {
        console.warn('NotificationConfig non définie, notifications désactivées');
        return;
    }

    // Éléments DOM
    const notificationCountEl = document.getElementById('notification-count');
    const notificationCountElDesktop = document.getElementById('notification-count-desktop');
    const notificationListElDesktop = document.getElementById('notification-list-desktop');
    const notificationModal = document.getElementById('notificationModal');
    const notificationModalBody = document.querySelector('#notificationModal .modal-body');

    // URLs des API depuis la configuration
    const unreadCountUrl = window.NotificationConfig.unreadCountUrl;
    const recentNotificationsUrl = window.NotificationConfig.recentNotificationsUrl;
    const notificationIndexUrl = window.NotificationConfig.notificationIndexUrl;

    // Variables pour gérer les intervalles et la visibilité
    let updateInterval = null;
    let isDocumentVisible = !document.hidden;

    // Fonction utilitaire : parse JSON avec gestion d'erreurs
    async function safeJson(response) {
        try {
            return await response.json();
        } catch (error) {
            console.warn("Réponse non JSON reçue :", error.message);
            return null;
        }
    }

    // Fonction utilitaire : gestion des erreurs réseau
    function handleNetworkError(error) {
        // Éviter le spam d'erreurs dans la console pour les erreurs courantes
        if (error.name !== 'AbortError' && !error.message.includes('NetworkError')) {
            console.warn('Erreur réseau notifications:', error.message);
        }
    }

    // Masquer les badges de notifications
    function hideNotificationBadges() {
        if (notificationCountEl) {
            notificationCountEl.style.display = 'none';
        }
        if (notificationCountElDesktop) {
            notificationCountElDesktop.style.display = 'none';
        }
    }

    // Mettre à jour les badges de notifications
    function updateNotificationBadges(count) {
        if (count > 0) {
            if (notificationCountEl) {
                notificationCountEl.innerText = count;
                notificationCountEl.style.display = 'flex';
            }
            if (notificationCountElDesktop) {
                notificationCountElDesktop.innerText = count;
                notificationCountElDesktop.style.display = 'flex';
            }
        } else {
            hideNotificationBadges();
        }
    }

    // Met à jour le compteur de notifications avec timeout et contrôle d'abandon
    const updateUnreadCount = () => {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 secondes

        fetch(unreadCountUrl, {
            headers: {
                'Accept': 'application/json'
            },
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return safeJson(response);
        })
        .then(data => {
            if (!data || typeof data.count === 'undefined') {
                hideNotificationBadges();
                return;
            }
            updateNotificationBadges(data.count);
        })
        .catch(handleNetworkError);
    };

    // Charge les notifications récentes avec timeout
    const loadRecentNotifications = (containerElement, isModal = false) => {
        containerElement.innerHTML = '<p class="text-center text-muted my-4">Chargement...</p>';
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000); // 8 secondes

        fetch(recentNotificationsUrl, {
            headers: {
                'Accept': 'application/json'
            },
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return safeJson(response);
        })
        .then(data => {
            containerElement.innerHTML = '';

            if (!data) {
                containerElement.innerHTML = '<p class="text-center text-warning my-4">Données temporairement indisponibles</p>';
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
                    item.className = 'list-group-item list-group-item-action d-flex align-items-start notification-item';
                    if (!notif.is_read) {
                        item.classList.add('unread');
                    }

                    // Sécurisation des données avec valeurs par défaut
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

            // Ajout du lien "Voir toutes" pour le dropdown desktop
            if (!isModal) {
                const divider = document.createElement('li');
                divider.innerHTML = '<hr class="dropdown-divider">';
                const linkContainer = document.createElement('li');
                const link = document.createElement('a');
                link.href = notificationIndexUrl;
                link.className = "dropdown-item text-center";
                link.textContent = "Voir toutes les notifications";
                linkContainer.appendChild(link);

                containerElement.appendChild(divider);
                containerElement.appendChild(linkContainer);
            }
        })
        .catch(error => {
            handleNetworkError(error);
            containerElement.innerHTML = '<p class="text-center text-warning my-4">Connexion temporairement indisponible</p>';
        });
    };

    // Gestion de la visibilité du document pour optimiser les performances
    function handleVisibilityChange() {
        isDocumentVisible = !document.hidden;
        
        if (isDocumentVisible) {
            // Reprendre les mises à jour quand l'onglet redevient visible
            if (!updateInterval) {
                updateUnreadCount(); // Mise à jour immédiate
                updateInterval = setInterval(updateUnreadCount, 120000); // 2 minutes
            }
        } else {
            // Arrêter les mises à jour quand l'onglet n'est plus visible
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
            }
        }
    }

    // Écouteurs d'événements
    document.addEventListener('visibilitychange', handleVisibilityChange);

    // Événement pour le modal mobile
    if (notificationModal) {
        notificationModal.addEventListener('show.bs.modal', function () {
            loadRecentNotifications(notificationModalBody, true);
        });
    }

    // Événement pour le dropdown desktop
    const notificationDropdownDesktop = document.getElementById('notificationDropdownDesktop');
    if (notificationDropdownDesktop) {
        notificationDropdownDesktop.addEventListener('show.bs.dropdown', function () {
            loadRecentNotifications(notificationListElDesktop, false);
        });
    }

    // Chargement initial des notifications
    updateUnreadCount();

    // Démarrer l'intervalle de mise à jour si le document est visible
    if (isDocumentVisible) {
        updateInterval = setInterval(updateUnreadCount, 120000); // 2 minutes
    }

    // Nettoyage des ressources à la fermeture de la page
    window.addEventListener('beforeunload', function() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    });
});