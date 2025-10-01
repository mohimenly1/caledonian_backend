<?php

namespace Database\Seeders;

// database/seeders/SubscriptionFeesSeeder.php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionFeesSeeder extends Seeder
{
    public function run()
    {
        DB::table('subscription_fees')->insert([
            // Registration Fees
            ['category' => 'Registration Fees', 'sub_category' => 'New Families', 'amount' => 2800.00],
            ['category' => 'Registration Fees', 'sub_category' => 'Re-enrollment', 'amount' => 2200.00],

            // Books
            ['category' => 'Books', 'sub_category' => 'Printed Books', 'amount' => 950.00],
            ['category' => 'Books', 'sub_category' => 'E-Books', 'amount' => 3500.00],

            // Tuition Fees
            ['category' => 'Tuition Fees', 'sub_category' => 'Pre KG', 'amount' => 11200.00],
            ['category' => 'Tuition Fees', 'sub_category' => 'KG 1', 'amount' => 11200.00],
            ['category' => 'Tuition Fees', 'sub_category' => 'KG 2', 'amount' => 11200.00],
            ['category' => 'Tuition Fees', 'sub_category' => 'GR 1', 'amount' => 15500.00],
            ['category' => 'Tuition Fees', 'sub_category' => 'GR 2', 'amount' => 15500.00],
            ['category' => 'Tuition Fees', 'sub_category' => 'GR 3', 'amount' => 15500.00],
            ['category' => 'Tuition Fees', 'sub_category' => 'GR 4', 'amount' => 16600.00],
            ['category' => 'Tuition Fees', 'sub_category' => 'GR 5', 'amount' => 16600.00],
            ['category' => 'Tuition Fees', 'sub_category' => 'GR 6', 'amount' => 17700.00],

            // Uniform
            ['category' => 'Uniform', 'sub_category' => 'Full Uniform', 'amount' => 1500.00],
            ['category' => 'Uniform', 'sub_category' => 'Jacket', 'amount' => 500.00],
            ['category' => 'Uniform', 'sub_category' => 'Pants', 'amount' => 200.00],
            ['category' => 'Uniform', 'sub_category' => 'Shirt', 'amount' => 200.00],

            // Transportation and Food
            ['category' => 'Transportation and Food', 'sub_category' => 'Bus Service', 'amount' => 4200.00],
            ['category' => 'Transportation and Food', 'sub_category' => 'Meals', 'amount' => 3100.00],
        ]);
    }
}
