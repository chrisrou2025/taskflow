// Import des styles globaux
import './styles/app.css';

/**
 * Le point d'entrée principal de l'application.
 * Ce script est exécuté sur toutes les pages.
 */
document.addEventListener('DOMContentLoaded', () => {

    console.log('App.js chargé via AssetMapper');

    // --- GESTION DES NOTIFICATIONS ---
    // On vérifie si l'élément qui contient la configuration des notifications existe
    const notificationConfigElement = document.getElementById('notification-config');
    if (notificationConfigElement) {
        // On récupère et on parse la configuration depuis les attributs data-*
        const config = JSON.parse(notificationConfigElement.dataset.config);
        
        // On importe dynamiquement le module des notifications SEULEMENT si nécessaire
        import('./js/notifications.js').then(({ initNotificationSystem }) => {
            initNotificationSystem(config);
        }).catch(error => console.error("Erreur lors du chargement du module de notifications:", error));
    }

    // --- GESTION DU DASHBOARD ---
    // On vérifie si on est sur la page du dashboard
    const dashboardConfigElement = document.getElementById('dashboard-config');
    if (dashboardConfigElement) {
        // On récupère et on parse la configuration depuis les attributs data-*
        const config = JSON.parse(dashboardConfigElement.dataset.config);
        
        // On importe dynamiquement le module du dashboard SEULEMENT si nécessaire
        import('./js/dashboard.js').then(({ initDashboard }) => {
            initDashboard(config);
        }).catch(error => console.error("Erreur lors du chargement du module dashboard:", error));
    }

    // --- GESTION DES FORMULAIRES D'AUTHENTIFICATION ---
    // On vérifie si on est sur une page d'authentification (connexion, inscription, etc.)
    if (document.querySelector('.auth-container')) {
        // On importe dynamiquement le module d'authentification SEULEMENT sur ces pages
        import('./js/auth.js').then(({ initAuthPage }) => {
            initAuthPage();
        }).catch(error => console.error("Erreur lors du chargement du module d'authentification:", error));
    }

});