<?php

/**
 * Class Role - Describes ability of User
 */
class Role
{
    private $name;
    private $permissions = array();

    public const USER = "user";
    public const SYSTEM = "system";

    /**
     * Role constructor.
     *
     * @param array $permissions
     */
    public function __construct(string $name, array $permissions)
    {
        $this->name = $name;
        $this->permissions = $permissions;
    }

    public function is(string $name)
    {
        return $name === $this->name;
    }

    public function hasPermission(string $name): bool
    {
        return in_array($name, $this->permissions);
    }

    public function hasPermissions(array $required): bool
    {
        return count(array_intersect($required, $this->permissions)) === count($required);
    }
}