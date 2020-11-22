<?php

namespace Tests\Factories;

use Faker\Factory;
use WabLab\MemoryTable\Table;

class TableFillerFactory
{
    public static function fillPersonTableObject(Table $table, $recordsCount = 100) {


        for($id = 1; $id <= $recordsCount; $id++) {
            $faker = Factory::create();
            $table->insertRow([
                'id' => $id,
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'address' => $faker->address,
                'birth_date' => rand(time() - (60 * 60 * 24 * 365 * 90), time()),
                'weight' => rand(50, 150)
            ]);
        }
        return $table;
    }
}