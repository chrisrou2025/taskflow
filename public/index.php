<?php

use App\Kernel;

// Chargement de l'autoloader Composer avec gestion d'erreur
$autoloadPath = dirname(__DIR__) . '/vendor/autoload_runtime.php';

// Vérification de l'existence du fichier avant inclusion
if (!file_exists($autoloadPath)) {
    die('Erreur : Les dépendances Composer ne sont pas installées. Veuillez exécuter "composer install" dans le répertoire racine du projet.');
}

require_once $autoloadPath;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
