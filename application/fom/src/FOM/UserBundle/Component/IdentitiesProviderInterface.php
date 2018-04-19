<?php

namespace FOM\UserBundle\Component;

interface IdentitiesProviderInterface
{
    /**
     * Get user security identifiers for given query.
     *
     * @param  string $search Query string
     * @return array         Array of user security identifiers
     */
    public function getUsers($search);


    /**
     * Get role identifiers
     *
     * @return array Array of role security identifiers
     */
    public function getRoles();

    /**
     * Get all group objects
     * @return array Array of group objects
     */
    public function getAllGroups();

    /**
     * Get all user objects
     * @return array Array of user objects
     */
    public function getAllUsers();
}
