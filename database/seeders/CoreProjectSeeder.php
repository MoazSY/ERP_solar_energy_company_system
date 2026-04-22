<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Agency;
use App\Models\Agency_manager;
use App\Models\Areas;
use App\Models\Batteries;
use App\Models\Company_agency_employee;
use App\Models\Employee;
use App\Models\Governorates;
use App\Models\Inverters;
use App\Models\Neighborhood;
use App\Models\Products;
use App\Models\Solar_company;
use App\Models\Solar_company_manager;
use App\Models\Solar_panal;
use App\Models\Subscribe_polices;
use App\Models\System_admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoreProjectSeeder extends Seeder
{
    private int $phoneCounter = 1;
    private int $accountCounter = 1;

    public function run(): void
    {
        $admin = System_admin::updateOrCreate(
            ['email' => 'admin@solar.local'],
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'date_of_birth' => '1990-01-01',
                'phoneNumber' => $this->nextPhone(),
                'password' => Hash::make('Admin12345'),
                'account_number' => $this->nextAccount(),
                'syriatel_cash_phone' => $this->nextPhone(),
                'about_him' => 'Seeded admin account',
            ]
        );

        $this->seedSubscribePolicies($admin);

        [$governorates, $areas, $neighborhoods] = $this->seedLocations();

        $companyManagers = $this->seedCompanyManagers();
        $agencyManagers = $this->seedAgencyManagers();

        $companies = $this->seedCompanies($companyManagers);
        $agencies = $this->seedAgencies($agencyManagers);

        $this->seedAddresses($companies, $agencies, $governorates, $areas, $neighborhoods);

        $employees = $this->seedEmployees(30);

        $this->seedEmployeeAssignments($employees, $companies, $agencies);

        $this->seedProducts($companies, $agencies);
    }

    private function seedSubscribePolicies(System_admin $admin): void
    {
        $policies = [
            ['Company Monthly Basic', 'company', 50000, 'SY', 1, 'month', true],
            ['Company Yearly Pro', 'company', 1200, 'USD', 1, 'year', true],
            ['Agency Monthly Basic', 'agency', 45000, 'SY', 1, 'month', true],
            ['Agency Yearly Pro', 'agency', 1000, 'USD', 1, 'year', true],
        ];

        foreach ($policies as $policy) {
            Subscribe_polices::updateOrCreate(
                [
                    'admin_id' => $admin->id,
                    'name' => $policy[0],
                ],
                [
                    'description' => 'Seeded policy: ' . $policy[0],
                    'apply_to' => $policy[1],
                    'subscription_fee' => $policy[2],
                    'currency' => $policy[3],
                    'duration_value' => $policy[4],
                    'duration_type' => $policy[5],
                    'is_active' => $policy[6],
                    'is_trial_granted' => true,
                ]
            );
        }
    }

    private function seedLocations(): array
    {
        $map = [
            'Damascus' => ['Mazzeh', 'Midan'],
            'Homs' => ['Waer', 'Inshaat'],
            'Aleppo' => ['Aziziyeh', 'Sulaimaniyah'],
        ];

        $governorates = collect();
        $areas = collect();
        $neighborhoods = collect();

        foreach ($map as $govName => $areaNames) {
            $gov = Governorates::firstOrCreate(['name' => $govName]);
            $governorates->push($gov);

            foreach ($areaNames as $idx => $areaName) {
                $area = Areas::firstOrCreate(
                    ['name' => $govName . ' - ' . $areaName],
                    ['governorate_id' => $gov->id]
                );
                if (!$area->governorate_id) {
                    $area->governorate_id = $gov->id;
                    $area->save();
                }
                $areas->push($area);

                for ($n = 1; $n <= 2; $n++) {
                    $neighborhood = Neighborhood::firstOrCreate(
                        ['name' => $area->name . ' - Block ' . $n],
                        ['area_id' => $area->id]
                    );
                    if (!$neighborhood->area_id) {
                        $neighborhood->area_id = $area->id;
                        $neighborhood->save();
                    }
                    $neighborhoods->push($neighborhood);
                }
            }
        }

        return [$governorates, $areas, $neighborhoods];
    }

    private function seedCompanyManagers(): array
    {
        $managers = [];

        for ($i = 1; $i <= 5; $i++) {
            $email = 'company.manager' . $i . '@solar.local';
            $manager = Solar_company_manager::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => 'Company',
                    'last_name' => 'Manager ' . $i,
                    'date_of_birth' => '1992-01-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'phoneNumber' => $this->nextPhone(),
                    'password' => Hash::make('Password123'),
                    'account_number' => $this->nextAccount(),
                    'syriatel_cash_phone' => $this->nextPhone(),
                    'about_him' => 'Seeded company manager ' . $i,
                    'Activate_Account' => true,
                ]
            );
            $managers[] = $manager;
        }

        return $managers;
    }

    private function seedAgencyManagers(): array
    {
        $managers = [];

        for ($i = 1; $i <= 5; $i++) {
            $email = 'agency.manager' . $i . '@solar.local';
            $manager = Agency_manager::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => 'Agency',
                    'last_name' => 'Manager ' . $i,
                    'date_of_birth' => '1993-02-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'phoneNumber' => $this->nextPhone(),
                    'password' => Hash::make('Password123'),
                    'account_number' => $this->nextAccount(),
                    'syriatel_cash_phone' => $this->nextPhone(),
                    'about_him' => 'Seeded agency manager ' . $i,
                    'Activate_Account' => true,
                ]
            );
            $managers[] = $manager;
        }

        return $managers;
    }

    private function seedCompanies(array $companyManagers): array
    {
        $companies = [];

        foreach ($companyManagers as $i => $manager) {
            $company = Solar_company::updateOrCreate(
                ['company_email' => 'company' . ($i + 1) . '@solar.local'],
                [
                    'solar_company_manager_id' => $manager->id,
                    'company_name' => 'Solar Company ' . ($i + 1),
                    'commerical_register_number' => 'SC-REG-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'company_description' => 'Seeded company profile',
                    'company_phone' => $this->nextPhone(),
                    'tax_number' => 'SC-TAX-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'company_status' => 'active',
                    'verified_at' => now(),
                    'working_hours_start' => '08:00:00',
                    'working_hours_end' => '16:00:00',
                ]
            );
            $companies[] = $company;
        }

        return $companies;
    }

    private function seedAgencies(array $agencyManagers): array
    {
        $agencies = [];

        foreach ($agencyManagers as $i => $manager) {
            $agency = Agency::updateOrCreate(
                ['agency_email' => 'agency' . ($i + 1) . '@solar.local'],
                [
                    'agency_manager_id' => $manager->id,
                    'agency_name' => 'Solar Agency ' . ($i + 1),
                    'commerical_register_number' => 'AG-REG-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'agency_description' => 'Seeded agency profile',
                    'agency_phone' => $this->nextPhone(),
                    'tax_number' => 'AG-TAX-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    'agency_status' => 'active',
                    'verified_at' => now(),
                    'working_hours_start' => '08:30:00',
                    'working_hours_end' => '17:00:00',
                ]
            );
            $agencies[] = $agency;
        }

        return $agencies;
    }

    private function seedAddresses(array $companies, array $agencies, $governorates, $areas, $neighborhoods): void
    {
        foreach (array_merge($companies, $agencies) as $entity) {
            $gov = $governorates->random();
            $area = $areas->where('governorate_id', $gov->id)->first() ?? $areas->random();
            $neighborhood = $neighborhoods->where('area_id', $area->id)->first() ?? $neighborhoods->random();

            Address::updateOrCreate(
                [
                    'entity_type_type' => get_class($entity),
                    'entity_type_id' => $entity->id,
                ],
                [
                    'governorate_id' => $gov->id,
                    'area_id' => $area->id,
                    'neighborhood_id' => $neighborhood->id,
                    'address_description' => 'Seeded address for entity #' . $entity->id,
                    'latitude' => '33.5138',
                    'longitude' => '36.2765',
                ]
            );
        }
    }

    private function seedEmployees(int $count): array
    {
        $employeeTypes = ['technician', 'inventory_manager', 'driver'];
        $employees = [];

        for ($i = 1; $i <= $count; $i++) {
            $type = $employeeTypes[array_rand($employeeTypes)];
            $employee = Employee::updateOrCreate(
                ['email' => 'employee' . $i . '@solar.local'],
                [
                    'first_name' => 'Employee',
                    'last_name' => 'No.' . $i,
                    'date_of_birth' => '1995-03-' . str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT),
                    'phoneNumber' => $this->nextPhone(),
                    'password' => Hash::make('Password123'),
                    'account_number' => $this->nextAccount(),
                    'syriatel_cash_phone' => null,
                    'about_him' => 'Seeded employee profile',
                    'employee_type' => $type,
                    'is_active' => true,
                ]
            );
            $employees[] = $employee;
        }

        return $employees;
    }

    private function seedEmployeeAssignments(array $employees, array $companies, array $agencies): void
    {
        $rolesByType = [
            'technician' => ['install_technician', 'metal_base_technician', 'blacksmith_workshop'],
            'inventory_manager' => ['inventory_manager'],
            'driver' => ['driver'],
        ];

        foreach ($employees as $index => $employee) {
            $toCompany = $index % 2 === 0;
            $entity = $toCompany ? $companies[array_rand($companies)] : $agencies[array_rand($agencies)];
            $entityType = get_class($entity);

            $candidateRoles = $rolesByType[$employee->employee_type] ?? ['install_technician'];
            $role = $candidateRoles[array_rand($candidateRoles)];
            $salaryType = $index % 3 === 0 ? 'rate' : 'fixed';

            Company_agency_employee::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'entity_type_type' => $entityType,
                    'entity_type_id' => $entity->id,
                    'role' => $role,
                ],
                [
                    'salary_type' => $salaryType,
                    'currency' => $index % 2 === 0 ? 'SY' : 'USD',
                    'work_type' => $index % 2 === 0 ? 'full_time' : 'task_based',
                    'payment_method' => $index % 2 === 0 ? 'bank_transfer' : 'cash',
                    'payment_frequency' => $index % 2 === 0 ? 'monthly' : 'after_task',
                    'salary_rate' => $salaryType === 'rate' ? 0.5 : 0,
                    'salary_amount' => $salaryType === 'fixed' ? 450000 : 0,
                ]
            );
        }
    }

    private function seedProducts(array $companies, array $agencies): void
    {
        $entities = array_merge($agencies, $companies);

        foreach ($entities as $index => $entity) {
            $productTypes = ['solar_panel', 'inverter', 'battery', 'accessory'];

            foreach ($productTypes as $typeIndex => $productType) {
                $productName = ucfirst($productType) . ' Product ' . ($index + 1) . '-' . ($typeIndex + 1);

                $product = Products::updateOrCreate(
                    [
                        'entity_type_type' => get_class($entity),
                        'entity_type_id' => $entity->id,
                        'product_name' => $productName,
                    ],
                    [
                        'product_type' => $productType,
                        'product_brand' => 'Brand-' . ($typeIndex + 1),
                        'model_number' => strtoupper(substr($productType, 0, 3)) . '-' . ($index + 100),
                        'quentity' => rand(10, 80),
                        'price' => rand(100, 2000),
                        'disscount_type' => $typeIndex % 2 === 0 ? 'percentage' : 'fixed',
                        'disscount_value' => $typeIndex % 2 === 0 ? rand(3, 15) : rand(10, 100),
                        'currency' => $typeIndex % 2 === 0 ? 'USD' : 'SY',
                        'manufacture_date' => now()->subMonths(rand(1, 24))->toDateString(),
                    ]
                );

                if ($productType === 'battery') {
                    Batteries::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'battery_type' => 'lithium_ion',
                            'capacity_kwh' => 5.5,
                            'voltage_v' => '48V',
                            'cycle_life' => 6000,
                            'warranty_years' => 5,
                            'weight_kg' => 52,
                            'Amperage_Ah' => '200Ah',
                            'celles_type' => 'new',
                            'celles_name' => 'CATL',
                        ]
                    );
                }

                if ($productType === 'inverter') {
                    Inverters::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'grid_type' => 'hybrid',
                            'voltage_v' => '48V',
                            'grid_capacity_kw' => 10,
                            'solar_capacity_kw' => 12,
                            'inverter_open' => true,
                            'voltage_open' => 500,
                            'weight_kg' => 24,
                            'warranty_years' => 5,
                        ]
                    );
                }

                if ($productType === 'solar_panel') {
                    Solar_panal::updateOrCreate(
                        ['product_id' => $product->id],
                        [
                            'capacity_kw' => '620w',
                            'basbar_number' => 16,
                            'is_half_cell' => true,
                            'is_bifacial' => true,
                            'warranty_years' => 9.5,
                            'weight_kg' => 31.5,
                            'length_m' => 2.3,
                            'width_m' => 1.13,
                        ]
                    );
                }
            }
        }
    }

    private function nextPhone(): string
    {
        $phone = '09' . str_pad((string) $this->phoneCounter, 8, '0', STR_PAD_LEFT);
        $this->phoneCounter++;

        return $phone;
    }

    private function nextAccount(): string
    {
        $account = 'ACC-' . str_pad((string) $this->accountCounter, 8, '0', STR_PAD_LEFT);
        $this->accountCounter++;

        return $account;
    }
}
