<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;

// Every seeder extends this one, so they all have $this->faker ready.
class BaseSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }
}
