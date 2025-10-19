<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ajoute des headers de cache HTTP pour optimiser les performances
 */
class CacheHeaderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Ne traiter que la requête principale
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Assets statiques : cache longue durée
        if (preg_match('#^/(css|js|images|fonts|build|bundles)/#', $request->getPathInfo())) {
            $response->setPublic();
            $response->setMaxAge(31536000); // 1 an
            $response->setSharedMaxAge(31536000);
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            
            // Immutable : le fichier ne changera jamais
            $response->headers->addCacheControlDirective('immutable');
            
            return;
        }

        // Pages dynamiques authentifiées : pas de cache
        if ($this->isAuthenticatedRoute($request->getPathInfo())) {
            $response->setPrivate();
            $response->setMaxAge(0);
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            
            return;
        }

        // Pages publiques : cache court
        if ($this->isPublicRoute($request->getPathInfo())) {
            $response->setPublic();
            $response->setMaxAge(300); // 5 minutes
            $response->setSharedMaxAge(600); // 10 minutes pour CDN
        }
    }

    private function isAuthenticatedRoute(string $path): bool
    {
        $authenticatedPaths = [
            '/dashboard',
            '/profile',
            '/projects',
            '/tasks',
            '/admin',
            '/collaboration',
            '/notifications',
        ];

        foreach ($authenticatedPaths as $authPath) {
            if (str_starts_with($path, $authPath)) {
                return true;
            }
        }

        return false;
    }

    private function isPublicRoute(string $path): bool
    {
        return $path === '/' || 
               str_starts_with($path, '/login') || 
               str_starts_with($path, '/register');
    }
}