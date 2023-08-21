<?php

namespace App\Controller;

use App\Handler\PasswordReset\PasswordResetException;
use App\Handler\PasswordReset\PasswordResetHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PasswordResetController extends AbstractController
{
    public function __construct(private PasswordResetHandler $handler)
    {
    }

    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgot(Request $request): Response
    {
        try {
            $resetCode = $this->handler->forgot($request->get('email'));
        } catch (PasswordResetException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        return $this->json([
            'message' => 'Code was send',
            'expires_in' => $resetCode->getExpiresIn(),
        ]);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function reset(Request $request): Response
    {
        try {
            $this->handler->reset(
                $request->get('email'),
                $request->get('code'),
                $request->get('password'),
            );
        } catch (PasswordResetException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        return $this->json([
            'message' => 'Password was reset',
        ]);
    }
}
