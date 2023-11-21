<?php

namespace Modules\Package\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Package\Entities\Package;

class PackageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Package::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'package_name' => fake()->name(),
            'no_of_departments' => fake()->randomElement([2, 4, 6, 8, 10, 12]),
            'no_of_employees' => fake()->randomElement([100, 500, 1000]),
            'no_of_payslips' => fake()->randomElement([500, 2500, 5000]),
        ];
    }
}
