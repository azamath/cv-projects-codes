<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class App1Authenticator extends AbstractAuthenticator
{
    public const SESSION_NAME = 'PHPSESSID';

    public function __construct(private \App\Services\App1HttpService $app1HttpService)
    {
    }

    public function supports(Request $request): bool
    {
        $sessionId = $request->cookies->get(self::SESSION_NAME);
        return $this->checkSessionIdValue($sessionId);
    }

    public function authenticate(Request $request): Passport
    {
        $sessionId = $request->cookies->get(self::SESSION_NAME);
        if (!$this->checkSessionIdValue($sessionId)) {
            // The session id cookie was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('No PHPSESSID provided in cookies.');
        }

        $userBadge = new UserBadge($sessionId, function ($sessionId) {
            return $this->retrieveUserBySessionId($sessionId);
        });

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $errorMessage = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new JsonResponse(['error' => $errorMessage], Response::HTTP_UNAUTHORIZED);
    }

    protected function retrieveUserBySessionId(string $sessionId): User
    {
        try {
            $result = $this->app1HttpService->getCurrentUser($sessionId);
        } catch (\Exception $e) {
            throw new AuthenticationServiceException('', 0, $e);
        }

        if (!$result) {
            throw new CustomUserMessageAuthenticationException('Unauthorized.');
        }

        $user = new User();
        $user->setUserId((int)$result['userId']);
        $user->setUsername($result['username']);

        return $user;
    }

    protected function checkSessionIdValue($sessionId): bool
    {
        return '' !== trim((string)$sessionId);
    }
}
