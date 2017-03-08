<?php

namespace Tests;

require __DIR__.'/../vendor/autoload.php';

use Corviz\Connector\PDO\Connection;
//use Corviz\Database\Query\WhereClause;
use Corviz\Database\Result;
//
//
//class ConnectionTest extends \PHPUnit_Framework_TestCase
//{
//    public function testSimpleQuery()
//    {
//        $connection = new ConnectionDebug([
//            'dsn' => 'mysql:host=localhost;dbname=test',
//            'user' => 'root',
//            'password' => ''
//        ]);
//
//        $query = $connection->createQuery();
//
//        $query->from('user');
//
//        echo $connection->lastQuery, "\r\n";
//        print_r($connection->lastArgs);
//    }
//}

/* Debug class */
class ConnectionDebug extends Connection
{
    public $lastQuery;
    public $lastArgs;

    public function nativeQuery(...$args): Result
    {
        $aux = $args;
        $this->lastQuery = array_shift($aux);
        $this->lastArgs = $aux;

        return parent::nativeQuery($args);
    }
}

$connection = new ConnectionDebug([
    'dsn' => 'mysql:host=localhost;database=test',
    'user' => 'root',
    'password' => ''
]);

$query = $connection->createQuery();

$query->from('user');

echo $connection->lastQuery, "\r\n";
print_r($connection->lastArgs);