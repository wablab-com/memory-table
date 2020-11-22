<?php

namespace Tests\Factories;

use WabLab\MemoryTable\ComputeFieldType;
use WabLab\MemoryTable\Index;
use WabLab\MemoryTable\Table;
use WabLab\MemoryTable\Field;
use WabLab\MemoryTable\FloatFieldType;
use WabLab\MemoryTable\IntegerFieldType;
use WabLab\MemoryTable\StringFieldType;

class TableFactory
{
    public static function createPersonTableObject():Table {
        $table = new Table('person_table');
        $table->addToPrimaryKey( new Field('id', new IntegerFieldType() ) );
        $table->addField( new Field('first_name', new StringFieldType() ) );
        $table->addField( new Field('last_name', new StringFieldType() ) );
        $table->addField( new Field('birth_date', new IntegerFieldType() ) );
        $table->addField( new Field('weight', new FloatFieldType() ) );
        $table->addField( new Field('address', new StringFieldType() ) );
        $table->addField( new Field( 'age', new ComputeFieldType(function ($args) {
            $birthDate = $args['birth_date'] ?? 0;
            $integerAge = time() - $birthDate;
            return round($integerAge / (60 * 60 * 24 * 365));
        }) ) );

        return $table;
    }

    public static function createPersonTableWithIndexes():Table {
        $personTable = static::createPersonTableObject();

        $firstNameIndex = new Index('first_name_index');
        $firstNameIndex->addField($personTable->getField('first_name'));

        $lastNameIndex = new Index('last_name_index');
        $lastNameIndex->addField($personTable->getField('last_name'));

        $personTable->addIndex($firstNameIndex);
        $personTable->addIndex($lastNameIndex);
        return $personTable;
    }
}