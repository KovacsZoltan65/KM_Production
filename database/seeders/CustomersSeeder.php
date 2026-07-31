<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class CustomersSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $customers = [
            [
                'code' => 'CUST-0001',
                'name' => 'Központi Laboratórium Kft.',
                'tax_number' => '12345678-2-42',
                'email' => 'kozponti-labor@teszt.test',
                'phone' => '+36 1 111 2222',
                'billing_address' => '1051 Budapest, Teszt utca 1.',
                'shipping_address' => '1051 Budapest, Teszt utca 1.',
                'notes' => 'Központi laboratórium, aki az első kliens.',
                'is_active' => true,
            ]
        ];

        foreach($customers as $customer) {
            // Create customer logic here
            Customer::create($customer);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

}