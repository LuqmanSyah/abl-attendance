<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operational = Division::where('name', 'Operasional')->firstOrFail();
        $administration = Division::where('name', 'Administrasi')->firstOrFail();
        $security = Division::where('name', 'Keamanan')->firstOrFail();
        $logistic = Division::where('name', 'Logistik')->firstOrFail();
        $fieldTechnician = Division::where('name', 'Teknisi Lapangan')->firstOrFail();

        $staff = Position::where('name', 'Staff')->firstOrFail();
        $supervisorPosition = Position::where('name', 'Supervisor')->firstOrFail();
        $fieldCoordinator = Position::where('name', 'Koordinator Lapangan')->firstOrFail();

        $andi = Employee::updateOrCreate(
            ['employee_code' => 'SPV001'],
            [
                'user_id' => $this->createUser('Andi Pratama', 'andi.pratama@example.com', 'supervisor')->id,
                'division_id' => $operational->id,
                'position_id' => $supervisorPosition->id,
                'superior_id' => null,
                'name' => 'Andi Pratama',
                'phone' => '081234560001',
                'address' => 'Jl. Merdeka No. 10, Jakarta',
                'status' => 'active',
            ],
        );

        $siti = Employee::updateOrCreate(
            ['employee_code' => 'SPV002'],
            [
                'user_id' => $this->createUser('Siti Rahma', 'siti.rahma@example.com', 'supervisor')->id,
                'division_id' => $fieldTechnician->id,
                'position_id' => $supervisorPosition->id,
                'superior_id' => null,
                'name' => 'Siti Rahma',
                'phone' => '081234560002',
                'address' => 'Jl. Sudirman No. 22, Jakarta',
                'status' => 'active',
            ],
        );

        $employees = [
            [
                'employee_code' => 'EMP001',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@example.com',
                'division_id' => $operational->id,
                'position_id' => $staff->id,
                'superior_id' => $andi->id,
                'phone' => '081234560101',
                'address' => 'Jl. Kenanga No. 5, Jakarta',
            ],
            [
                'employee_code' => 'EMP002',
                'name' => 'Rina Lestari',
                'email' => 'rina.lestari@example.com',
                'division_id' => $administration->id,
                'position_id' => $staff->id,
                'superior_id' => $andi->id,
                'phone' => '081234560102',
                'address' => 'Jl. Melati No. 8, Jakarta',
            ],
            [
                'employee_code' => 'EMP003',
                'name' => 'Dedi Kurniawan',
                'email' => 'dedi.kurniawan@example.com',
                'division_id' => $security->id,
                'position_id' => $fieldCoordinator->id,
                'superior_id' => $siti->id,
                'phone' => '081234560103',
                'address' => 'Jl. Anggrek No. 12, Jakarta',
            ],
            [
                'employee_code' => 'EMP004',
                'name' => 'Maya Putri',
                'email' => 'maya.putri@example.com',
                'division_id' => $logistic->id,
                'position_id' => $staff->id,
                'superior_id' => $siti->id,
                'phone' => '081234560104',
                'address' => 'Jl. Cempaka No. 15, Jakarta',
            ],
        ];

        foreach ($employees as $employee) {
            Employee::updateOrCreate(
                ['employee_code' => $employee['employee_code']],
                [
                    'user_id' => $this->createUser($employee['name'], $employee['email'], 'employee')->id,
                    'division_id' => $employee['division_id'],
                    'position_id' => $employee['position_id'],
                    'superior_id' => $employee['superior_id'],
                    'name' => $employee['name'],
                    'phone' => $employee['phone'],
                    'address' => $employee['address'],
                    'status' => 'active',
                ],
            );
        }
    }

    protected function createUser(string $name, string $email, string $role): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
            ],
        );
    }
}
