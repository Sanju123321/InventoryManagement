<?php

namespace Database\Seeders;

use App\Models\BillOfMaterial;
use App\Models\Company;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Create SuperAdmin (no company)
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@erp.com',
            'phone_number' => '00000000000',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
        ]);

        // Create demo company and admin
        $company = Company::create([
            'company_name' => 'Demo Manufacturing Co.',
            'business_type' => 'chemical',
            'phone' => '08012345678',
        ]);

        User::create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'phone_number' => '08012345678',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create sample product
        $product = Product::create([
            'company_id' => $company->id,
            'name' => 'Engine Oil Bottle (250ml)',
            'sku' => 'EO-250',
            'unit' => 'bottle',
        ]);

        // Create raw materials with unit_cost and min_stock_alert
        $oil = RawMaterial::create(['company_id' => $company->id, 'name' => '250ml Engine Oil', 'unit' => 'ml', 'stock_qty' => 50000, 'min_stock_alert' => 5000, 'unit_cost' => 0.50]);
        $bottle = RawMaterial::create(['company_id' => $company->id, 'name' => 'Plastic Bottle', 'unit' => 'pcs', 'stock_qty' => 500, 'min_stock_alert' => 50, 'unit_cost' => 15.00]);
        $label = RawMaterial::create(['company_id' => $company->id, 'name' => 'Label Sticker', 'unit' => 'pcs', 'stock_qty' => 500, 'min_stock_alert' => 50, 'unit_cost' => 5.00]);
        $seal = RawMaterial::create(['company_id' => $company->id, 'name' => 'Seal Foil', 'unit' => 'pcs', 'stock_qty' => 500, 'min_stock_alert' => 50, 'unit_cost' => 3.00]);
        $carton = RawMaterial::create(['company_id' => $company->id, 'name' => 'Carton Box (holds 12)', 'unit' => 'pcs', 'stock_qty' => 100, 'min_stock_alert' => 20, 'unit_cost' => 50.00]);

        // Create Bill of Materials
        BillOfMaterial::create(['company_id' => $company->id, 'product_id' => $product->id, 'material_id' => $oil->id, 'quantity_required' => 250]);
        BillOfMaterial::create(['company_id' => $company->id, 'product_id' => $product->id, 'material_id' => $bottle->id, 'quantity_required' => 1]);
        BillOfMaterial::create(['company_id' => $company->id, 'product_id' => $product->id, 'material_id' => $label->id, 'quantity_required' => 1]);
        BillOfMaterial::create(['company_id' => $company->id, 'product_id' => $product->id, 'material_id' => $seal->id, 'quantity_required' => 1]);
        BillOfMaterial::create(['company_id' => $company->id, 'product_id' => $product->id, 'material_id' => $carton->id, 'quantity_required' => 0.0833]);
    }
}
