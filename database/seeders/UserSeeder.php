<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Plant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('role_code', 'super_admin')->first();
        $plant = Plant::first();
        $adminDept = Department::where('code', 'DEPT-ADM')->first();

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'user_id' => 'USR-0001',
                'name' => 'Admin User',
                'email' => 'admin@sattvabotanical.local',
                'mobile' => '+91 9876543210',
                'password' => Hash::make('Admin@12345'),
                'department_id' => $adminDept ? $adminDept->id : null,
                'role_id' => $superAdminRole ? $superAdminRole->id : 1,
                'plant_id' => $plant ? $plant->id : null,
                'status' => 'Active',
            ]
        );
    }
}
