<?php

namespace FOM\UserBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\DependencyInjection\Container;

class ACEDataTransformer implements DataTransformerInterface
{
    protected $container;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    /**
     * Transforms an single ACE to an object suitable for ACEType
     *
     * @param ace $ace
     * @return object
     */
    public function transform($ace)
    {
        $sid = null;
        $mask = null;

        $sidPrefix = '';
        $sidName = '';
        $permissions = array();

        if($ace instanceof Entry) {
            $sid = $ace->getSecurityIdentity();
            $mask = $ace->getMask();
        } elseif(is_array($ace)) {
            $sid = $ace['sid'];
            $mask = $ace['mask'];
        }

        $sidString = '';
        if($sid instanceof RoleSecurityIdentity) {
            $sidPrefix = 'r';
            $sidName = $sid->getRole();
            $sidString = sprintf('%s:%s', $sidPrefix, $sidName);
        } elseif($sid instanceof UserSecurityIdentity) {
            $sidPrefix = 'u';
            $sidName = $sid->getUsername();
            $sidClass = $sid->getClass();
            $sidString = sprintf('%s:%s:%s', $sidPrefix, $sidName, $sidClass);
        }

        for($i = 1; $i <= 30; $i++) {
                $key = 1 << ($i-1);
                if($mask & $key) {
                    $permissions[$i] = true;
                } else {
                    $permissions[$i] = false;
                }
        }

        return array(
            'sid' => $sidString,
            'permissions' => $permissions);
    }

    /**
     * Transforms an ACEType result into an ACE
     *
     * @param object $data
     * @return ace
     */
    public function reverseTransform($data)
    {
        $sidParts = explode(':', $data['sid']);
        if(strtoupper($sidParts[0]) == 'R') {
            $sid = new RoleSecurityIdentity($sidParts[1]);
        } else {
            if(3 == count($sidParts)) {
                $class = $sidParts[2];
            } else {
                if($this->isLdapUser($sidParts[1])) {
                  $class = 'FOM\UserBundle\Entity\LdapUser';
                } else {
                  $class = 'FOM\UserBundle\Entity\User';
                }
            }
            $sid = new UserSecurityIdentity($sidParts[1], $class);
        }

        $maskBuilder = new MaskBuilder();
        foreach($data['permissions'] as $bit => $permission) {
            if(true === $permission) {
                $maskBuilder->add(1 << ($bit - 1));
            }
        }

        return array(
            'sid' => $sid,
            'mask' => $maskBuilder->get());
    }
    public function isLdapUser($username)
    {
        if(!$this->container->hasParameter('ldap_host')) {
            return false;
        }

        $ldapHostname = $this->container->getParameter("ldap_host");
        $ldapPort = $this->container->getParameter("ldap_port");
        $ldapVersion = $this->container->getParameter("ldap_version");
        $baseDn = $this->container->getParameter("ldap_user_base_dn");
        $nameAttribute = $this->container->getParameter("ldap_user_name_attribute");
        $filter = "(" . $nameAttribute . "=*)";

        $connection = @ldap_connect($ldapHostname, $ldapPort);
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $ldapVersion);

        $ldapListRequest = ldap_list($connection, $baseDn, $filter); // or throw exeption('Unable to list. LdapError: ' . ldap_error($ldapConnection));

        $ldapUserList = ldap_get_entries($connection, $ldapListRequest);

        foreach($ldapUserList as $ldapUser) {
            if ($ldapUser[$nameAttribute][0] == $username) {
                return true;
            }
        }
        return false;
    }
}
