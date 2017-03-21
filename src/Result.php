<?php

namespace Corviz\Connector\PDO;

use Corviz\Database\Connection as BaseConnection;
use Corviz\Database\Result as BaseResult;
use Corviz\Database\Row;

class Result extends BaseResult
{
    /**
     * @var \PDOStatement
     */
    private $statement;

    /**
     * The number of rows.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * @return Row|null
     */
    public function fetch()
    {
        $rowData = $this->statement->fetch(\PDO::FETCH_ASSOC);
        return $rowData ? new Row($rowData) : null;
    }

    /**
     * @return array
     */
    public function fetchAll(): array
    {
        $rows = $this->statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row = new Row($row);
        }

        return $rows;
    }

    /**
     * Result constructor.
     *
     * @param BaseConnection $connection
     * @param \PDOStatement $statement
     */
    public function __construct(BaseConnection $connection, \PDOStatement $statement)
    {
        parent::__construct($connection);
        $this->statement = $statement;
    }
}