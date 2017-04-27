<?php

/**
 * Class Account
 *
 * @property  string $id
 * @property  string $createdAt
 * @property  string $name
 * @property  string $role
 * @property  string $password
 * @property  string $displayName
 */
class Account extends PersistentModel
{

    public const TABLE = "account";
    // Keys that are not accepted from RequestBody
    private const EXCLUDED = ["id", "createdAt", "role"];

    public static function fromRequestBody(array $body): Account
    {

        return new Account(array_filter($body, function ($key) {
            return empty(array_search($key, Account::EXCLUDED, true));
        }, ARRAY_FILTER_USE_KEY));
    }

    public function __construct(array $columnValues = array())
    {
        parent::__construct(self::TABLE, array(
            "id" => ["sql" => "NULL",],
            "createdAt" => ["sql" => "now()"],
            "name" => ["required" => true],
            "role" => ["required" => true],
            "password" => ["required" => true],
            "displayName" => ["required" => true]
        ), $columnValues);
    }
}