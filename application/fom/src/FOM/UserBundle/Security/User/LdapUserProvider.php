<?php

namespace FOM\UserBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Bridge\Monolog\Logger;
use Mapbender\Component\Ldap;

class LdapUserProvider implements UserProviderInterface
{
    protected $params;
    protected $logger;
    protected $ldap;

    public function __construct(array $fom_params, Logger $logger)
    {
        $this->params = $fom_params['ldap'];
        $this->logger = $logger;
    }

    protected function getLDAPConnection()
    {
        if(null === $this->ldap) {
            $this->ldap = new Ldap($this->params['host'],
                $this->params['port'],
                $this->params['version']);
        }
        return $this->ldap;
    }

    public function loadUserByUsername($username, $isDn=false)
    {
        $ldap = $this->getLDAPConnection();

        // Find remote user
        $base = $this->params['base_dn'];
        $filter = sprintf($this->params['filter'], $username);
        if($isDn) {
            $filter = sprintf($this->params['filter'], '*');
            $base = $username;
        }

        $result = $ldap->search($base, $filter);

        $this->logger->debug(sprintf('LDAP search with base dn "%s" and filter "%s" yielded: %s',
            $base, $filter, print_r($result, true)));

        if(false === $result || 1 !== $result['count']) {
            throw new UsernameNotFoundException(
                sprintf('No record found for user %s', $username));
        }

        $groupData = $this->getGroupData($result[0]);

        $user_class = $this->params['user_class'];
        $user = new $user_class();
        $user->setLdapData($result[0], $groupData);

        return $user;
    }

    /**
     * Get's group data for given user data
     * @param  array $userData User data as returned by LDAP query
     * @return array           Group data as returned by LDAP query
     */
    protected function getGroupData($userData)
    {
        if(!(array_key_exists('group_dn', $this->params)
            && array_key_exists('group_user_relation_attribute', $this->params)
            && array_key_exists('group_user_attribute', $this->params))) {
            return array();
        }

        $ldap = $this->getLDAPConnection();

        $base = $this->params['group_dn'];
        $filter = sprintf('(%s=%s)',
            $this->params['group_user_relation_attribute'],
            $userData[$this->params['group_user_attribute']][0]);

        $groups = $ldap->search($base, $filter);
        $this->logger->debug(sprintf('LDAP search with base dn "%s" and filter "%s" yielded: %s',
            $base, $filter, print_r($groups, true)));

        if($groups['count'] > 0) {
            $result = array();
            for($i = 0; $i < $groups['count']; $i++) {
                $result[] = $groups[$i];
            }
            return $result;
        }

        return array();
    }

    public function refreshUser(UserInterface $user)
    {
        return $this->loadUserByUsername($user->getUsername(), true);
    }

    public function supportsClass($class)
    {
        return $class === $this->params['user_class'];
    }
}
