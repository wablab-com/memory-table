<?php

namespace WabLab\MemoryTable\DataFeeder;


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
            echo "\nFetching from database:";
            $rows = $handler->fetchAll(\PDO::FETCH_ASSOC);
            echo "\nOffset: {$offset}, Batch Size: {$batchSize}\n";
            $startTime = time();
            foreach($rows as &$row) {
                $table->insertRow($row);
            }
            $offset += $batchSize;
            echo "Finished in ".(time() - $startTime)." seconds\n";
        } while($batchSize && count($rows));

    }

}
