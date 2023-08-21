<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Tests\Traits;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

/**
 * Helper functions related for loading fixtures in tests.
 * @link https://github.com/liip/LiipTestFixturesBundle
 */
trait LoadsFixtures
{
    protected \Doctrine\Common\DataFixtures\ReferenceRepository $_fixtureReferences;

    /**
     * Shorthand method for loading fixtures.
     * @link https://github.com/liip/LiipTestFixturesBundle/blob/2.x/doc/database.md#methods
     * @param array $classNames
     * @param bool $append
     * @return AbstractExecutor
     */
    protected function loadFixtures(array $classNames, bool $append = false): AbstractExecutor
    {
        $executor = $this->getDatabaseTool()->loadFixtures($classNames, $append);
        $this->_fixtureReferences = $executor->getReferenceRepository();

        return $executor;
    }

    protected function getEntityReference(string $name)
    {
        if (!isset($this->_fixtureReferences)) {
            throw new \RuntimeException('Trying to get entity reference before loading any fixtures');
        }

        return $this->_fixtureReferences->getReference($name);
    }

    protected function getDatabaseTool(): AbstractDatabaseTool
    {
        return static::getContainer()->get(DatabaseToolCollection::class)->get();
    }
}
