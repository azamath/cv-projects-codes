<?php

namespace App\Tests\Unit\Security;

use App\Security\App1Authenticator;
use App\Services\App1HttpService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class App1AuthenticatorTest extends TestCase
{
    public function testSupports(): void
    {
        $app1Authenticator = $this->getApp1Authenticator();

        $request = Request::create('/');
        $this->assertFalse($app1Authenticator->supports($request));

        $request = Request::create('/', 'GET', [], [App1Authenticator::SESSION_NAME => '']);
        $this->assertFalse($app1Authenticator->supports($request));

        $request = Request::create('/', 'GET', [], [App1Authenticator::SESSION_NAME => ' ']);
        $this->assertFalse($app1Authenticator->supports($request));

        $request = Request::create('/', 'GET', [], [App1Authenticator::SESSION_NAME => 'foo']);
        $this->assertTrue($app1Authenticator->supports($request));
    }

    public function testAuthenticateThrowsException()
    {
        $this->expectException(AuthenticationException::class);
        $request = Request::create('/');
        $this->getApp1Authenticator()->authenticate($request);
    }

    public function testAuthenticate()
    {
        $sessionId = 'foo';
        $request = Request::create('/', 'GET', [], [App1Authenticator::SESSION_NAME => $sessionId]);
        $passport = $this->getApp1Authenticator()->authenticate($request);
        $this->assertTrue($passport->hasBadge(UserBadge::class));
        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        $this->assertInstanceOf(UserBadge::class, $badge);
        $this->assertEquals($sessionId, $badge->getUserIdentifier());
        $this->assertIsCallable($badge->getUserLoader());
    }

    protected function getApp1Authenticator(): App1Authenticator
    {
        $app1HttpService = $this->createMock(App1HttpService::class);
        return new App1Authenticator($app1HttpService);
    }
}
