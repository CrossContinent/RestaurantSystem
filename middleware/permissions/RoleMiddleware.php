<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 4/27/17
 * Time: 3:44 PM
 */
class RoleMiddleware
{
    private $roleObservable;

    private $requiredRole;
    private $requiredPermissions = array();

    /**
     * RoleMiddleware constructor.
     *
     * @param $roleObservable callable
     * @param $role string
     * @param $permissions array
     */
    function __construct(callable $roleObservable, ?string $role = null, ?array $permissions = null)
    {
        $this->requiredRole = $role;
        $this->roleObservable = $roleObservable;
        $this->requiredPermissions = $permissions;
    }

    public function intercept(Request $request, Response $response, Chain $chain)
    {
        /** @var Role */
        $role = call_user_func($this->roleObservable, $request);

        if ($role == null) {
            throw new Exception("Role not specified in HTTP Request");
        } else if (!($role instanceof Role)) {
            throw new Exception("Role object expected from RoleObservable");
        }

        if (empty($this->requiredRole) || !$role->is($this->requiredRole)) {
            throw new SecurityException("Permission denied");
        }

        if (!empty($this->requiredPermissions) && !$role->hasPermissions($this->requiredPermissions)) {
            throw new SecurityException("Permission denied");
        }

        $request->setVariable("role", $role);

        $chain->proceed($request, $response);
    }
}