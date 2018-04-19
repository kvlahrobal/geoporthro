<?php

namespace FOM\CoreBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Totally fake Doctrine Geometry type. Just so we can use doctrine:schema:update
 */
class FakeGeometryType extends Type
{
    public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'select 1';
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return '';
    }
    
    public function getName()
    {
        return 'geometry';
    }
    
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return '1';
    }
    
    public function canRequireSQLConversion()
    {
        return true;
    }
    
    public function convertToPHPValueSQL($sqlExpr, $platform)
    {
        return '';
    }
    
    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform)
    {
        return $sqlExpr;
    }
}
