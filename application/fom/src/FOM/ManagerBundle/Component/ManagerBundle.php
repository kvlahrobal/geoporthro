<?php

namespace FOM\ManagerBundle\Component;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * ManagerBundle base class.
 *
 * This class is the base class for bundles implementing Manager functionality.
 *
 * @author Christian Wygoda
 */
class ManagerBundle extends Bundle
{
    /**
     * Getter for list of controllers to embed into Manager interface.
     *
     * The list must be an array of arrays, each giving the integer weight, name, route and array of route prefixes
     * to match against. See source for an example.
     *
     * return array(
     *      array(
     *          weight => 5,
     *          name => 'Users',
     *          route => 'fom_user_useranager_index',
     *          routes => array(
     *              'fom_user_usermanager',
     *              'fom_user_rolemanager'
     *          )
     *      )
     *  );
     *
     * @return array
     */
    public function getManagerControllers()
    {
        return array();
    }

    /**
     * Getter for all available roles a bundles defines.
     *
     * The list must be an array with:
     *     name: String, must start with ROLE_, e.g. ROLE_USER_ADMIN
     *     title: String, human readable, e.g. "Can administrate users"
     *
     * @return array roles
     */
    public function getRoles()
    {
        return array();
    }

    public function getACLClasses()
    {
        return array();
    }
}
