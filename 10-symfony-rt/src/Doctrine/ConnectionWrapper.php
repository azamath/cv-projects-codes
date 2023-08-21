<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Doctrine;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

class ConnectionWrapper extends Connection
{
    /**
     * @inheritDoc
     */
    public function executeQuery(string $sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        // quote fields that are not quoted yet
        $sql = preg_replace('/(t\d+)\.([\w\n]+)/', '$1."$2"', $sql);

        return parent::executeQuery($sql, $params, $types, $qcp);
    }
}
