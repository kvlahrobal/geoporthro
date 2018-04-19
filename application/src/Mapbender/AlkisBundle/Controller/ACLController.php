<?php
  namespace Mapbender\AlkisBundle\Controller;

  use FOM\UserBundle\Controller\ACLController as BaseACLController;
  use FOM\ManagerBundle\Configuration\Route;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
  use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

  class ACLController extends BaseACLController
  {
    /**
     * @Route("/acl/search/{slug}", name="fom_user_acl_search")
     * @Method({ "GET" })
     * @Template("MapbenderAlkisBundle:ACL:ldap-result.html.twig")
     */
    public function searchAction($slug) {
        $idProvider = $this->get('fom.identities.provider');
        $groups = $idProvider->getAllGroups();
        $users = array();//$idProvider->getAllUsers();
        
        //**//
        // Settings for LDAP
        $ldapHostname = $this->container->getParameter("ldap_host");
        $ldapPort = $this->container->getParameter("ldap_port");
        $ldapVersion = $this->container->getParameter("ldap_version");
        $baseDn = $this->container->getParameter("ldap_user_base_dn");
        $nameAttribute = $this->container->getParameter("ldap_user_name_attribute");
        $filter = "(" . $nameAttribute . "=*" . $slug . "*)";

        $connection = @ldap_connect($ldapHostname, $ldapPort);
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, $ldapVersion);

        $ldapListRequest = ldap_search($connection, $baseDn, $filter);

        if (!$ldapListRequest) {
            throw exeption('Unable to search in LDAP. LdapError: ' . ldap_error($ldapConnection));
        }
        $ldapUserList = ldap_get_entries($connection, $ldapListRequest);

        // Add Users from LDAP

        foreach($ldapUserList as $ldapUser) {
            if(gettype($ldapUser) === 'array') { // first entry is the number of results!
                $user = new \stdClass;
                $user->getUsername = $ldapUser[$nameAttribute][0];
                $users[] = $user;
            }
        }
        //**//
        //$users  = $idProvider->searchLdapUsers($slug);
        return array('groups' => $groups, 'users' => $users);
    }
    
    /**
     * Used for delivering index page to start ldap search
     * @Route("/acl/search/", name="fom_user_acl_search_index")
     * @Method({ "GET" })
     * @Template("MapbenderAlkisBundle:ACL:ldap-search-form.html.twig")
     */
    public function searchIndexAction() {
        return array();
    }
    
    /**
     * @Route("/acl/edit", name="fom_user_acl_edit")
     * @Method("GET")
     * @Template("MapbenderAlkisBundle:ACL:edit.html.twig")
     */
    public function editAction()
    {
      return parent::editAction();
    }
  }
?>