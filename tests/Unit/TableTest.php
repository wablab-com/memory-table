<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Factories\TableFactory;
use Tests\Factories\TableFillerFactory;
use WabLab\MemoryTable\ComputeFieldType;
use WabLab\MemoryTable\Exception\AtLeastOneKeyMustBeAdded;
use WabLab\MemoryTable\Exception\ComputeFieldCannotBeUsedAsKey;
use WabLab\MemoryTable\Exception\FieldNameAlreadyDeclared;
use WabLab\MemoryTable\Exception\IndexAlreadyAssigned;
use WabLab\MemoryTable\Exception\IndexFieldDoesNotMatchWithTableFields;
use WabLab\MemoryTable\Exception\NoMatchedRecordsCouldBeFound;
use WabLab\MemoryTable\Exception\PrimaryKeyAlreadyExists;
use WabLab\MemoryTable\Field;
use WabLab\MemoryTable\Index;
use WabLab\MemoryTable\StringFieldType;
use WabLab\MemoryTable\Table;

class TableTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setOutputCallback(function() {});
    }


    public function testTableObject() {
        $table = TableFactory::createPersonTableObject();
        $this->assertTrue($table instanceof Table);
    }


    public function testTableObjectAttributes() {
        $table = TableFactory::createPersonTableObject();
        $this->assertEquals('person_table', $table->getName());
        $this->assertTrue(count($table->getFields()) > 0);
        $this->assertTrue(count($table->getPrimaryKeys()) > 0);
    }


    public function testInsertProcess() {
        $table = TableFactory::createPersonTableObject();

        $recordsToAdd = 100;
        TableFillerFactory::fillPersonTableObject($table, $recordsToAdd);
        $this->assertEquals($table->count(), $recordsToAdd);
    }

    public function testUpdateProcess() {
        $table = TableFactory::createPersonTableObject();

        $recordsToAdd = 100;
        TableFillerFactory::fillPersonTableObject($table, $recordsToAdd);

        $table->updateRow(['id' => 2], ['first_name' => 'Ahmad', 'last_name' => 'Ahmad']);

        $record = $table->find(['id' => 2]);
        $this->assertEquals('Ahmad', $record['first_name']);
        $this->assertEquals('Ahmad', $record['last_name']);
    }

    public function testReplaceAnExistingRow() {
        $table = TableFactory::createPersonTableObject();

        $recordsToAdd = 100;
        TableFillerFactory::fillPersonTableObject($table, $recordsToAdd);

        $table->replaceRow(['id' => 2, 'first_name' => 'Ahmad', 'last_name' => 'Ahmad']);

        $record = $table->find(['id' => 2]);
        $this->assertEquals('Ahmad', $record['first_name']);
        $this->assertEquals('Ahmad', $record['last_name']);
        $this->assertEmpty($record['address']);
    }

    public function testReplaceANoneExistingRow() {
        $table = TableFactory::createPersonTableObject();
        $table->replaceRow(['id' => 2, 'first_name' => 'Ahmad', 'last_name' => 'Ahmad']);

        $record = $table->find(['id' => 2]);
        $this->assertEquals('Ahmad', $record['first_name']);
        $this->assertEquals('Ahmad', $record['last_name']);
        $this->assertEmpty($record['address']);
    }

    public function testFieldNameAlreadyDeclared() {
        $table = TableFactory::createPersonTableObject();
        try {
            $table->addField( new Field( 'first_name', new StringFieldType() ) );
            throw new \Exception('No errors');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(FieldNameAlreadyDeclared::class, $exception);
        }
    }

    public function testComputeFieldCannotBeUsedAsKey() {
        $table = TableFactory::createPersonTableObject();
        try {
            $table->addToPrimaryKey( new Field( 'computeField', new ComputeFieldType(function () {}) ) );
            throw new \Exception('No errors');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(ComputeFieldCannotBeUsedAsKey::class, $exception);
        }
    }

    public function testPrimaryKeyAlreadyExists() {
        $table = TableFactory::createPersonTableObject();
        $recordsToAdd = 100;
        TableFillerFactory::fillPersonTableObject($table, $recordsToAdd);
        try {
            $table->insertRow(['id' => 3, 'first_name' => 'Ahmad']);
            throw new \Exception('No errors');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(PrimaryKeyAlreadyExists::class, $exception);
        }
    }

    public function testNoMatchedRecordsCouldBeFound() {
        $table = TableFactory::createPersonTableObject();
        try {
            $table->updateRow(['id' => 3], ['first_name' => 'Ahmad']);
            throw new \Exception('No errors');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(NoMatchedRecordsCouldBeFound::class, $exception);
        }
    }

    public function testFindNoneExistingRecord() {
        $table = TableFactory::createPersonTableObject();
        $this->assertNull($table->find(['id' => 2]));
    }

    public function testMethodYieldAll() {
        $table = TableFactory::createPersonTableObject();
        $recordsToAdd = 100;
        TableFillerFactory::fillPersonTableObject($table, $recordsToAdd);
        $counter = 0;
        foreach($table->yieldAll() as $row) {
            $counter++;
        }
        $this->assertEquals($recordsToAdd, $counter);
    }

    public function testMethodAll() {
        $table = TableFactory::createPersonTableObject();
        $recordsToAdd = 100;
        TableFillerFactory::fillPersonTableObject($table, $recordsToAdd);

        $this->assertEquals($recordsToAdd, count($table->all()));
    }

    public function testAtLeastOneKeyMustBeAdded() {
        $table = new Table('test_table');
        $table->addField( new Field('name', new StringFieldType() ) );

        try {
            $table->insertRow(['name' => 'Ahmad']);
            throw new \Exception('No errors');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(AtLeastOneKeyMustBeAdded::class, $exception);
        }
    }

    public function testLongerPrimaryLength() {
        $table = new Table('test_table');
        $table->addToPrimaryKey( new Field('text', new StringFieldType() ) );
        $table->insertRow(['text' => "I've got my mom's old phone, but I can't afford a pricey wireless plan, so I got a free phone number from TextNow and I use the app to talk to my friends for free!"]);
        $this->assertEquals(1, $table->count());
    }

    public function testAtLeastOneKeyMustBeAddedToIndex() {
        $table = TableFactory::createPersonTableObject();
        try {
            $table->addIndex(new Index('blank_index'));
            throw new \Exception('No errors');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(AtLeastOneKeyMustBeAdded::class, $exception);
        }
    }

    public function testIndexAlreadyAssigned() {
        $table = TableFactory::createPersonTableObject();
        try {
            $index1 = new Index('first_name_index');
            $index1->addField($table->getField('first_name'));

            $table->addIndex($index1);
            $table->addIndex($index1);
            throw new \Exception('No errors');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(IndexAlreadyAssigned::class, $exception);
        }
    }

    public function testIndexFieldDoesNotMatchWithTableFields() {
        $table = TableFactory::createPersonTableObject();
        try {
            $index1 = new Index('unknown_field_index');
            $index1->addField(new Field('unknown_field_name', new StringFieldType() ));

            $table->addIndex($index1);
            throw new \Exception('No errors');
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(IndexFieldDoesNotMatchWithTableFields::class, $exception);
        }
    }


    public function testIndexFieldsCountAfterInsertProcess() {
        $table = TableFactory::createPersonTableWithIndexes();
        $table->insertRow([
            'id' => 1,
            'first_name' => 'Ahmad',
            'last_name' => 'Saleh',
            'address' => '',
            'birth_date' => rand(time() - (60 * 60 * 24 * 365 * 90), time()),
            'weight' => rand(50, 150)
        ]);
        $table->insertRow([
            'id' => 2,
            'first_name' => 'Ahmad',
            'last_name' => 'Saleh',
            'address' => '',
            'birth_date' => rand(time() - (60 * 60 * 24 * 365 * 90), time()),
            'weight' => rand(50, 150)
        ]);

        $this->assertEquals(2, $table->countByIndex('first_name_index', ['first_name' => 'Ahmad']));
    }


    public function testUpdateTableWithIndexes() {
        $table = TableFactory::createPersonTableWithIndexes();
        $table->insertRow([
            'id' => 1,
            'first_name' => 'Ahmad',
            'last_name' => 'Saleh',
            'address' => '',
            'birth_date' => rand(time() - (60 * 60 * 24 * 365 * 90), time()),
            'weight' => rand(50, 150)
        ]);
        $table->insertRow([
            'id' => 2,
            'first_name' => 'Ahmad',
            'last_name' => 'Saleh',
            'address' => '',
            'birth_date' => rand(time() - (60 * 60 * 24 * 365 * 90), time()),
            'weight' => rand(50, 150)
        ]);

        $this->assertEquals(0, $table->countByIndex('first_name_index', ['first_name' => 'Osama']));

        $table->updateRow(['id' => 2], ['first_name' => 'Osama']);

        $this->assertEquals(1, $table->countByIndex('first_name_index', ['first_name' => 'Ahmad']));
        $this->assertEquals(1, $table->countByIndex('first_name_index', ['first_name' => 'Osama']));
    }

    public function testUpdateRowPrimaryKey() {
        $table = TableFactory::createPersonTableWithIndexes();

        $table->insertRow([
            'id' => 1,
            'first_name' => 'Ahmad',
            'last_name' => 'Saleh',
            'address' => '',
            'birth_date' => rand(time() - (60 * 60 * 24 * 365 * 90), time()),
            'weight' => rand(50, 150)
        ]);
        $table->insertRow([
            'id' => 2,
            'first_name' => 'Ahmad',
            'last_name' => 'Saleh',
            'address' => '',
            'birth_date' => rand(time() - (60 * 60 * 24 * 365 * 90), time()),
            'weight' => rand(50, 150)
        ]);


        $table->updateRow(['id' => 2], ['id' => 5, 'first_name' => 'Osama']);
        $this->assertEquals(1, $table->countByIndex('first_name_index', ['first_name' => 'Ahmad']));
        $this->assertEquals(1, $table->countByIndex('first_name_index', ['first_name' => 'Osama']));

        $this->assertNull($table->find(['id' => 2]));
        $this->assertNotNull($table->find(['id' => 5]));
    }



}
