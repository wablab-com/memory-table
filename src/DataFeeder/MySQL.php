<?php

namespace WabLab\MemoryTable\DataFeeder;


use WabLab\MemoryTable\Table;

class MySQL
{
    /**
     * @var \PDO
     */
    protected $connection;

    public function __construct($host, $username, $password, $dbname)
    {
        $this->connection = new \PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
    }

    public function fill(Table $table, string $query, $batchSize = 0) {
        $offset = 0;
        do {
            $limitQuery = '';
            if($batchSize) {
                $limitQuery = "LIMIT {$offset}, {$batchSize}";
            }
            $handler = $this->connection->query("$query {$limitQuery}");
            $rows = $handler->fetchAll(\PDO::FETCH_ASSOC);
            foreach($rows as &$row) {
                $table->insertRow($row, true);
            }
            $offset += $batchSize;
        } while($batchSize && count($rows));

    }

}
