<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Conflict_invoice;
use App\Models\Items;
use App\Models\Order_list;
use App\Models\Products;
use App\Models\Purchase_invoice;
use App\Models\Solar_company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConflictAgencyInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $company = Solar_company::query()->first();
        $agency = Agency::query()->first();
        $product = Products::query()->first();

        if (!$company || !$agency || !$product) {
            return;
        }

        $orderList = Order_list::query()->firstOrCreate(
            [
                'request_entity_type' => Solar_company::class,
                'request_entity_id' => $company->id,
                'orderable_entity_type' => Agency::class,
                'orderable_entity_id' => $agency->id,
            ],
            [
                'customer_first_name' => 'Conflict',
                'customer_last_name' => 'Order',
                'status' => 'in_progress',
                'sub_total_amount' => 150000,
                'total_discount_amount' => 0,
                'total_amount' => 150000,
                'with_delivery' => false,
                'calculated_delivery_fee' => 0,
                'identical_state' => 'seeded',
                'request_datetime' => now(),
            ]
        );

        Items::query()->updateOrCreate(
            [
                'itemable_type' => Order_list::class,
                'itemable_id' => $orderList->id,
                'product_id' => $product->id,
            ],
            [
                'item_name_snapshot' => $product->product_name,
                'quantity' => 2,
                'unit_price' => (float) ($product->price ?? 0),
                'total_price' => (float) ($product->price ?? 0) * 2,
                'unit_discount_amount' => 0,
                'total_discount_amount' => 0,
                'discount_type' => $product->disscount_type ?? 'fixed',
                'currency' => $product->currency ?? 'SY',
            ]
        );

        $invoice = Purchase_invoice::query()->firstOrCreate(
            [
                'seller_entity_type' => Agency::class,
                'seller_entity_id' => $agency->id,
                'order_list_id' => $orderList->id,
            ],
            [
                'buyer_entity_type' => Solar_company::class,
                'buyer_entity_id' => $company->id,
                'buyer_name' => $company->company_name,
                'buyer_phone' => $company->company_phone,
                'object_entity_type' => Order_list::class,
                'object_entity_id' => $orderList->id,
                'invoice_number' => 'AG-CONFLICT-' . $agency->id . '-' . $orderList->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7),
                'currency' => 'SY',
                'delivery_fee' => 0,
                'installation_fee' => 0,
                'subtotal' => 150000,
                'total_discount' => 0,
                'total_amount' => 150000,
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'net_profit' => 0,
            ]
        );

        Conflict_invoice::query()->updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
                'agency_id' => $agency->id,
            ],
            [
                'conflict_type' => 'decreased_amount',
                'conflict_amount' => 25000,
                'conflict_description' => 'Seeded conflict for testing show_conflict_agency_invoice',
                'image_related' => null,
                'conflict_state' => 'pending',
            ]
        );

        DB::statement('select 1');
    }
}
