<?php

namespace Modules\Company\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Company\Entities\Company::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'company_name' => $this->faker->unique()->name(),
            'company_address' => $this->faker->address(),
            'company_email' => $this->faker->unique()->safeEmail(),
            'company_phone' => $this->faker->unique()->phoneNumber(),
            'company_logo' => null,
            'company_website' => $this->faker->url(),
            'company_registration_no' => $this->faker->unique()->phoneNumber(),
            'government_employee_no' => $this->faker->unique()->phoneNumber(),
            'fiscal_year_from' => '2022-05-18',
            'fiscal_year_to' => '2023-05-18',
            'bank_name' => $this->faker->unique()->name(),
            'bank_bic_or_swift_code' => $this->faker->unique()->phoneNumber(),
            'bank_iban_or_account_no' => $this->faker->unique()->phoneNumber(),
            'contact_person_name' => $this->faker->unique()->name(),
            'contact_person_email' => $this->faker->unique()->safeEmail(),
            'contact_person_phone' => $this->faker->unique()->phoneNumber(),
        ];
    }
}
