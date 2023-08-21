<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthcheckController extends AbstractController
{
    #[Route('/healthcheck', name: 'healthcheck')]
    public function index(): Response
    {
        return $this->json([
            'message' => 'Works!',
            'context' => getenv('DD_CONTEXT'),
        ]);
    }
}
