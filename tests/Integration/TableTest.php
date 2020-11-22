<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Factories\TableFactory;
use Tests\Factories\TableFillerFactory;
use WabLab\MemoryTable\Table;

class TableTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setOutputCallback(function() {});
    }


    public function testTableToJson() {
        $table = TableFactory::createPersonTableObject();

        $recordsToAdd = 100;
        TableFillerFactory::fillPersonTableObject($table, $recordsToAdd);

        file_put_contents('/tmp/table_test_output.json', $table->toJson());
        $this->assertFileExists('/tmp/table_test_output.json');
    }

    public function testTableFromJson() {
        $table = TableFactory::createPersonTableObject();
        $table->fromJson(file_get_contents('/tmp/table_test_output.json'));
        $this->assertEquals(100, $table->count());
    }

}
