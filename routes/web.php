<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OrderExportController as AdminOrderExportController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController as AdminProductImageController;
use App\Http\Controllers\Admin\ProductModelController as AdminProductModelController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UserLogController as AdminUserLogController;
use App\Http\Controllers\Admin\VisitController as AdminVisitController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\OrderConfirmationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// ---------- Shop (everyone) ----------

// For search engines: robots.txt (what may be crawled) and sitemap.xml (every page worth indexing), see SeoController.
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

// Page of one promotion: banner, countdown and every product it covers.
Route::get('/promo/{promotion:slug}', [PromotionController::class, 'show'])->name('promotions.show');

// Help page: how ordering works, returns & exchanges, promotion and coupon rules.
Route::view('/how-to-order', 'pages.main.how-to-order')->name('how-to-order');

// Cart lives in the session, so guests can use it too.
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
// Contents of the cart drawer (side panel opened from the bag icon and after "Add to cart"), as JSON.
Route::get('/cart/drawer', [CartController::class, 'drawer'])->name('cart.drawer');
Route::patch('/cart/{key}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{key}', [CartController::class, 'destroy'])->name('cart.destroy');

// Guests can order too.
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
// honeypot: rejects bots (see HoneypotMiddleware). Max 3 orders per minute is checked in CheckoutController::store().
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('honeypot');
// "Apply" coupon button. throttle: max 10 requests per minute (wrong codes are limited separately, see Coupons service).
Route::post('/checkout/coupon', [CheckoutController::class, 'coupon'])->name('checkout.coupon')->middleware('throttle:10,1');
Route::get('/checkout/thank-you', [CheckoutController::class, 'success'])->name('checkout.success');

// Link from the "confirm your order" email. "signed" = the link can't be changed and it expires.
// throttle: max 5 code attempts per minute, so the 6-digit code can't be guessed.
Route::get('/orders/{order}/confirm', [OrderConfirmationController::class, 'show'])->name('orders.confirm')->middleware('signed');
Route::post('/orders/{order}/confirm', [OrderConfirmationController::class, 'store'])->name('orders.confirm.store')->middleware(['signed', 'throttle:5,1']);

// ---------- Only for guests (not logged in) ----------

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.store')->middleware('honeypot');

    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.auth')->middleware('honeypot');

    // Forgot password: a 6-digit code is mailed, then entered with the new password (see PasswordResetController).
    // throttle: max 5 code requests per 10 minutes and 10 reset tries per minute per IP (wrong codes are limited per email too).
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->name('password.email')->middleware(['honeypot', 'throttle:5,10']);
    Route::get('/reset-password', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update')->middleware(['honeypot', 'throttle:10,1']);
});

// ---------- Only for logged-in users ----------

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/account/orders/{order}', [AccountController::class, 'showOrder'])->name('account.orders.show');
    Route::get('/account/profile', [AccountController::class, 'editProfile'])->name('account.profile');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');

    // Customer support form. honeypot: rejects bots. Max 3 messages per 10 minutes is checked in ContactController::store().
    Route::get('/contact', [ContactController::class, 'create'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('honeypot');
});

// ---------- Admin panel (role: admin) ----------

Route::middleware(['auth', 'role:admin', 'admin.locale'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics');
    // Every request to the site (kept PageView::KEEP_DAYS days), for spotting abuse and problems.
    Route::get('/visits', [AdminVisitController::class, 'index'])->name('visits.index');

    Route::resource('products', AdminProductController::class)->except('show');
    Route::delete('/products/{product}/images/{image}', [AdminProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::patch('/products/{product}/images/{image}/main', [AdminProductImageController::class, 'makeMain'])->name('products.images.main');

    Route::resource('brands', AdminBrandController::class)->except('show');
    Route::resource('models', AdminProductModelController::class)->except('show');
    Route::resource('categories', AdminCategoryController::class)->except('show');

    Route::resource('promotions', AdminPromotionController::class)->except('show');
    Route::resource('coupons', AdminCouponController::class)->except('show');
    Route::resource('announcements', AdminAnnouncementController::class)->except('show');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    // Excel / text download of the orders from a chosen period (before orders/{order}, so "export" isn't read as an order).
    Route::get('/orders/export', AdminOrderExportController::class)->name('orders.export');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->name('users.deactivate');
    // withTrashed(): the user to activate is a deactivated (soft-deleted) one.
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate')->withTrashed();

    Route::get('/logs', [AdminUserLogController::class, 'index'])->name('logs.index');

    // Contact page messages.
    Route::get('/messages', [AdminContactMessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [AdminContactMessageController::class, 'show'])->name('messages.show');
    Route::delete('/messages/{message}', [AdminContactMessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
});

// Any other address: our 404 page (resources/views/errors/404.blade.php). Through this route (instead of
// Laravel's own "no route found") the page runs with the session, so the cart count and login show correctly.
Route::fallback(fn () => abort(404));
