<?php

namespace FOM\UserBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Mapbender\Component\Ldap;

/**
 * Description of LdapProvider
 *
 * @author arp
 */
class LdapProvider implements AuthenticationProviderInterface
{
    protected $userProvider;
    protected $ldapConfiguration;
    
    public function __construct(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;
        //$ldapConfiguration = $container->getParameter('fom');
        //$this->ldapConfiguration = $ldapConfiguration['ldap'];
    }

    public function authenticate(TokenInterface $token)
    {
        $user = $this->userProvider->loadUserByUsername($token->getUsername());
        die("XXX");
        if($user && $this->validateLdapUser($user)) {
            $token->setUser($user);
            return $token;
        }
        
        throw new AuthenticationException('The LDAP authentication failed.');
    }
    
    protected function validateLdapUser($user)
    {
        $ldap = new Ldap($this->ldapConfiguration['host'],
            $this->ldapConfiguration['port'],
            $this->ldapConfiguration['version']);
        
        if(!$ldap) {
            throw new \RuntimeException('LDAP configuration error: ' . $ldap->lastError());
        }
        
        $dn = 'xxx';
        $password = 'xxx';
        if(!$ldap->bind($dn, $password)) {
            throw new AuthenticationException('The LDAP authentication failed.');
        }
        
        return true;
    }
    
    /**
     * Checks whether this authentication provider supports the given token.
     * 
     * Returns true for now, but actually this should check if the token
     * belongs to a LDAP user object.
     * 
     * @param TokenInterface $token
     * @return boolean
     */
    public function supports(TokenInterface $token)
    {
        return true;
    }
}
