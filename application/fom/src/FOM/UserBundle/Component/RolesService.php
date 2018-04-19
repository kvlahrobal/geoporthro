<?php

namespace FOM\UserBundle\Component;

use FOM\ManagerBundle\Component\ManagerBundle;

class RolesService
{
    protected $kernel;
    protected $roles;

    public function __construct(\AppKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    protected function loadRoles()
    {
        $this->roles = array();
        foreach($this->kernel->getBundles() as $bundle) {
            if(is_subclass_of($bundle, 'FOM\ManagerBundle\Component\ManagerBundle')) {
                $bundle_roles = $bundle->getRoles();
                $this->roles = array_merge($this->roles, $bundle_roles);
            }
        }
    }

    public function getAll()
    {
        if($this->roles === null) {
            $this->loadRoles();
        }

        return $this->roles;
    }
}