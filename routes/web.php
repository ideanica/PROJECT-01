<?php

use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\CustomerApprovalController;
use App\Http\Controllers\Admin\CustomerGroupController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberOrderController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ShipmentGroupController;
use App\Http\Controllers\Admin\StatusHistoryController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\MemberTrackingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MemberTrackingController::class, 'home'])->name('home');
Route::redirect('/orders', '/profile')->name('orders.index');
Route::redirect('/billing', '/profile')->name('billing.index');
Route::get('/billing/orders', [CustomerPortalController::class, 'billingOrders'])->name('billing.orders');
Route::get('/billing/ems', [CustomerPortalController::class, 'billingEms'])->name('billing.ems');
Route::get('/services', [CustomerPortalController::class, 'services'])->name('services.index');
Route::get('/catalog', [CustomerPortalController::class, 'catalog'])->name('catalog.index');
Route::get('/orders/unpaid', [CustomerPortalController::class, 'unpaid'])->name('orders.unpaid');
Route::get('/orders/shipping', [CustomerPortalController::class, 'shipping'])->name('orders.shipping');
Route::get('/orders/refunds', [CustomerPortalController::class, 'refunds'])->name('orders.refunds');
Route::get('/order-history', [CustomerPortalController::class, 'history'])->name('orders.history');
Route::get('/tracking', [MemberTrackingController::class, 'index'])->name('tracking.index');
Route::post('/search', [MemberTrackingController::class, 'smartLookup'])->middleware('throttle:10,1')->name('tracking.search');
Route::post('/tracking', [MemberTrackingController::class, 'lookup'])->middleware('throttle:10,1')->name('tracking.lookup');
Route::get('/tracking/order/{orderCode}', [MemberTrackingController::class, 'progress'])->middleware(['signed', 'throttle:30,1'])->name('tracking.progress');
Route::post('/history', [MemberTrackingController::class, 'historyLookup'])->middleware('throttle:10,1')->name('tracking.history.lookup');
Route::get('/history/{memberCode}', [MemberTrackingController::class, 'member'])->middleware(['signed', 'throttle:30,1'])->name('tracking.member');
Route::get('/history/{memberCode}/orders/{memberOrder}', [MemberTrackingController::class, 'order'])->middleware(['signed', 'throttle:30,1'])->name('tracking.order');
Route::get('/profile', ProfileController::class)->name('profile.show');
Route::get('/profile/detail', [CustomerProfileController::class, 'detail'])->middleware('auth')->name('profile.detail');
Route::get('/profile/edit', [CustomerProfileController::class, 'edit'])->middleware('auth')->name('profile.edit');
Route::put('/profile', [CustomerProfileController::class, 'update'])->middleware('auth')->name('profile.update');
Route::put('/profile/admin', [AdminProfileController::class, 'update'])->middleware(['auth', 'can:access-admin'])->name('profile.admin.update');
Route::get('/profile/photo/{member}', [CustomerProfileController::class, 'photo'])->middleware('auth')->name('profile.photo');
Route::get('/profile/orders/{memberOrder}/payment-proof', [ProfileController::class, 'paymentProof'])->middleware('auth')->name('profile.orders.payment-proof');
Route::redirect('/auth/line', '/login');
Route::redirect('/auth/line/callback', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1,password-email')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1,password-reset')->name('password.update');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/register', [RegisteredCustomerController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredCustomerController::class, 'store'])->middleware('throttle:5,1');
    Route::redirect('/admin/login', '/login')->name('admin.login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:access-admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('batches/settings', [BatchController::class, 'settings'])->name('batches.settings');
    Route::post('batches/settings/payment-methods', [BatchController::class, 'storePaymentMethod'])->name('batches.payment-methods.store');
    Route::put('batches/settings/payment-methods/{paymentMethod}', [BatchController::class, 'updatePaymentMethod'])->name('batches.payment-methods.update');
    Route::post('batches/settings/payment-methods/{paymentMethod}/toggle', [BatchController::class, 'togglePaymentMethod'])->name('batches.payment-methods.toggle');
    Route::delete('batches/settings/payment-methods/{paymentMethod}', [BatchController::class, 'destroyPaymentMethod'])->name('batches.payment-methods.destroy');
    Route::put('batches/{batch}/ems-tax', [BatchController::class, 'updateEmsTax'])->name('batches.ems-tax');
    Route::put('batches/{batch}/orders/{member_order}/ems-payment', [BatchController::class, 'updateEmsPayment'])->name('batches.ems-payment');
    Route::post('batches/{batch}/status', [BatchController::class, 'transition'])->name('batches.status');

    Route::get('batches/{batch}/shipments', [ShipmentGroupController::class, 'index'])->name('batches.shipments.index');
    Route::post('batches/{batch}/shipments', [ShipmentGroupController::class, 'store'])->name('batches.shipments.store');
    Route::put('batches/{batch}/shipments/{shipmentGroup}', [ShipmentGroupController::class, 'update'])->name('batches.shipments.update');
    Route::delete('batches/{batch}/shipments/{shipmentGroup}', [ShipmentGroupController::class, 'destroy'])->name('batches.shipments.destroy');

    Route::resource('batches', BatchController::class);
    Route::resource('order-statuses', OrderStatusController::class);
    Route::post('members/{member}/approve', [CustomerApprovalController::class, 'store'])->name('members.approve');
    Route::resource('members', MemberController::class);
    Route::resource('customer-groups', CustomerGroupController::class);
    Route::resource('products', ProductController::class);
    Route::patch('products/{product}/status', [ProductController::class, 'updateStatus'])->name('products.status');
    Route::resource('member-orders', MemberOrderController::class);
    Route::get('member-orders/{member_order}/payment-proof', [MemberOrderController::class, 'paymentProof'])->name('member-orders.payment-proof');
    Route::post('member-orders/{member_order}/status', [MemberOrderController::class, 'transition'])->name('member-orders.status');

    Route::get('member-orders/{member_order}/payments', [OrderPaymentController::class, 'index'])->name('member-orders.payments.index');
    Route::post('member-orders/{member_order}/payments', [OrderPaymentController::class, 'store'])->name('member-orders.payments.store');
    Route::put('member-orders/{member_order}/payments/{payment}', [OrderPaymentController::class, 'update'])->name('member-orders.payments.update');
    Route::delete('member-orders/{member_order}/payments/{payment}', [OrderPaymentController::class, 'destroy'])->name('member-orders.payments.destroy');
    Route::get('member-orders/{member_order}/payments/{payment}/proof', [OrderPaymentController::class, 'proof'])->name('member-orders.payments.proof');

    Route::get('status-histories', StatusHistoryController::class)->name('status-histories.index');
});
