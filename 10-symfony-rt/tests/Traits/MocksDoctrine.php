<?php

namespace App\Tests\Traits;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;

trait MocksDoctrine
{
    private MockObject|ObjectManager $mockEntityManager;
    private ManagerRegistry|MockObject $mockDoctrine;

    protected function getMockDoctrine(): ManagerRegistry|MockObject
    {
        if (!isset($this->mockDoctrine)) {
            $this->mockDoctrine = $this->createMock(ManagerRegistry::class);
            $this->mockDoctrine->expects($this->any())
                ->method('getManager')
                ->willReturn($this->getMockObjectManager());
        }

        return $this->mockDoctrine;
    }

    protected function getMockEntityManager(): EntityManagerInterface|MockObject
    {
        if (!isset($this->mockEntityManager)) {
            $this->mockEntityManager = $this->createMock(EntityManager::class);
        }

        return $this->mockEntityManager;
    }

    /**
     * @deprecated use $this->getMockEntityManager()
     * @use static::getMockEntityManager
     */
    protected function getMockObjectManager(): EntityManagerInterface|MockObject
    {
        return $this->getMockEntityManager();
    }

}
