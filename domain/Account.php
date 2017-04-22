<?php

/**
 * Class Account
 *
 * @property  string $id
 * @property  string $createdAt
 * @property  string $name
 * @property  string $role
 * @property  string $password
 */
class Account extends PersistentModel
{

    public const TABLE = "account";

    public function __construct(array $columnValues = array())
    {
        parent::__construct(self::TABLE, array(
            "id" => ["sql" => "NULL",],
            "createdAt" => ["sql" => "now()"],
            "name" => ["required" => true],
            "role" => ["required" => true],
            "password" => ["required" => true]
        ), $columnValues);
    }
}