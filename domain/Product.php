<?php

/**
 * Class Product
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $createdAt
 * @property float $price
 * @property bool $available
 */
class Product extends PersistentModel
{
    public const TABLE = "product";
    // Keys that are not accepted from RequestBody
    private const EXCLUDED = [];

    /**
     * Product constructor.
     */
    public function __construct(array $columnValues = array())
    {
        parent::__construct(static::TABLE, [
            "id" => ["sql" => "NULL"],
            "createdAt" => ["sql" => "now()"],
            "name" => ["required" => true],
            "category_id" => ["required" => true],
            "price" => ["required" => true],
            "available" => ["required" => true]
        ], $columnValues);
    }


}