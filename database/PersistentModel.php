<?php

class PersistentModel
{
    /** @var array */
    private $columns;
    /** @var array */
    private $changed;
    /** @var string */
    private $tableName;
    /** @var array */
    private $columnsDefinition;

    public static function create(string $className, array $columns, array $arguments)
    {
        $combined = array_combine($columns, $arguments);
        if (!$combined) {
            throw new RuntimeException("columns(" . count($columns) . ") != arguments(" . count($columns) . ")");
        }
        $reflection = new ReflectionClass($className);
        return $reflection->newInstance($combined);
    }

    public static function createFromRequestBody(string $className, array $body): PersistentModel
    {
        $class = new ReflectionClass($className);
        $excluded = $class->getConstant("EXCLUDED");

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $class->newInstance(array_filter($body, function ($key) use ($excluded) {
            return empty(array_search($key, $excluded, true));
        }, ARRAY_FILTER_USE_KEY));
    }

    /**
     * PersistentModel constructor.
     * @param string $tableName Table name
     * @param array $columns
     * @param array $columnsDefinition
     * @internal param $columns
     */
    protected function __construct(string $tableName,
                                   array $columnsDefinition = array(),
                                   array $columns = array())
    {
        if (count($columnsDefinition) === 0) {
            throw new InvalidArgumentException("Columns must be defined");

        }

        $this->tableName = $tableName;
        $this->columnsDefinition = $columnsDefinition;
        $this->columns = array();
        $this->changed = array();

        if (count($columns) > 0) {
            foreach (array_keys($columnsDefinition) as $key) {
                $this->columns[$key] = $columns[$key] ??  null;
            }
        }
    }

    function __isset($name)
    {
        return isset($this->columnsDefinition[$name]);
    }

    function __get($name)
    {
        if (isset($this->columnsDefinition[$name])) {
            return $this->changed[$name] ?? $this->columns[$name];
        }
        throw new BadFunctionCallException("No property <{$name}> found");
    }

    function __set($name, $value)
    {
        if (isset($this->columnsDefinition[$name])) {
            $this->changed[$name] = $value;
            return;
        }

        throw new BadFunctionCallException("No property <{$name}> found");
    }

    private function throwIfNotSatisfiesRequirements()
    {
        $unsatisfiedColumns = array();
        foreach ($this->columnsDefinition as $key => $value) {
            if ($this->columnsDefinition[$key]["required"]) {
                $satisfied = !empty($this->changed[$key]) || !empty($this->columns[$key]);
                if (!$satisfied) {
                    array_push($unsatisfiedColumns, $key);
                }
            }
        }

        if (count($unsatisfiedColumns) > 0) {
            $columns = implode($unsatisfiedColumns, ", ");
            throw new RuntimeException("Columns ({$columns}) are not satisfied");
        }
    }

    public function toArray()
    {
        return array_merge($this->columns, $this->changed);
    }

    /**
     * Exports array containing columns, placeholders(?), values sequence
     * for building SQL statements
     *
     * @return array data set for SQL
     * @hide
     */
    public function export()
    {
        $this->throwIfNotSatisfiesRequirements();

        if (count($this->columns) == 0) {
            return array();
        }

        $values = [];
        $columnsSql = "";
        $placeholders = "";
        $needsCommaSeparate = false;

        foreach ($this->columnsDefinition as $key => $definition) {
            $comma = ($needsCommaSeparate ? "," : '');

            $columnsSql .= $comma . "{$key}";
            $needsCommaSeparate = true;

            if (isset($definition["sql"])) {
                $placeholders .= $comma . $definition["sql"];
            } else {
                $placeholders .= $comma . '? ';
                array_push($values, $this->changed[$key] ?? $this->columns[$key]);
            }
        }

        return array(
            "columns" => $columnsSql,
            "placeholders" => $placeholders,
            "values" => $values
        );
    }

    function __toString()
    {
        $comma = '';
        $result = '';
        foreach ($this->toArray() as $key => $value) {
            $result .= $comma . " {$key} => {$value}";
            $comma = ',';
        }
        return __CLASS__ . "($result)";
    }
}