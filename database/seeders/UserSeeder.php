<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Plant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('role_code', 'super_admin')->first();
        $plant = Plant::first();
        $adminDept = Department::where('code', 'DEPT-ADM')->first();

        $existing = User::withTrashed()
            ->where('user_id', 'USR-0001')
            ->orWhere('id', 1)
            ->first();

        if (!$existing) {
            User::create([
                'user_id'       => 'USR-0001',
                'username'      => 'admin@gmail.com',
                'name'          => 'Super Admin',
                'email'         => 'admin@gmail.com',
                'mobile'        => '9876543210',
                'password'      => 'admin@123',
                'department_id' => $adminDept ? $adminDept->id : null,
                'role_id'       => $superAdminRole ? $superAdminRole->id : 1,
                'plant_id'      => $plant ? $plant->id : null,
                'status'        => 'Active',
            ]);
        }
    }
}
