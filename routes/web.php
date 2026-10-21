<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\UsageRequestController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureActive;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,1')->name('login.attempt');
});

Route::middleware(['auth', EnsureActive::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ---- Data Master (lihat: semua role; ubah: admin) ----
    Route::prefix('master/{type}')->name('master.')->group(function () {
        Route::get('/', [MasterController::class, 'index'])->middleware('can:inventory.view')->name('index');
        Route::middleware('can:master.manage')->group(function () {
            Route::get('/create', [MasterController::class, 'create'])->name('create');
            Route::post('/', [MasterController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [MasterController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [MasterController::class, 'update'])->whereNumber('id')->name('update');
            Route::patch('/{id}/toggle', [MasterController::class, 'toggle'])->whereNumber('id')->name('toggle');
        });
    });

    // ---- Pembelian ----
    Route::middleware('can:purchase.view')->group(function () {
        Route::get('/pembelian', [PurchaseOrderController::class, 'index'])->name('purchases.index');
    });
    Route::middleware('can:purchase.create')->group(function () {
        Route::get('/pembelian/buat', [PurchaseOrderController::class, 'create'])->name('purchases.create');
        Route::post('/pembelian', [PurchaseOrderController::class, 'store'])->name('purchases.store');
        Route::get('/pembelian/{purchase}/edit', [PurchaseOrderController::class, 'edit'])->name('purchases.edit');
        Route::put('/pembelian/{purchase}', [PurchaseOrderController::class, 'update'])->name('purchases.update');
        Route::post('/pembelian/{purchase}/ajukan', [PurchaseOrderController::class, 'submit'])->name('purchases.submit');
        Route::post('/pembelian/{purchase}/batal', [PurchaseOrderController::class, 'cancel'])->name('purchases.cancel');
    });
    Route::middleware('can:purchase.approve')->group(function () {
        Route::post('/pembelian/{purchase}/setujui', [PurchaseOrderController::class, 'approve'])->name('purchases.approve');
        Route::post('/pembelian/{purchase}/tolak', [PurchaseOrderController::class, 'reject'])->name('purchases.reject');
    });
    Route::middleware('can:purchase.view')->group(function () {
        Route::get('/pembelian/{purchase}', [PurchaseOrderController::class, 'show'])->name('purchases.show');
        Route::get('/pembelian/{purchase}/cetak', [PurchaseOrderController::class, 'printout'])->name('purchases.print');
    });

    // ---- Penerimaan barang ----
    Route::middleware('can:purchase.view')->group(function () {
        Route::get('/penerimaan', [GoodsReceiptController::class, 'index'])->name('receipts.index');
        Route::get('/penerimaan/{receipt}', [GoodsReceiptController::class, 'show'])->whereNumber('receipt')->name('receipts.show');
    });
    Route::middleware('can:purchase.receive')->group(function () {
        Route::get('/pembelian/{purchase}/terima', [GoodsReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/pembelian/{purchase}/terima', [GoodsReceiptController::class, 'store'])->name('receipts.store');
    });

    // ---- Inventaris & mutasi ----
    Route::middleware('can:inventory.view')->group(function () {
        Route::get('/inventaris', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventaris/{item}', [InventoryController::class, 'show'])->whereNumber('item')->name('inventory.show');
        Route::get('/mutasi', [StockTransferController::class, 'index'])->name('transfers.index');
    });
    Route::middleware('can:inventory.manage')->group(function () {
        Route::post('/inventaris/{item}/penyesuaian', [InventoryController::class, 'adjust'])->whereNumber('item')->name('inventory.adjust');
        Route::get('/mutasi/buat', [StockTransferController::class, 'create'])->name('transfers.create');
        Route::post('/mutasi', [StockTransferController::class, 'store'])->name('transfers.store');
    });

    // ---- Pemakaian barang ----
    Route::middleware('can:usage.create')->group(function () {
        Route::get('/pemakaian/buat', [UsageRequestController::class, 'create'])->name('usage.create');
        Route::post('/pemakaian', [UsageRequestController::class, 'store'])->name('usage.store');
        Route::post('/pemakaian/{usage}/batal', [UsageRequestController::class, 'cancel'])->name('usage.cancel');
    });
    Route::middleware('can:usage.view')->group(function () {
        Route::get('/pemakaian', [UsageRequestController::class, 'index'])->name('usage.index');
        Route::get('/pemakaian/{usage}', [UsageRequestController::class, 'show'])->whereNumber('usage')->name('usage.show');
        Route::get('/pemakaian/{usage}/cetak', [UsageRequestController::class, 'printout'])->whereNumber('usage')->name('usage.print');
    });
    Route::middleware('can:usage.approve')->group(function () {
        Route::post('/pemakaian/{usage}/setujui', [UsageRequestController::class, 'approve'])->name('usage.approve');
        Route::post('/pemakaian/{usage}/tolak', [UsageRequestController::class, 'reject'])->name('usage.reject');
    });
    Route::middleware('can:usage.release')->group(function () {
        Route::post('/pemakaian/{usage}/proses', [UsageRequestController::class, 'process'])->name('usage.process');
        Route::post('/pemakaian/{usage}/keluarkan', [UsageRequestController::class, 'release'])->name('usage.release');
    });

    // ---- Laporan ----
    Route::get('/laporan', [ReportController::class, 'index'])->middleware('can:report.view')->name('reports.index');

    // ---- Pengguna & log ----
    Route::middleware('can:users.manage')->prefix('pengguna')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/buat', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/toggle', [UserController::class, 'toggle'])->name('toggle');
    });
    Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->middleware('can:activity.view')->name('activity.index');

    // ---- Hak akses (informasi untuk semua pengguna) ----
    Route::get('/hak-akses', [RoleController::class, 'index'])->name('roles.index');
    Route::middleware('can:roles.manage')->group(function () {
        Route::get('/hak-akses/ubah', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/hak-akses', [RoleController::class, 'update'])->name('roles.update');
        Route::post('/hak-akses/reset', [RoleController::class, 'reset'])->name('roles.reset');
    });

    // ---- Profil / pengaturan ----
    Route::get('/pengaturan', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/pengaturan/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/pengaturan/password', [ProfileController::class, 'password'])->name('profile.password');
});
