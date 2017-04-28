<?php

class Roles
{
    private $roleObservable;

    public function __construct()
    {
    }

    public static function from($token)
    {
        return new Role($token['role'], array());
    }

    /**
     * @param callable $roleObservable (Request) -> Role
     * @return Roles Builder
     */
    public function setRoleObservable($roleObservable)
    {
        $this->roleObservable = $roleObservable;
        return $this;
    }

    /**
     * Checks whether both role & permissions
     *
     * @param string $role
     * @param array $permissions
     * @return array
     */
    public function has(string $role, array $permissions)
    {
        return [new RoleMiddleware($this->roleObservable, $role, $permissions), "intercept"];
    }

    /**
     * Check whether logged in user has (role | access) to manage path
     *
     * @param string $role
     * @param callable $userObserver (Request) -> Array('role')
     * @return callable to check role
     */
    public function hasRole(string $role)
    {
        return [new RoleMiddleware($this->roleObservable, $role), "intercept"];
    }

    /**
     * Checks whether user has permissions
     *
     * @param array $permission Set of permissions
     *
     * @return array True if all permissions are granted to user
     */
    public function hasPermissions(array $permission)
    {
        return [new RoleMiddleware($this->roleObservable, null, $permission), "intercept"];
    }

}