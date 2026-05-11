<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\ProductionLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SalesDashboardController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductCostController;
use App\Http\Controllers\Api\FcmController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AppNotificationController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SuperAdminAnalyticsController;
use App\Http\Controllers\CompanyAnalyticsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CompanyExportController;
use App\Http\Controllers\SystemHealthController;

// Auth routes
Route::get('/', fn() => redirect('/login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);

// ── Public SEO routes ────────────────────────────────────────────────────
Route::get('/sitemap.xml', function () {
    $content = file_get_contents(public_path('sitemap.xml'));
    return response($content, 200)->header('Content-Type', 'application/xml');
});

// ── Shared authenticated routes (all company roles) ─────────────────────
Route::middleware(['auth.admin', 'check.status'])->group(function () {

    // Dashboard — all roles
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Profile settings — all roles
    Route::get('/profile/settings', [ProfileController::class, 'show'])->name('profile.settings');
    Route::put('/profile/settings', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Stop impersonation — available to any authenticated user who is being impersonated
    Route::post('/impersonate/stop', [ImpersonateController::class, 'stop'])->name('impersonate.stop');

    // Notifications — all roles
    Route::get('/notifications', [AppNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [AppNotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/mark-all-read', [AppNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::patch('/notifications/{notification}/read', [AppNotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [AppNotificationController::class, 'destroy'])->name('notifications.destroy');

    // FCM token saving — all roles
    Route::post('/api/save-token', [FcmController::class, 'saveToken'])->name('fcm.save-token');

    // Activity Log — admin only
    Route::middleware('check.role:admin')->group(function () {
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    });

    // ── Inventory routes (admin + inventory_admin) ────────────────────────
    Route::middleware('check.role:admin,inventory_admin')->group(function () {
        Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
        Route::resource('/products', ProductController::class);
        Route::resource('/materials', RawMaterialController::class)->parameters(['materials' => 'rawMaterial']);
        Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
        Route::resource('/inventory', InventoryController::class);
        Route::resource('/production', ProductionLogController::class)->parameters(['production' => 'productionLog']);
        Route::resource('/bom', BomController::class);
        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/{report}', [ReportController::class, 'show']);
        Route::get('/tables', [PagesController::class, 'tables']);
        Route::get('/charts', [PagesController::class, 'charts']);
    });

    // ── Sales routes (admin + sales_admin) ──────────────────────────────
    Route::middleware('check.role:admin,sales_admin')->group(function () {
        Route::get('/sales/dashboard', [SalesDashboardController::class, 'index']);
        Route::resource('/customers', CustomerController::class);
        Route::post('/customers/{customer}/payments', [CustomerController::class, 'storePayment'])->name('customers.payments.store');
        Route::get('/customers/{customer}/ledger-export', [CustomerController::class, 'exportLedger'])->name('customers.ledger.export');

        Route::get('/sales/orders', [SalesOrderController::class, 'index']);
        Route::get('/sales/orders/export', [SalesOrderController::class, 'export'])->name('sales.orders.export');
        Route::get('/sales/orders/recent-items', [SalesOrderController::class, 'recentItems'])->name('sales.orders.recent-items');
        Route::get('/sales/orders/create', [SalesOrderController::class, 'create']);
        Route::post('/sales/orders', [SalesOrderController::class, 'store']);
        Route::get('/sales/orders/{order}', [SalesOrderController::class, 'show']);

        Route::post('/sales/orders/{order}/payments', [PaymentController::class, 'store']);

        Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    });

    // ── Admin-only sales routes (product pricing & reports) ─────────────
    Route::middleware('check.role:admin')->group(function () {
        Route::get('/sales/product-costs', [ProductCostController::class, 'index']);
        Route::get('/sales/product-costs/export', [ProductCostController::class, 'export'])->name('sales.product-costs.export');
        Route::get('/sales/product-costs/create', [ProductCostController::class, 'create']);
        Route::post('/sales/product-costs', [ProductCostController::class, 'store']);
        Route::get('/sales/product-costs/{productCost}/edit', [ProductCostController::class, 'edit']);
        Route::put('/sales/product-costs/{productCost}', [ProductCostController::class, 'update']);
        Route::get('/sales/reports/products', [ProductCostController::class, 'report']);

        Route::get('/sales/orders/{order}/edit', [SalesOrderController::class, 'edit'])->name('sales.orders.edit');
        Route::put('/sales/orders/{order}', [SalesOrderController::class, 'update'])->name('sales.orders.update');

        // Order approval — admin only
        Route::patch('/sales/orders/{order}/approve', [SalesOrderController::class, 'approve']);
        Route::patch('/sales/orders/{order}/reject', [SalesOrderController::class, 'reject']);
        Route::patch('/sales/orders/{order}/deliver', [SalesOrderController::class, 'markDelivered']);
        Route::patch('/sales/orders/{order}/notes', [SalesOrderController::class, 'updateNotes']);
        Route::patch('/sales/orders/{order}/driver', [SalesOrderController::class, 'updateDriver']);
    });

    // ── Admin-only routes ────────────────────────────────────────────────
    Route::middleware('check.role:admin')->group(function () {
        // User & role management
        Route::get('/users', [UserRoleController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserRoleController::class, 'create'])->name('users.create');
        Route::post('/users', [UserRoleController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserRoleController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserRoleController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserRoleController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/reset-password', [UserRoleController::class, 'resetPassword'])->name('users.reset-password');

        // FCM manual broadcast (admin only)
        Route::post('/notify/user/{user}', [NotificationController::class, 'notifyUser'])->name('notify.user');
        Route::post('/notify/broadcast', [NotificationController::class, 'broadcast'])->name('notify.broadcast');

        // Company Analytics
        Route::get('/analytics', [CompanyAnalyticsController::class, 'index'])->name('analytics.index');
    });

});

// ── SuperAdmin routes ────────────────────────────────────────────────────
Route::middleware(['auth.admin', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);

    // Companies CRUD
    Route::get('/companies', [SuperAdminController::class, 'companies'])->name('superadmin.companies');
    Route::get('/companies/create', [SuperAdminController::class, 'companyCreate'])->name('superadmin.companies.create');
    Route::post('/companies', [SuperAdminController::class, 'companyStore'])->name('superadmin.companies.store');
    Route::get('/companies/{company}/edit', [SuperAdminController::class, 'companyEdit'])->name('superadmin.companies.edit');
    Route::put('/companies/{company}', [SuperAdminController::class, 'companyUpdate'])->name('superadmin.companies.update');
    Route::delete('/companies/{company}', [SuperAdminController::class, 'companyDestroy'])->name('superadmin.companies.destroy');
    Route::get('/companies/{company}', [SuperAdminController::class, 'companyDetail'])->name('superadmin.companies.show');
    Route::patch('/companies/{company}/toggle-status', [SuperAdminController::class, 'toggleCompanyStatus']);

    Route::get('/users', [SuperAdminController::class, 'users'])->name('superadmin.users');
    Route::get('/users/create', [SuperAdminController::class, 'userCreate'])->name('superadmin.users.create');
    Route::post('/users', [SuperAdminController::class, 'userStore'])->name('superadmin.users.store');
    Route::get('/users/{user}/edit', [SuperAdminController::class, 'userEdit'])->name('superadmin.users.edit');
    Route::put('/users/{user}', [SuperAdminController::class, 'userUpdate'])->name('superadmin.users.update');
    Route::delete('/users/{user}', [SuperAdminController::class, 'userDestroy'])->name('superadmin.users.destroy');
    Route::patch('/users/{user}/toggle-status', [SuperAdminController::class, 'toggleUserStatus']);

    // Impersonation
    Route::post('/impersonate/{company}', [ImpersonateController::class, 'start'])->name('superadmin.impersonate.start');

    // Activity Log
    Route::get('/activity-log', [ActivityLogController::class, 'superadminIndex'])->name('superadmin.activity-log');

    // Cross-Company Analytics
    Route::get('/analytics', [SuperAdminAnalyticsController::class, 'index'])->name('superadmin.analytics');

    // Broadcast Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('superadmin.announcements');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('superadmin.announcements.store');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('superadmin.announcements.destroy');

    // Data Export
    Route::get('/companies/{company}/export', [CompanyExportController::class, 'export'])->name('superadmin.companies.export');

    // System Health
    Route::get('/health', [SystemHealthController::class, 'index'])->name('superadmin.health');
    Route::post('/health/clear-failed-jobs', [SystemHealthController::class, 'clearFailedJobs'])->name('superadmin.health.clear-failed');
    Route::post('/health/clear-cache', [SystemHealthController::class, 'clearCache'])->name('superadmin.health.clear-cache');
    Route::post('/health/optimize', [SystemHealthController::class, 'optimizeAll'])->name('superadmin.health.optimize');
});

