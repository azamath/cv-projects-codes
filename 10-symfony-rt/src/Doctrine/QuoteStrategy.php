<?php


namespace App\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;

class QuoteStrategy extends DefaultQuoteStrategy
{

    /**
     * {@inheritdoc}
     */
    public function getColumnName($fieldName, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $platform->quoteIdentifier($class->fieldMappings[$fieldName]['columnName']);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform): string
    {
        $tableName = $class->table['name'];

        if (!empty($class->table['schema'])) {
            $tableName = $class->table['schema'] . '.' . $class->table['name'];

            if (!$platform->supportsSchemas() && $platform->canEmulateSchemas()) {
                $tableName = $class->table['schema'] . '__' . $class->table['name'];
            }
        }

        return $platform->quoteIdentifier($tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $platform->quoteIdentifier($definition['sequenceName']);
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $platform->quoteIdentifier($joinColumn['name']);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $platform->quoteIdentifier($joinColumn['referencedColumnName']);
    }

}
