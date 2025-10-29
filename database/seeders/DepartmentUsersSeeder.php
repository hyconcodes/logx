<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DepartmentUsersSeeder extends Seeder
{
    public function run()
    {
        // Define supervisors for each department
        $departmentSupervisors = [
            'Computer Science' => [
                ['name' => 'Dr. Alan Turing', 'email' => 'alan.turing@university.edu'],
                ['name' => 'Prof. Ada Lovelace', 'email' => 'ada.lovelace@university.edu'],
                ['name' => 'Dr. Tim Berners-Lee', 'email' => 'tim.bernerslee@university.edu'],
            ],
            'Mathematics' => [
                ['name' => 'Prof. Isaac Newton', 'email' => 'isaac.newton@university.edu'],
                ['name' => 'Dr. Emmy Noether', 'email' => 'emmy.noether@university.edu'],
                ['name' => 'Prof. Leonhard Euler', 'email' => 'leonhard.euler@university.edu'],
                ['name' => 'Dr. Srinivasa Ramanujan', 'email' => 'srinivasa.ramanujan@university.edu'],
            ],
            'Physics' => [
                ['name' => 'Prof. Albert Einstein', 'email' => 'albert.einstein@university.edu'],
                ['name' => 'Dr. Marie Curie', 'email' => 'marie.curie@university.edu'],
                ['name' => 'Prof. Richard Feynman', 'email' => 'richard.feynman@university.edu'],
                ['name' => 'Dr. Niels Bohr', 'email' => 'niels.bohr@university.edu'],
            ],
            'Chemistry' => [
                ['name' => 'Prof. Dmitri Mendeleev', 'email' => 'dmitri.mendeleev@university.edu'],
                ['name' => 'Dr. Linus Pauling', 'email' => 'linus.pauling@university.edu'],
                ['name' => 'Prof. Dorothy Hodgkin', 'email' => 'dorothy.hodgkin@university.edu'],
                ['name' => 'Dr. Robert Woodward', 'email' => 'robert.woodward@university.edu'],
            ],
            'Biology' => [
                ['name' => 'Prof. Charles Darwin', 'email' => 'charles.darwin@university.edu'],
                ['name' => 'Dr. Rosalind Franklin', 'email' => 'rosalind.franklin@university.edu'],
                ['name' => 'Prof. Gregor Mendel', 'email' => 'gregor.mendel@university.edu'],
                ['name' => 'Dr. Barbara McClintock', 'email' => 'barbara.mcclintock@university.edu'],
            ],
        ];

        // Define students for each department
        $departmentStudents = [
            'Computer Science' => [
                ['name' => 'Alex Johnson', 'matric' => 'CS2024011'],
                ['name' => 'Sarah Williams', 'matric' => 'CS2024012'],
                ['name' => 'Michael Brown', 'matric' => 'CS2024013'],
                ['name' => 'Emily Davis', 'matric' => 'CS2024014'],
                ['name' => 'James Wilson', 'matric' => 'CS2024015'],
                ['name' => 'Jessica Miller', 'matric' => 'CS2024016'],
                ['name' => 'David Garcia', 'matric' => 'CS2024017'],
                ['name' => 'Ashley Rodriguez', 'matric' => 'CS2024018'],
            ],
            'Mathematics' => [
                ['name' => 'Christopher Lee', 'matric' => 'MT2024001'],
                ['name' => 'Amanda Taylor', 'matric' => 'MT2024002'],
                ['name' => 'Joshua Anderson', 'matric' => 'MT2024003'],
                ['name' => 'Stephanie Thomas', 'matric' => 'MT2024004'],
                ['name' => 'Andrew Jackson', 'matric' => 'MT2024005'],
                ['name' => 'Michelle White', 'matric' => 'MT2024006'],
                ['name' => 'Ryan Harris', 'matric' => 'MT2024007'],
                ['name' => 'Lauren Martin', 'matric' => 'MT2024008'],
                ['name' => 'Kevin Thompson', 'matric' => 'MT2024009'],
                ['name' => 'Nicole Garcia', 'matric' => 'MT2024010'],
            ],
            'Physics' => [
                ['name' => 'Brandon Martinez', 'matric' => 'PH2024001'],
                ['name' => 'Samantha Robinson', 'matric' => 'PH2024002'],
                ['name' => 'Tyler Clark', 'matric' => 'PH2024003'],
                ['name' => 'Rachel Rodriguez', 'matric' => 'PH2024004'],
                ['name' => 'Justin Lewis', 'matric' => 'PH2024005'],
                ['name' => 'Megan Lee', 'matric' => 'PH2024006'],
                ['name' => 'Nathan Walker', 'matric' => 'PH2024007'],
                ['name' => 'Brittany Hall', 'matric' => 'PH2024008'],
                ['name' => 'Zachary Allen', 'matric' => 'PH2024009'],
            ],
            'Chemistry' => [
                ['name' => 'Danielle Young', 'matric' => 'CH2024001'],
                ['name' => 'Jonathan Hernandez', 'matric' => 'CH2024002'],
                ['name' => 'Kayla King', 'matric' => 'CH2024003'],
                ['name' => 'Austin Wright', 'matric' => 'CH2024004'],
                ['name' => 'Courtney Lopez', 'matric' => 'CH2024005'],
                ['name' => 'Sean Hill', 'matric' => 'CH2024006'],
                ['name' => 'Alexis Scott', 'matric' => 'CH2024007'],
                ['name' => 'Jordan Green', 'matric' => 'CH2024008'],
                ['name' => 'Taylor Adams', 'matric' => 'CH2024009'],
                ['name' => 'Morgan Baker', 'matric' => 'CH2024010'],
            ],
            'Biology' => [
                ['name' => 'Cameron Gonzalez', 'matric' => 'BL2024001'],
                ['name' => 'Jasmine Nelson', 'matric' => 'BL2024002'],
                ['name' => 'Hunter Carter', 'matric' => 'BL2024003'],
                ['name' => 'Sierra Mitchell', 'matric' => 'BL2024004'],
                ['name' => 'Caleb Perez', 'matric' => 'BL2024005'],
                ['name' => 'Destiny Roberts', 'matric' => 'BL2024006'],
                ['name' => 'Mason Turner', 'matric' => 'BL2024007'],
                ['name' => 'Savannah Phillips', 'matric' => 'BL2024008'],
                ['name' => 'Logan Campbell', 'matric' => 'BL2024009'],
                ['name' => 'Paige Parker', 'matric' => 'BL2024010'],
            ],
        ];

        $departments = Department::all();
        
        foreach ($departments as $dept) {
            $this->command->info("Processing {$dept->name} Department...");
            
            // Skip if no data defined for this department
            if (!isset($departmentSupervisors[$dept->name]) || !isset($departmentStudents[$dept->name])) {
                $this->command->warn("No seed data defined for {$dept->name}, skipping...");
                continue;
            }
            
            // Create supervisors
            $supervisors = $departmentSupervisors[$dept->name];
            $this->command->info("Creating " . count($supervisors) . " supervisors...");
            
            foreach ($supervisors as $supervisor) {
                if (User::where('email', $supervisor['email'])->exists()) {
                    $this->command->warn("- Supervisor {$supervisor['name']} already exists");
                    continue;
                }
                
                $user = User::create([
                    'name' => $supervisor['name'],
                    'email' => $supervisor['email'],
                    'password' => Hash::make('password123'),
                    'department_id' => $dept->id,
                    'status' => 'active'
                ]);
                $user->assignRole('supervisor');
                $this->command->info("- Created supervisor: {$supervisor['name']}");
            }
            
            // Create students
            $students = $departmentStudents[$dept->name];
            $this->command->info("Creating " . count($students) . " students...");
            
            foreach ($students as $student) {
                $email = strtolower(str_replace(' ', '.', $student['name'])) . '@student.edu';
                
                if (User::where('email', $email)->exists()) {
                    $this->command->warn("- Student {$student['name']} already exists");
                    continue;
                }
                
                $user = User::create([
                    'name' => $student['name'],
                    'email' => $email,
                    'matric_no' => $student['matric'],
                    'password' => Hash::make('password123'),
                    'department_id' => $dept->id,
                    'status' => 'active'
                ]);
                $user->assignRole('student');
                $this->command->info("- Created student: {$student['name']} ({$student['matric']}) - {$email}");
            }
            
            // Show department statistics
            $totalStudents = $dept->students()->count();
            $totalSupervisors = $dept->supervisors()->count();
            $unassignedStudents = $dept->students()->whereNull('supervisor_id')->count();
            
            $this->command->info("Department Statistics:");
            $this->command->info("- Total Students: {$totalStudents}");
            $this->command->info("- Total Supervisors: {$totalSupervisors}");
            $this->command->info("- Unassigned Students: {$unassignedStudents}");
            $this->command->info("---");
        }
        
        $this->command->info('🎉 Seeding completed for all departments!');
    }
}