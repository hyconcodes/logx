<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SupervisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure supervisor role exists
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor']);

        // Create sample supervisor accounts
        $supervisors = [
            [
                'name' => 'Dr. John Smith',
                'email' => 'john.smith@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'name' => 'Prof. Sarah Johnson',
                'email' => 'sarah.johnson@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Michael Brown',
                'email' => 'michael.brown@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'name' => 'Prof. Emily Davis',
                'email' => 'emily.davis@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Robert Wilson',
                'email' => 'robert.wilson@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Lisa Anderson',
                'email' => 'lisa.anderson@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'name' => 'Prof. David Martinez',
                'email' => 'david.martinez@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Jennifer Taylor',
                'email' => 'jennifer.taylor@bouesti.edu.ng',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ],
        ];

        foreach ($supervisors as $supervisorData) {
            // Check if supervisor already exists
            $existingSupervisor = User::where('email', $supervisorData['email'])->first();
            
            if (!$existingSupervisor) {
                $supervisor = User::create($supervisorData);
                $supervisor->assignRole($supervisorRole);
                
                $this->command->info("Created supervisor: {$supervisor->name}");
            } else {
                $this->command->info("Supervisor already exists: {$existingSupervisor->name}");
            }
        }
    }
}
