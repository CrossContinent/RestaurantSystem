<?php

define("TAG", "DataSource");
Log::setLoggable(TAG, true);

define("MYSQL_DB", "mysql:host=localhost;dbname=restaurant");
define("MYSQL_USERNAME", "root");
define("MYSQL_PASSWORD", "root");


class DataSource
{
    /** @var PDO */
    private $database;

    private static function buildConditions($conditions): ?string
    {
        if (is_array($conditions)) {
            return self::buildSqlSequence($conditions, 'AND', function ($key) {
                return "{$key} = ?";
            });
        } else if (is_string($conditions)) {
            return $conditions;
        }
        return null;
    }

    private static function buildSqlSequence(array $array, $commaValue, $mergeFunc): string
    {
        $comma = '';
        $sql = '';
        foreach ($array as $key => $value) {
            $sql .= $comma . " " . call_user_func_array($mergeFunc, array($key, $value)) . " ";
            $comma = $commaValue;
        }
        return $sql;
    }

    /**
     * DataSource constructor.
     */
    public function __construct()
    {

        $this->database = new PDO(MYSQL_DB, MYSQL_USERNAME, MYSQL_PASSWORD);
        $this->database->setAttribute(PDO::ATTR_ERRMODE, true);
        $this->database->setAttribute(PDO::ERRMODE_EXCEPTION, true);
    }

    /**
     * @return PDO Connected instance to pdo
     */
    public function getDatabase(): PDO
    {
        return $this->database;
    }

    public function getLastInsertedId()
    {
        $stmt = $this->database->query("SELECT LAST_INSERT_ID()");
        try {
            return $lastId = $stmt->fetchColumn();
        } finally {
            $stmt->closeCursor();
        }
    }


    /**
     * @param PersistentModel $model
     * @return bool
     */
    public function insert(PersistentModel $model): bool
    {
        $data = $model->export();

        if (!isset($data["columns"], $data["placeholders"], $data["values"])) {
            throw new RuntimeException("Exported data set is malformed");
        }

        $statement = $this->database
            ->prepare("INSERT INTO restaurant.account({$data['columns']}) VALUE({$data['placeholders']})");

        Log::debug(TAG, "SQL Explain: $statement->queryString\n"
            . join(',', $data['values']));

        return $statement->execute($data["values"]);
    }

    public function update(PersistentModel $model, mixed $conditions): bool
    {
        $data = $model->export();

        if (!isset($data["columns"], $data["placeholders"], $data["values"])) {
            throw new RuntimeException("Exported data set is malformed");
        }

        $conditionsSql = self::buildConditions($conditions) ?? "1";

        $comma = '';
        $setPlaceholdersSql = "";
        foreach (array_combine($data['columns'], $data['placeholders']) as $key => $value) {
            $setPlaceholdersSql .= $comma . " {$key}={$value}";
            $comma = ',';
        }

        $statement = $this->database
            ->prepare("UPDATE restaurant.account SET {$setPlaceholdersSql} WHERE {$conditionsSql}");

        Log::debug(TAG, "SQL Explain: $statement->queryString\n"
            . join(',', $data['values']));

        return $statement->execute(array_merge($data["values"], array_values($conditions)));
    }

    /**
     * @param string $tableName
     * @param array $condition
     * @param array $columns
     * @param callable $createMapping
     * @return array|bool
     * @internal param null|string $class
     */
    public function fetchAll(string $tableName,
                             $condition = array(),
                             $columns = array(),
                             callable $createMapping)
    {

        if (count($columns) > 0) {
            $columns = self::buildSqlSequence($columns, ",", function ($key, $value) {
                return $value;
            });
        } else {
            $columns = "*";
        }

        $conditionSql = self::buildConditions($condition);

        $statement = $this->database
            ->prepare("SELECT {$columns} FROM {$tableName} WHERE {$conditionSql}");

        Log::debug(TAG, "SQL Explain: $statement->queryString, values: " .
            join(', ', array_values($condition)));

        if (!$statement->execute(array_values($condition))) {
            Log::error(TAG, $statement->errorInfo(), null);
            return array();
        }

        return $statement->fetchAll(PDO::FETCH_FUNC, $createMapping);
    }
}