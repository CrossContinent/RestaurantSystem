<?php

class Category extends PersistentModel
{
    public const TABLE = "category";
    public const EXCLUDED = [
        "id", "createdAt", "available"
    ];

    /**
     * Product constructor.
     */
    public function __construct(array $columnValues = array())
    {
        parent::__construct(static::TABLE, [
            "id" => ["sql" => "NULL"],
            "createdAt" => ["sql" => "now()"],
            "name" => ["required" => true],
            "available" => ["required" => true]
        ], $columnValues);
    }
}