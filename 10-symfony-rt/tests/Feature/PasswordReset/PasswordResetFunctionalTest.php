<?php

namespace App\Tests\Feature\PasswordReset;

use App\Factory\UserFactory;
use App\Handler\PasswordReset\PasswordResetCodeHandler;
use App\Handler\PasswordReset\PasswordResetHandler;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PasswordResetFunctionalTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
    }

    public function testResetUpdatesPassword(): void
    {
        $user = UserFactory::new()->create();
        $passwordBefore = $user->getPassword();

        $resetCode = $this->getHandler()->forgot($user->getEmail());
        $this->getHandler()->reset($user->getEmail(), $resetCode->getCode(), 'new password');

        /** @var UserRepository $userRepo */
        $userRepo = static::getContainer()->get(UserRepository::class);
        $passwordAfter = $userRepo->findOneByEmail($user->getEmail())->getPassword();
        $this->assertNotSame($passwordAfter, $passwordBefore);
    }

    public function testResetCodeExpiration()
    {
        $handler = $this->getHandler();
        $codeHandler = $this->getCodeHandler();
        $user = UserFactory::new()->create();

        $resetCode = $handler->forgot($user->getEmail());

        // set a fake current timestamp to imitate 6 minutes pass (5 minutes is configured)
        $codeHandler->setTestCurrentTime(time() + 60 * 6);

        $this->expectException(\App\Handler\PasswordReset\PasswordResetException::class);
        $this->expectExceptionCode(\App\Handler\PasswordReset\PasswordResetException::CODE_INVALID);
        $handler->reset($user->getEmail(), $resetCode->getCode(), 'new password');
    }

    public function testResetCodeUsedOnce()
    {
        $user = UserFactory::new()->create();

        $resetCode = $this->getHandler()->forgot($user->getEmail());
        $this->getHandler()->reset($user->getEmail(), $resetCode->getCode(), 'new password');

        // second time it should fail
        $this->expectException(\App\Handler\PasswordReset\PasswordResetException::class);
        $this->expectExceptionCode(\App\Handler\PasswordReset\PasswordResetException::CODE_INVALID);
        $this->getHandler()->reset($user->getEmail(), $resetCode->getCode(), 'new password 2');
    }

    protected function getHandler(): PasswordResetHandler
    {
        /** @var PasswordResetHandler $handler */
        $handler = static::getContainer()->get(PasswordResetHandler::class);
        return $handler;
    }

    protected function getCodeHandler(): PasswordResetCodeHandler
    {
        /** @var PasswordResetCodeHandler $handler */
        $handler = static::getContainer()->get(PasswordResetCodeHandler::class);
        return $handler;
    }
}
