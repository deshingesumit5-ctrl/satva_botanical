<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Plant;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $plant = Plant::first();
        $plantId = $plant ? $plant->id : null;
        $plantCode = $plant ? $plant->code : 'PLANT-01';

        $departments = [
            ['code' => 'DEPT-ADM', 'name' => 'Administration', 'department_head' => 'Admin Manager'],
            ['code' => 'DEPT-PUR', 'name' => 'Purchase', 'department_head' => 'Purchase Manager'],
            ['code' => 'DEPT-STR', 'name' => 'Stores & Warehouse', 'department_head' => 'Store Manager'],
            ['code' => 'DEPT-PRD', 'name' => 'Production', 'department_head' => 'Production Head'],
            ['code' => 'DEPT-QC', 'name' => 'Quality Control', 'department_head' => 'QC Manager'],
            ['code' => 'DEPT-SLS', 'name' => 'Sales & Marketing', 'department_head' => 'Sales Head'],
            ['code' => 'DEPT-ACC', 'name' => 'Accounts & Costing', 'department_head' => 'Chief Accountant'],
            ['code' => 'DEPT-MNT', 'name' => 'Maintenance', 'department_head' => 'Plant Engineer'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                [
                    'name' => $dept['name'],
                    'plant_id' => $plantId,
                    'plant_code' => $plantCode,
                    'department_head' => $dept['department_head'],
                    'description' => $dept['name'] . ' Department',
                    'status' => 'Active',
                ]
            );
        }
    }
}
