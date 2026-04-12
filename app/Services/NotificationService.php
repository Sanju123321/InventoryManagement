<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\RawMaterial;
use App\Models\SalesOrder;
use App\Models\User;
use App\Mail\NewOrderMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles in-app + FCM push notifications for stock alerts and order events.
 *
 * Each public method:
 *  1. Persists the notification to `app_notifications` table.
 *  2. Pushes a FCM notification to all company users who have an FCM token.
 */
class NotificationService
{
    // No constructor injection — FcmService is resolved lazily so its
    // missing-credentials exception never prevents DB notifications from saving.

    /**
     * Notify all admins in the company when a sales admin places a new order.
     * Sends an in-app notification + email to every admin.
     */
    public function notifyNewOrder(SalesOrder $order): void
    {
        $companyId   = $order->company_id;
        $creatorName = $order->creator->name ?? 'A sales admin';
        $title   = '🛒 New Order #' . $order->id . ' Pending Approval';
        $message = "{$creatorName} placed order #{$order->id} for {$order->customer->name}. "
            . 'Total: ₹' . number_format($order->total_amount, 2) . '. Awaiting your approval.';

        $this->store($companyId, 'new_order', $title, $message, [
            'order_id'     => (string) $order->id,
            'customer'     => $order->customer->name,
            'total_amount' => (string) $order->total_amount,
            'created_by'   => $creatorName,
            'url'          => '/sales/orders/' . $order->id,
        ]);

        $this->pushToCompany($companyId, $title, $message);

        // Send email to all admins of this company
        $admins = User::where('company_id', $companyId)
            ->whereIn('role', ['admin', 'superadmin'])
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send(new NewOrderMail($order));
            } catch (\Throwable $e) {
                Log::error('NewOrderMail failed for ' . $admin->email . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Notify when a raw material's stock falls at or below its alert threshold.
     */
    public function notifyLowStockMaterial(RawMaterial $material, int $companyId): void
    {
        $title   = '⚠ Low Stock Alert: ' . $material->name;
        $message = "Stock for \"{$material->name}\" is low. "
            . "Current: {$material->stock_qty} {$material->unit}, "
            . "Alert level: {$material->min_stock_alert} {$material->unit}.";

        $this->store($companyId, 'low_stock_material', $title, $message, [
            'material_id'     => $material->id,
            'material_name'   => $material->name,
            'stock_qty'       => (string) $material->stock_qty,
            'min_stock_alert' => (string) $material->min_stock_alert,
            'unit'            => $material->unit,
            'url'             => '/materials',
        ]);

        $this->pushToCompany($companyId, $title, $message);
    }

    /**
     * Notify when a sales order is created but product stock is getting low.
     *
     * @param  array  $lowItems  Each: ['name' => string, 'available' => int, 'ordered' => int]
     */
    public function notifyLowStockProduct(int $companyId, int $orderId, array $lowItems): void
    {
        $names   = implode(', ', array_column($lowItems, 'name'));
        $title   = '📦 Low Product Stock: Order #' . $orderId;
        $message = "Order #{$orderId} was created but the following products have low remaining stock: {$names}.";

        $this->store($companyId, 'low_stock_product', $title, $message, [
            'order_id'  => (string) $orderId,
            'low_items' => $lowItems,
            'url'       => '/sales/orders/' . $orderId,
        ]);

        $this->pushToCompany($companyId, $title, $message);
    }

    /**
     * Notify when a sales order cannot be approved due to insufficient product stock.
     */
    public function notifyOrderInsufficientStock(int $companyId, int $orderId, string $productName, int $available, int $required): void
    {
        $title   = '🚫 Insufficient Stock: Order #' . $orderId;
        $message = "Order #{$orderId} cannot be approved. "
            . "Product \"{$productName}\" — Available: {$available}, Required: {$required}.";

        $this->store($companyId, 'order_low_stock', $title, $message, [
            'order_id'     => (string) $orderId,
            'product_name' => $productName,
            'available'    => (string) $available,
            'required'     => (string) $required,
            'url'          => '/sales/orders/' . $orderId,
        ]);

        $this->pushToCompany($companyId, $title, $message);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function store(
        int    $companyId,
        string $type,
        string $title,
        string $message,
        array  $data = []
    ): AppNotification {
        return AppNotification::create([
            'company_id' => $companyId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'data'       => $data,
            'is_read'    => false,
        ]);
    }

    private function pushToCompany(int $companyId, string $title, string $body): void
    {
        try {
            $tokens = User::where('company_id', $companyId)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->toArray();

            if (! empty($tokens)) {
                // Resolve FcmService lazily — if service account is missing it throws here,
                // not at NotificationService construction, so store() is unaffected.
                app(FcmService::class)->sendToMultiple($tokens, $title, $body);
            }
        } catch (\Throwable $e) {
            // Never let FCM failure break the main request flow
            Log::error('NotificationService: FCM push failed — ' . $e->getMessage());
        }
    }
}
