<?php

namespace Corviz\Connector\PDO;

use Corviz\Database\Connection as BaseConnection;
use Corviz\Database\Query;
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
        $sql = $this->translate($query);

        $args = [];
        //TODO: fill $args with values set in query object

        array_unshift($args, $sql);
        return $this->nativeQuery(...$args);
    }

    /**
     * Translate a query object into a
     * SELECT string.
     *
     * @param Query $query
     *
     * @return string
     */
    private function translate(Query $query): string
    {
        return "";
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

        $this->options = $options;
    }
}
