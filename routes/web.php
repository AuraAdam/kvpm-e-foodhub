<?php
/**
 * All routes of the application and the access rule for each one.
 * POST routes are CSRF-checked automatically by the router.
 *
 * @var App\Core\Router $router
 */
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\HomeController;
use App\Controllers\OrderController;
use App\Controllers\ProductController;
use App\Controllers\ReviewController;
use App\Controllers\SellerApplicationController;
use App\Controllers\ShopController;
use App\Controllers\WishlistController;
use App\Controllers\Seller;
use App\Controllers\Admin;

// ---------------------------------------------------------------- Public
$router->get('/', [HomeController::class, 'index']);
$router->get('/info/{page}', [HomeController::class, 'page']);
$router->get('/products', [ProductController::class, 'index']);
$router->get('/product/{slug}', [ProductController::class, 'show']);
$router->get('/shops', [ShopController::class, 'index']);
$router->get('/shop/{slug}', [ShopController::class, 'show']);

// ---------------------------------------------------------------- Authentication
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->get('/forgot-password', [AuthController::class, 'forgot'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

// ---------------------------------------------------------------- Any signed-in user
$router->get('/account', [AccountController::class, 'profile'], ['auth']);
$router->post('/account', [AccountController::class, 'updateProfile'], ['auth']);
$router->get('/account/password', [AccountController::class, 'showPassword'], ['auth']);
$router->post('/account/password', [AccountController::class, 'updatePassword'], ['auth']);
$router->get('/notifications', [AccountController::class, 'notifications'], ['auth']);
$router->post('/notifications/read', [AccountController::class, 'markRead'], ['auth']);

// ---------------------------------------------------------------- Shoppers (customers and sellers)
$router->get('/cart', [CartController::class, 'index'], ['shopper']);
$router->post('/cart/add', [CartController::class, 'add'], ['shopper']);
$router->post('/cart/update', [CartController::class, 'update'], ['shopper']);
$router->post('/cart/remove', [CartController::class, 'remove'], ['shopper']);
$router->get('/checkout', [CheckoutController::class, 'show'], ['shopper']);
$router->post('/checkout', [CheckoutController::class, 'place'], ['shopper']);
$router->get('/orders', [OrderController::class, 'index'], ['shopper']);
$router->get('/orders/{number}', [OrderController::class, 'show'], ['shopper']);
$router->post('/orders/{number}/cancel', [OrderController::class, 'cancel'], ['shopper']);
$router->get('/orders/{number}/pay', [OrderController::class, 'payment'], ['shopper']);
$router->post('/orders/{number}/pay', [OrderController::class, 'pay'], ['shopper']);
$router->post('/reviews', [ReviewController::class, 'store'], ['shopper']);
$router->get('/account/reviews', [ReviewController::class, 'mine'], ['shopper']);
$router->get('/wishlist', [WishlistController::class, 'index'], ['shopper']);
$router->post('/wishlist/toggle', [WishlistController::class, 'toggle'], ['shopper']);
$router->post('/product/{slug}/report', [ProductController::class, 'report'], ['shopper']);
$router->get('/seller/apply', [SellerApplicationController::class, 'show'], ['shopper']);
$router->post('/seller/apply', [SellerApplicationController::class, 'submit'], ['shopper']);

// ---------------------------------------------------------------- Seller (approved shops only)
$router->get('/seller', [Seller\DashboardController::class, 'index'], ['seller']);
$router->get('/seller/products', [Seller\ProductController::class, 'index'], ['seller']);
$router->get('/seller/products/create', [Seller\ProductController::class, 'create'], ['seller']);
$router->post('/seller/products', [Seller\ProductController::class, 'store'], ['seller']);
$router->get('/seller/products/{id}/edit', [Seller\ProductController::class, 'edit'], ['seller']);
$router->post('/seller/products/{id}', [Seller\ProductController::class, 'update'], ['seller']);
$router->post('/seller/products/{id}/stock', [Seller\ProductController::class, 'stock'], ['seller']);
$router->post('/seller/products/{id}/availability', [Seller\ProductController::class, 'availability'], ['seller']);
$router->post('/seller/products/{id}/archive', [Seller\ProductController::class, 'archive'], ['seller']);
$router->post('/seller/products/{id}/images/{imageId}/delete', [Seller\ProductController::class, 'deleteImage'], ['seller']);
$router->post('/seller/products/{id}/images/{imageId}/primary', [Seller\ProductController::class, 'primaryImage'], ['seller']);
$router->get('/seller/orders', [Seller\OrderController::class, 'index'], ['seller']);
$router->get('/seller/orders/{id}', [Seller\OrderController::class, 'show'], ['seller']);
$router->post('/seller/orders/{id}/status', [Seller\OrderController::class, 'status'], ['seller']);
$router->get('/seller/reports', [Seller\DashboardController::class, 'reports'], ['seller']);
$router->get('/seller/reports/export', [Seller\DashboardController::class, 'export'], ['seller']);
$router->get('/seller/shop', [Seller\ShopController::class, 'edit'], ['seller']);
$router->post('/seller/shop', [Seller\ShopController::class, 'update'], ['seller']);

// ---------------------------------------------------------------- Administrator
$router->get('/admin', [Admin\DashboardController::class, 'index'], ['admin']);
$router->get('/admin/users', [Admin\UserController::class, 'index'], ['admin']);
$router->get('/admin/users/{id}', [Admin\UserController::class, 'show'], ['admin']);
$router->post('/admin/users/{id}/status', [Admin\UserController::class, 'status'], ['admin']);
$router->post('/admin/users/{id}/role', [Admin\UserController::class, 'role'], ['admin']);
$router->post('/admin/users/{id}/reset-password', [Admin\UserController::class, 'resetPassword'], ['admin']);
$router->get('/admin/sellers', [Admin\SellerController::class, 'index'], ['admin']);
$router->post('/admin/sellers/{id}/decision', [Admin\SellerController::class, 'decision'], ['admin']);
$router->get('/admin/products', [Admin\ProductController::class, 'index'], ['admin']);
$router->get('/admin/products/{id}', [Admin\ProductController::class, 'show'], ['admin']);
$router->post('/admin/products/{id}/moderate', [Admin\ProductController::class, 'moderate'], ['admin']);
$router->get('/admin/categories', [Admin\CategoryController::class, 'index'], ['admin']);
$router->post('/admin/categories', [Admin\CategoryController::class, 'store'], ['admin']);
$router->post('/admin/categories/{id}', [Admin\CategoryController::class, 'update'], ['admin']);
$router->post('/admin/categories/{id}/delete', [Admin\CategoryController::class, 'delete'], ['admin']);
$router->get('/admin/orders', [Admin\OrderController::class, 'index'], ['admin']);
$router->get('/admin/orders/{number}', [Admin\OrderController::class, 'show'], ['admin']);
$router->post('/admin/orders/{number}/override', [Admin\OrderController::class, 'override'], ['admin']);
$router->post('/admin/orders/{number}/note', [Admin\OrderController::class, 'note'], ['admin']);
$router->get('/admin/reviews', [Admin\ContentController::class, 'reviews'], ['admin']);
$router->post('/admin/reviews/{id}', [Admin\ContentController::class, 'moderateReview'], ['admin']);
$router->get('/admin/reports', [Admin\ContentController::class, 'reports'], ['admin']);
$router->post('/admin/reports/{id}', [Admin\ContentController::class, 'resolveReport'], ['admin']);
$router->get('/admin/announcements', [Admin\ContentController::class, 'announcements'], ['admin']);
$router->post('/admin/announcements', [Admin\ContentController::class, 'saveAnnouncement'], ['admin']);
$router->post('/admin/announcements/{id}/delete', [Admin\ContentController::class, 'deleteAnnouncement'], ['admin']);
$router->get('/admin/settings', [Admin\ContentController::class, 'settings'], ['admin']);
$router->post('/admin/settings', [Admin\ContentController::class, 'saveSettings'], ['admin']);
$router->get('/admin/audit', [Admin\DashboardController::class, 'audit'], ['admin']);
