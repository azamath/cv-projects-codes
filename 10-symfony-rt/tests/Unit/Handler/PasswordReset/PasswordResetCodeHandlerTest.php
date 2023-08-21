<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Unit\Handler\PasswordReset;

use App\Entity\User;
use App\Handler\PasswordReset\PasswordResetCodeHandler;
use App\Repository\PasswordResetRepository;
use App\Tests\Traits\MocksDoctrine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

class PasswordResetCodeHandlerTest extends TestCase
{
    use MocksDoctrine;

    protected const EXPIRATION = 300;

    public function testGenerate()
    {
        $handler = $this->createHandler();
        $user = $this->createUser();
        $code = $handler->generate($user);
        $this->assertNotEmpty($code->getCode());
        $this->assertNotEmpty($code->getCodeHash());
        $this->assertGreaterThan(0, $code->getExpiresIn());
    }

    public function testValidateCodeFalse1()
    {
        $handler = $this->createHandler();

        $user = $this->createUser();
        $code = $handler->generate($user);

        $this->getMockPasswordResetRepository()
            ->method('findLatestForUser')
            ->willReturn(null);
        $result = $handler->validateCode($user, $code->getCode());
        $this->assertFalse($result);
    }

    public function testValidateCodeFalse2()
    {
        $handler = $this->createHandler();

        $user = $this->createUser();
        $code = $handler->generate($user);

        $createdDate = (new \DateTime())->setTimestamp(time() - self::EXPIRATION);
        $this->getMockPasswordResetRepository()
            ->method('findLatestForUser')
            ->willReturn(
                (new \App\Entity\PasswordReset())
                    ->setUserId($user->getUserId())
                    ->setCode($code->getCodeHash())
                    ->setCreatedDate($createdDate)
            );
        $result = $handler->validateCode($user, $code->getCode());
        $this->assertFalse($result);
    }

    public function testValidateCodeFalse3()
    {
        $handler = $this->createHandler();

        $user = $this->createUser();
        $code = $handler->generate($user);

        $this->getMockPasswordResetRepository()
            ->method('findLatestForUser')
            ->willReturn(
                (new \App\Entity\PasswordReset())
                    ->setUserId($user->getUserId())
                    ->setCode($code->getCodeHash())
                    ->setCreatedDate(new \DateTime())
            );
        $result = $handler->validateCode($user, $code->getCode() . '1');
        $this->assertFalse($result);
    }

    public function testValidateCodeTrue()
    {
        $handler = $this->createHandler();

        $user = $this->createUser();
        $code = $handler->generate($user);

        $this->getMockPasswordResetRepository()
            ->method('findLatestForUser')
            ->willReturn(
                (new \App\Entity\PasswordReset())
                    ->setUserId($user->getUserId())
                    ->setCode($code->getCodeHash())
                    ->setCreatedDate(new \DateTime())
            );

        $result = $handler->validateCode($user, $code->getCode());
        $this->assertTrue($result);
    }

    protected function createHandler(): PasswordResetCodeHandler
    {
        $handler = new PasswordResetCodeHandler(
            new PasswordHasherFactory([
                'password_reset' => [
                    'algorithm' => 'bcrypt',
                ],
            ]),
            $this->getMockEntityManager(),
            $this->getMockPasswordResetRepository(),
            self::EXPIRATION,
        );

        return $handler;
    }

    protected function getMockPasswordResetRepository(): PasswordResetRepository|\PHPUnit\Framework\MockObject\MockObject
    {
        if (!isset($this->mockPasswordResetRepository)) {
            $this->mockPasswordResetRepository = $this->createMock(PasswordResetRepository::class);
        }
        return $this->mockPasswordResetRepository;
    }

    protected function createUser(): User
    {
        $user = new User();
        $user->setUserId(1);
        return $user;
    }
}
