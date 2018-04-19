<?php

namespace FOM\UserBundle\Security\User;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * LDAP User Interface
 *
 * LDAP User classes must implement this interface.
 *
 * @author Christian Wygoda
 */
interface LdapUserInterface extends AdvancedUserInterface
{
    /**
     * Set LDAP search result data for user.
     * getUsername and other methods should return values from this data array
     *
     * @param array $userData ldap user search result
     * @param array $groupData ldap group search result
     */
    public function setLdapData(array $userData, array $groupData);
}
