<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Traits;

use App\Security\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Security;

trait MocksSecurity
{

    protected function getMockSecurity(): MockObject|Security
    {
        if (!isset($this->mockSecurity)) {
            $this->mockSecurity = $this->createMock(Security::class);
        }

        return $this->mockSecurity;
    }

    protected function getAuthUser(): User
    {
        if (!isset($this->authUser)) {
            $this->authUser = (new User())
                ->setUserId(1)
                ->setUsername('test');
        }

        return $this->authUser;
    }

    protected function letsHaveAuthUser(): static
    {
        $this->getMockSecurity()->expects($this->any())
            ->method('getUser')
            ->willReturn($this->getAuthUser());
        return $this;
    }
}
