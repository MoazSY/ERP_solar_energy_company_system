<?php

namespace Database\Seeders;

use App\Models\Component_warranties;
use App\Models\Customer;
use App\Models\Metainence_request;
use App\Models\Products;
use App\Models\Project_warranties;
use App\Models\Solar_company;
use App\Models\Solar_company_manager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MaintenanceRequestsSeeder extends Seeder
{
    public function run()
    {
        // create manager and company
        $manager = Solar_company_manager::create([
            'first_name' => 'Demo',
            'last_name' => 'Manager',
            'email' => 'demo-manager' . rand(1, 999) . '@example.com',
            'password' => bcrypt('password'),
            'phoneNumber' => '0999' . rand(100000, 999999),
        ]);

        $uniqueSuffix = rand(1000, 9999);
        $company = Solar_company::create([
            'solar_company_manager_id' => $manager->id,
            'company_name' => 'Demo Solar Co ' . $uniqueSuffix,
            'company_email' => 'demo-company' . $uniqueSuffix . '@example.com',
            'company_phone' => '0999' . rand(100000, 999999),
            'commerical_register_number' => (string) rand(10000000, 99999999),
        ]);

        // create a customer
        $customer = Customer::create([
            'first_name' => 'Demo',
            'last_name' => 'Customer',
            'email' => 'demo-customer' . rand(1, 999) . '@example.com',
            'password' => bcrypt('password'),
            'phoneNumber' => '0999222' . rand(100, 999),
        ]);

        // create a product
        $product = Products::create([
            'product_name' => 'Demo Inverter',
            'product_type' => 'inverter',
            'price' => 100000,
            'currency' => 'SY',
            'entity_type_type' => Solar_company::class,
            'entity_type_id' => $company->id,
        ]);

        // create a minimal purchase invoice required by project warranties
        $purchaseInvoice = \App\Models\Purchase_invoice::create([
            'seller_entity_type' => Solar_company::class,
            'seller_entity_id' => $company->id,
            'buyer_entity_type' => Customer::class,
            'buyer_entity_id' => $customer->id,
            'buyer_name' => $customer->first_name . ' ' . $customer->last_name,
            'buyer_phone' => $customer->phoneNumber,
            'object_entity_type' => Metainence_request::class,
            'object_entity_id' => 0,
            'invoice_number' => 'INV-' . Str::upper(Str::random(8)),
            'invoice_date' => now()->toDateString(),
            'currency' => 'SY',
            'subtotal' => 0,
            'total_amount' => 0,
            'payment_status' => 'pending',
        ]);

        // create a project warranty and component warranty
        $projectWarranty = Project_warranties::create([
            'invoice_id' => $purchaseInvoice->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->first_name . ' ' . $customer->last_name,
            'company_id' => $company->id,
            'provider_name' => 'Demo Provider',
            'warranty_status' => 'active',
            'warranty_number' => 'W-' . Str::upper(Str::random(6)),
            'project_serial_number' => 'SN-' . Str::upper(Str::random(6)),
            'warranty_terms' => 'Standard warranty',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'installation_warranty_years' => 1,
        ]);

        Component_warranties::create([
            'project_warranty_id' => $projectWarranty->id,
            'item_id' => null,
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->first_name,
            'provider_name' => 'Demo Provider',
            'component_type' => 'inverter',
            'warranty_years' => 1,
            'warranty_terms' => 'Full component warranty',
            'product_name' => $product->product_name,
            'product_serial_number' => 'PSN-' . Str::upper(Str::random(6)),
            'warranty_status' => 'active',
            'warranty_source' => 'manufacturer',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        // create a maintenance request linked to the warranty
        Metainence_request::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->first_name . ' ' . $customer->last_name,
            'customer_phone' => $customer->phoneNumber,
            'metainence_type' => 'warranty',
            'issue_category' => 'inverter',
            'priority' => 'medium',
            'issue_description' => 'Unit not powering on',
            'manager_approval' => false,
            'manager_notes' => null,
            'metainence_status' => 'pending',
            'metainence_scheduled_at' => null,
            'system_sn' => $projectWarranty->project_serial_number,
            'warranty_number' => $projectWarranty->warranty_number,
            'image_state' => null,
            'estimated_cost' => 0,
            'problem_name' => 'No power',
            'problem_cause' => 'unknown',
            'is_paid' => false,
            'payment_method' => 'cash',
            'currency' => 'SY',
        ]);
    }
}
