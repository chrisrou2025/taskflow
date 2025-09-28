document.addEventListener('DOMContentLoaded', function () {
    // Système de filtres harmonisé
    const filterButtons = document.querySelectorAll('.filter-btn');
    const notificationItems = document.querySelectorAll('.notification-item');

    // Gestion des filtres
    filterButtons.forEach(button => {
        button.addEventListener('click', function () {
            const filter = this.dataset.filter;

            // Mettre à jour les boutons actifs
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Filtrer les notifications
            notificationItems.forEach(item => {
                const type = item.dataset.type;
                const isRead = item.dataset.read === 'true';

                let show = false;

                switch (filter) {
                    case 'all': 
                        show = true;
                        break;
                    case 'unread': 
                        show = !isRead;
                        break;
                    default: 
                        show = type === filter;
                        break;
                }

                // Utiliser la bonne valeur de display selon le contexte
                if (item.tagName === 'TR') {
                    // Pour les lignes de tableau
                    item.style.display = show ? 'table-row' : 'none';
                } else {
                    // Pour les cartes mobiles
                    item.style.display = show ? 'block' : 'none';
                }
            });
        });
    });

    // Animation d'apparition des notifications harmonisée
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';
                entry.target.style.transition = 'opacity 0.3s, transform 0.3s';

                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, entry.target.dataset.index * 30);
            }
        });
    });

    // Appliquer l'observation aux éléments de notification
    notificationItems.forEach((item, index) => {
        item.dataset.index = index;
        observer.observe(item);
    });
});