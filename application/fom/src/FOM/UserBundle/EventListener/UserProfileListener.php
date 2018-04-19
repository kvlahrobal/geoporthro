<?php

namespace FOM\UserBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;

/**
 * Event listener for adding user profile on the fly
 *
 * @author Christian Wygoda
 */
class UserProfileListener implements EventSubscriber
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata'
        );
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $metadata = $args->getClassMetadata();
        $user = 'FOM\UserBundle\Entity\User';
        $basicProfile = 'FOM\UserBundle\Entity\BasicProfile';
        $profile = $this->container->getParameter('fom_user.profile_entity');

        if ($user == $metadata->getName()) {
            $metadata->mapOneToOne(array(
                'fieldName' => 'profile',
                'targetEntity' => $profile,
                'mappedBy' => 'uid',
                'cascade' => array('persist'),
            ));
        }
        $connection = $args->getEntityManager()->getConnection();
        $platform = $connection->getDatabasePlatform();
        $uidColname = $connection->quoteIdentifier('uid');
        if ($platform instanceof OraclePlatform) {
            $uidColname = strtoupper($uidColname);
        } elseif ($platform instanceof MySqlPlatform) {
            $uidColname = 'uid';
        }

        // need to add metadata for the basic profile, else doctrine
        // will whine in many situations
        if($profile == $metadata->getName() || $basicProfile == $metadata->getName()) {
            $metadata->setIdentifier(array('uid'));
            $metadata->setIdGenerator(new AssignedGenerator());
            $metadata->mapOneToOne(array(
                'fieldName' => 'uid',
                'targetEntity' => $user,
                'inversedBy' => 'profile',
                'id' => true,
                'joinColumns' => array(array(
                    'name' => $uidColname,
                    'referencedColumnName' => 'id'))
            ));
        }
    }
}
