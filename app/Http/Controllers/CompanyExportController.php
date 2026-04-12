<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\BillOfMaterial;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductionLog;
use App\Models\RawMaterial;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\ActivityLogService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyExportController extends Controller
{
    public function export(Company $company): StreamedResponse
    {
        $id       = $company->id;
        $filename = 'company_' . preg_replace('/[^a-z0-9_]/i', '_', $company->company_name) . '_export.csv';
        ActivityLogService::log('company.exported', "Company '{$company->company_name}' data exported.", null, null);
        $datasets = [
            'PRODUCTS'               => $this->products($id),
            'RAW MATERIALS'          => $this->rawMaterials($id),
            'BILL OF MATERIALS'      => $this->bom($id),
            'PRODUCTION LOGS'        => $this->productionLogs($id),
            'CUSTOMERS'              => $this->customers($id),
            'SALES ORDERS'           => $this->salesOrders($id),
            'SALES ORDER ITEMS'      => $this->salesOrderItems($id),
            'PAYMENTS'               => $this->payments($id),
            'INVENTORY TRANSACTIONS' => $this->inventoryTransactions($id),
        ];
        $response = new StreamedResponse(function () use ($datasets, $company) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Company Export: ' . $company->company_name, 'Generated: ' . now()->toDateTimeString()]);
            fputcsv($handle, []);
            foreach ($datasets as $section => $dataset) {
                fputcsv($handle, ['--- ' . $section . ' ---']);
                fputcsv($handle, $dataset['headers']);
                foreach ($dataset['data'] as $row) {
                    fputcsv($handle, array_map(fn($v) => $v instanceof \Carbon\Carbon ? $v->toDateTimeString() : (string) ($v ?? ''), $row));
                }
                fputcsv($handle, []);
            }
            fclose($handle);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }
    private function products(int $id): array
    {
        $rows = Product::where('company_id', $id)->get();
        return ['headers' => ['id','name','sku','unit','custom_unit','created_at'], 'data' => $rows->map(fn($r) => [$r->id,$r->name,$r->sku,$r->unit,$r->custom_unit,$r->created_at])->toArray()];
    }
    private function rawMaterials(int $id): array
    {
        $rows = RawMaterial::where('company_id', $id)->get();
        return ['headers' => ['id','name','unit','custom_unit','stock_qty','min_stock_alert','unit_cost','created_at'], 'data' => $rows->map(fn($r) => [$r->id,$r->name,$r->unit,$r->custom_unit,$r->stock_qty,$r->min_stock_alert,$r->unit_cost,$r->created_at])->toArray()];
    }
    private function bom(int $id): array
    {
        $rows = BillOfMaterial::where('company_id', $id)->get();
        return ['headers' => ['id','product_id','material_id','quantity_required','created_at'], 'data' => $rows->map(fn($r) => [$r->id,$r->product_id,$r->material_id,$r->quantity_required,$r->created_at])->toArray()];
    }
    private function productionLogs(int $id): array
    {
        $rows = ProductionLog::where('company_id', $id)->get();
        return ['headers' => ['id','product_id','quantity_produced','production_date','created_at'], 'data' => $rows->map(fn($r) => [$r->id,$r->product_id,$r->quantity_produced,$r->production_date,$r->created_at])->toArray()];
    }
    private function customers(int $id): array
    {
        $rows = Customer::where('company_id', $id)->get();
        return ['headers' => ['id','name','email','phone','address','created_at'], 'data' => $rows->map(fn($r) => [$r->id,$r->name,$r->email,$r->phone,$r->address,$r->created_at])->toArray()];
    }
    private function salesOrders(int $id): array
    {
        $rows = SalesOrder::where('company_id', $id)->get();
        return ['headers' => ['id','customer_id','total_amount','paid_amount','pending_amount','status','created_by','approved_by','created_at'], 'data' => $rows->map(fn($r) => [$r->id,$r->customer_id,$r->total_amount,$r->paid_amount,$r->pending_amount,$r->status,$r->created_by,$r->approved_by,$r->created_at])->toArray()];
    }
    private function salesOrderItems(int $id): array
    {
        $rows = SalesOrderItem::whereHas('salesOrder', fn($q) => $q->where('company_id', $id))->get();
        return ['headers' => ['id','sales_order_id','product_id','quantity','price','total'], 'data' => $rows->map(fn($r) => [$r->id,$r->sales_order_id,$r->product_id,$r->quantity,$r->price,$r->total])->toArray()];
    }
    private function payments(int $id): array
    {
        $rows = Payment::whereHas('salesOrder', fn($q) => $q->where('company_id', $id))->get();
        return ['headers' => ['id','sales_order_id','amount','payment_method','note','paid_at'], 'data' => $rows->map(fn($r) => [$r->id,$r->sales_order_id,$r->amount,$r->payment_method,$r->note,$r->created_at])->toArray()];
    }
    private function inventoryTransactions(int $id): array
    {
        $rows = InventoryTransaction::where('company_id', $id)->get();
        return ['headers' => ['id','material_id','type','quantity','stock_after','note','created_at'], 'data' => $rows->map(fn($r) => [$r->id,$r->material_id,$r->type,$r->quantity,$r->stock_after,$r->note??'',$r->created_at])->toArray()];
    }
}