<?php

/**
 * Class ModelRouter
 */
abstract class ModelRouter extends BaseRouter
{

    /**
     * @var string Class representing Model that extends PersistentModel
     */
    private $modelClass;

    /**
     * ModelRouter constructor.
     *
     * @param string $modelClass
     */
    public function __construct($modelClass)
    {
        Log::debug("DEBUG", class_parents($modelClass));
        if (!in_array("PersistentModel", class_parents($modelClass))) {
            throw new InvalidArgumentException("Class must be extended from PersistentModel");
        }

        $this->modelClass = $modelClass;
    }

    /**
     * Creates model using reflection.
     * Can be overridden
     *
     * @param string $modelClass
     * @param array $body Request body
     * @return object|PersistentModel instance of <a href='psi_element://#modelClass'>#modelClass</a>
     */
    protected function createModel(string $modelClass, array $body): PersistentModel
    {
        return call_user_func_array("PersistentModel::create",
            [$modelClass, array_keys($body), array_values($body)]);
    }

    /**
     * [
     * "table" => "product",
     * "fields" => "product.*",
     * "joins" => [
     * [
     * "join" => "left",
     * "table" => "category",
     * "fields" => "category.name as categoryName",
     * "on" => ["categories.id" => "product.categoryId"]
     * ]
     * ],
     * "filter" => [
     *
     * ]
     * ]
     *
     * @param array $definition
     * @return array
     */
    protected function queryModels(array $definition): array
    {
        $fields = $definition['fields'];
        $joins = $definition['joins'];
        $filter = $definition['filter'];

        $fieldsSql = '';
        $joinsSql = '';
        $whereSql = '';
        $values = [];

        if (is_array($fields)) {
            $fieldsSql .= array_reduce($fields, function ($carry, $item) {
                return $carry .= ($carry === '' ? ' ' : ', ') . $item;
            }, '');
        } else {
            $fieldsSql .= $fields;
        }

        if (!empty($joins)) {
            foreach ($joins as $joinDef) {
                $joinsSql .= array_key_exists("join", $joinDef) ? $joinDef["join"] : '';
                $joinsSql .= " JOIN ON ";

                $first = true;
                foreach ($joinDef['on'] as $key => $value) {
                    $joinsSql .= ($first ? ' ' : ', ') . "{$key} = {$value}";
                    $first = false;
                }
            }
        }

        if (!empty($filter)) {
            $first = true;
            $whereSql .= "WHERE ";
            foreach ($filter as $conditionName => $condition) {
                $whereSql .= ($first ? ' ' : ', ') . $conditionName;
                foreach ($condition as $key => $value) {
                    $whereSql .= ($first ? ' ' : ', ') . "{$key} = ?";
                    array_push($value, $values);
                }
                $first = false;
            }
        }

        $sqlQuery = "SELECT {$fieldsSql} FROM {$definition['table']} ";
        $sqlQuery .= "{$joinsSql} {$whereSql}";

        $source = new DataSource();
        $database = $source->getDatabase();

        $statement = $database->prepare($sqlQuery);
        if ($statement->execute($values)) {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    }

    public function create(Request $request, Response $response, Chain $chain)
    {
        $model = $this->createModel($this->modelClass, $request->body());

        $source = new DataSource();

        if ($source->insert($model)) {
            $response->status(201)->json(['status' => 'ok']);
        } else {
            $response->status(500)->json(['status' => 'failed']);
        }

        $chain->proceed($request, $response);
    }

    public function query(Request $request, Response $response, Chain $chain)
    {
        $class = new ReflectionClass($this->modelClass);
        $tableName = $class->getConstant("TABLE");

        $result = $this->queryModels([
            "table" => $tableName,
            "fields" => "*",
        ]);

        $response->status(200)->json([
            "status" => "ok",
            "result" => $result
        ]);

        $chain->proceed($request, $response);
    }
}