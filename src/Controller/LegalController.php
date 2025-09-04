<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/legal')]
class LegalController extends AbstractController
{
    #[Route('/terms', name: 'legal_terms')]
    public function terms(): Response
    {
        return $this->render('legal/terms.html.twig');
    }

    #[Route('/privacy', name: 'legal_privacy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }

    #[Route('/gdpr', name: 'legal_gdpr')]
    public function gdpr(): Response
    {
        return $this->render('legal/gdpr.html.twig');
    }

    #[Route('/cookies', name: 'legal_cookies')]
    public function cookies(): Response
    {
        return $this->render('legal/cookies.html.twig');
    }
}