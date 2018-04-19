<?php
namespace FOM\CoreBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DoctrineHelper
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 */
class DoctrineHelper
{
    /**
     * Check and create table if not exists
     *
     * @param ContainerInterface $container ContainerInterface Container
     * @param                    $className String  Name of the ORM class
     * @param bool               $force     bool    Force update table
     */
    public static function checkAndCreateTableByEntityName(ContainerInterface $container, $className, $force = false)
    {
        /** @var Registry $doctrine */
        /** @var Connection $connection */
        /** @var ClassMetadata $classMetadata */
        $doctrine      = $container->get('doctrine');
        $manager       = $doctrine->getManager();
        $schemaTool    = new SchemaTool($doctrine->getManager());
        $connection    = $doctrine->getConnection();
        $schemaManager = $connection->getSchemaManager();
        $classMetadata = $manager->getClassMetadata($className);
        if ($force || !$schemaManager->tablesExist($classMetadata->getTableName())) {
            $schemaTool->updateSchema(array($classMetadata), true);
        }
    }
}