<?php

namespace FOM\UserBundle\Security;

use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

/**
 * Helper for user related stuff
 *
 * @author Christian Wygoda
 */
class UserHelper
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Set salt, encrypt password and set it on the user object
     *
     * @param User $user User object to manipulate
     * @param string $password Password to encrypt and store
     */
    public function setPassword(User $user, $password)
    {
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user);

        $salt = $this->createSalt();

        $encryptedPassword = $encoder->encodePassword($password, $salt);

        $user
            ->setPassword($encryptedPassword)
            ->setSalt($salt);
    }

    /**
     * Generate a salt for storing the encrypted password
     *
     * Taken from http://code.activestate.com/recipes/576894-generate-a-salt/
     *
     * @param int $max Length of salt
     * @return string
     */
    private function createSalt($max = 15)
    {
        $characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $i = 0;
        $salt = "";
        do {
            $salt .= $characterList{mt_rand(0,strlen($characterList)-1)};
            $i++;
        } while ($i < $max);

        return $salt;
    }

    /**
     * Gives a user the right to edit himself.
     */
    public function giveOwnRights($user) {
        $aclProvider = $this->container->get('security.acl.provider');
        $maskBuilder = new MaskBuilder();

        $usid = UserSecurityIdentity::fromAccount($user);
        $uoid = ObjectIdentity::fromDomainObject($user);
        foreach($this->container->getParameter("fom_user.user_own_permissions") as $permission) {
            $maskBuilder->add($permission);
        }
        $umask = $maskBuilder->get();

        try {
            $acl = $aclProvider->findAcl($uoid);
        } catch(\Exception $e) {
            $acl = $aclProvider->createAcl($uoid);
        }
        $acl->insertObjectAce($usid, $umask);
        $aclProvider->updateAcl($acl);
    }

}
