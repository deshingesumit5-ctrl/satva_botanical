<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlantSeeder::class,
            DepartmentSeeder::class,
            WarehouseSeeder::class,
            UomSeeder::class,
            ProductCategorySeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            CompanySettingSeeder::class,
            SystemSettingSeeder::class,
            DocumentNumberingSeeder::class,
            DemoGarciniaSeeder::class,
            DemoTransactionSeeder::class,
        ]);
    }
}
