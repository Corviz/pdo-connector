<?php

namespace Corviz\Connector\PDO;

use Corviz\Database\Connection as BaseConnection;
use Corviz\Database\Query;
use Corviz\Database\Query\Join;
use Corviz\Database\Result;
use Corviz\Mvc\Model;

class Connection extends BaseConnection
{
    /**
     * @var int
     */
    private $rowCount = 0;

    /**
     * @var array
     */
    private $options;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * Number of affected rows by the last
     * INSERT, UPDATE or DELETE query.
     *
     * @return int
     */
    public function affectedRows(): int
    {
        return $this->rowCount;
    }

    /**
     * Begin a database transaction.
     *
     * @return bool
     */
    public function begin(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Start a connection.
     *
     * @return mixed
     */
    public function connect(): bool
    {
        $options = $this->options;
        $connected = true;

        try {

            $this->pdo = new \PDO(
                $options['dsn'],
                $options['user'],
                $options['password'],
                $options['extras']
            );

            if (
                isset($options['afterConnect'])
                && $options['afterConnect'] instanceof \Closure
            ) {
                $options['afterConnect']($this->pdo);
            }

        } catch (\Exception $exception) {
            $connected = false;
        }

        return $connected;
    }

    /**
     * Inform if the current connection is active.
     *
     * @return bool
     */
    public function connected(): bool
    {
        return !is_null($this->pdo);
    }

    /**
     * @param Model $model
     *
     * @return Result
     */
    public function delete(Model $model): Result
    {
        // TODO: Implement delete() method.
    }

    /**
     * The id of the last stored document.
     *
     * @return string
     */
    public function lastId(): string
    {
        $this->pdo->lastInsertId();
    }

    /**
     * Execute a native query.
     *
     * @param array ...$args
     *
     * @return Result
     */
    public function nativeQuery(...$args): Result
    {
        $stmt = $this->pdo->prepare(array_shift($args));
        $stmt->execute($args);
        $this->rowCount = $stmt->rowCount();
    }

    /**
     * Rollback transaction.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Save the model data in its respective
     * table or collection.
     *
     * @param Model $model
     *
     * @return Result
     */
    public function save(Model $model): Result
    {
        // TODO: Implement save() method.
    }

    /**
     * Execute a select (or find) operation according
     * to the parameters provided by the query.
     *
     * @param Query $query
     *
     * @return Result
     */
    public function select(Query $query): Result
    {
        $args = [];
        $sql = $this->translate($query, $args);
        array_unshift($args, $sql);
        return $this->nativeQuery(...$args);
    }

    /**
     * @param array $joins
     * @param array $params
     *
     * @return string
     * @throws \Exception
     */
    private function parseJoinArray(array $joins, array &$params): string
    {
        $joinStr = '';
        foreach ($joins as $join) {
            if (!$join instanceof Join) {
                continue;
            }

            $piece = '';

            switch ($join->getType()) {
                case Join::TYPE_INNER: $piece .= 'INNER '; break;
                case Join::TYPE_RIGHT: $piece .= 'RIGHT '; break;
                case Join::TYPE_LEFT: $piece .= 'LEFT '; break;
                case JOIN::TYPE_OUTER: $piece .= 'FULL OUTER '; break;
                default: throw new \Exception('Invalid join type'); break;
            }

            $piece .= 'JOIN '.$join->getTable();

            $whereClause = $join->getWhereClause();
            if (!$whereClause->isEmpty()) {
                $piece .= ' ON '.$this->parseWhereClause($whereClause, $params);
            }

            $joinStr .= "$piece ";
        }

        return $joinStr;
    }

    /**
     * @param Query\WhereClause $whereClause
     * @param array             $params
     *
     * @return string
     */
    private function parseWhereClause(
        Query\WhereClause $whereClause,
        array &$params
    ): string {
        $clauses = $whereClause->getClauses();
        $whereStr = '';

        if (!$whereClause->isEmpty()) {
            $isFirst = true;

            foreach ($clauses as $clause) {
                $params = array_merge($params, $clause['params']);
                $value = $clause['value'];

                if (!$isFirst) {
                    $whereStr .= "{$clause['junction']} ";
                }

                //Format each type
                switch ($clause['type']) {
                    case 'where':
                        $whereStr .= "({$value['field1']} {$value['operator']} {$value['field2']}) ";
                    break;

                    case 'between':
                        $whereStr .= "({$value['value']} BETWEEN {$value['field1']} AND {$value['field2']}) ";
                    break;

                    case 'in':
                        $whereStr .= "({$value['field']} IN (".implode(',', $value['values']).")) ";
                    break;

                    case 'inQuery':
                        $whereStr .= "({$value['field']} IN (".$this->translate($value['query'], $params).")) ";
                    break;

                    case 'nested':
                        $whereStr .= '('.$this->parseWhereClause($value['whereClause'], $params).') ';
                    break;
                }

                if ($isFirst) {
                    $isFirst = false;
                }
            }
        }

        return $whereStr;
    }

    /**
     * Translate a query object into a
     * SELECT string.
     *
     * @param Query $query
     * @param array $params
     *
     * @return string
     */
    private function translate(Query $query, array &$params): string
    {
        $fields = implode(',', $query->getFields());
        $from = $query->getFrom();
        $join = $this->parseJoinArray($query->getJoins(), $params);
        $where = $this->parseWhereClause(
            $query->getWhereClause(),
            $params
        );

        $qryString = "
            SELECT $fields
            FROM $from
            $join
            
            ".($where ? "WHERE $where": '')."
        ";

        return $qryString;
    }

    /**
     * Connection constructor.
     *
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct(array $options)
    {
        if (!isset($options['dsn'], $options['user'], $options['password'])) {
            throw new \Exception('Missing "dsn", "user" or "password" information.');
        }

        if (!$this->connect()) {
            throw new \Exception('Could not connect to database');
        }

        $this->options = $options;
    }
}
