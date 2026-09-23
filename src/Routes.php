<?php

namespace Bazaar;

use Bazaar\Controllers\AccountController;
use Bazaar\Controllers\Admin\AnalyticsController;
use Bazaar\Controllers\Admin\AttributeController as AdminAttributeController;
use Bazaar\Controllers\Admin\CategoryController as AdminCategoryController;
use Bazaar\Controllers\Admin\CouponController as AdminCouponController;
use Bazaar\Controllers\Admin\DashboardController;
use Bazaar\Controllers\Admin\OrderController as AdminOrderController;
use Bazaar\Controllers\Admin\ProductController as AdminProductController;
use Bazaar\Controllers\Admin\ReviewController as AdminReviewController;
use Bazaar\Controllers\Admin\UserController as AdminUserController;
use Bazaar\Controllers\Admin\VariantController as AdminVariantController;
use Bazaar\Controllers\AuthController;
use Bazaar\Controllers\CartController;
use Bazaar\Controllers\CatalogController;
use Bazaar\Controllers\CheckoutController;
use Bazaar\Controllers\HomeController;
use Bazaar\Controllers\LocaleController;
use Bazaar\Controllers\ReviewController;
use Bazaar\Controllers\WishlistController;
use Bazaar\Core\Router;

final class Routes
{
    public static function define(Router $router): void
    {
        $router->get('/', [HomeController::class, 'index']);
        $router->get('/products', [CatalogController::class, 'index']);
        $router->get('/products/{slug}', [CatalogController::class, 'show']);

        $router->get('/locale/{locale}', [LocaleController::class, 'set']);

        $router->get('/cart', [CartController::class, 'index']);
        $router->post('/cart/add', [CartController::class, 'add']);
        $router->post('/cart/update', [CartController::class, 'update']);
        $router->post('/cart/remove', [CartController::class, 'remove']);
        $router->post('/cart/coupon', [CartController::class, 'applyCoupon']);
        $router->post('/cart/coupon/remove', [CartController::class, 'removeCoupon']);

        $router->post('/wishlist/toggle', [WishlistController::class, 'toggle'], ['auth']);
        $router->get('/account/wishlist', [WishlistController::class, 'index'], ['auth']);

        $router->post('/reviews', [ReviewController::class, 'store'], ['auth']);

        $router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
        $router->post('/register', [AuthController::class, 'register'], ['guest']);
        $router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
        $router->post('/login', [AuthController::class, 'login'], ['guest']);
        $router->post('/logout', [AuthController::class, 'logout'], ['auth']);

        $router->get('/account/orders', [AccountController::class, 'orders'], ['auth']);
        $router->get('/account/orders/{id}', [AccountController::class, 'show'], ['auth']);
        $router->post('/account/orders/{id}/cancel', [AccountController::class, 'cancel'], ['auth']);

        $router->get('/checkout', [CheckoutController::class, 'index'], ['auth']);
        $router->post('/checkout', [CheckoutController::class, 'create'], ['auth']);
        $router->get('/checkout/success', [CheckoutController::class, 'success'], ['auth']);
        $router->get('/checkout/cancel', [CheckoutController::class, 'cancel'], ['auth']);
        $router->post('/webhook/stripe', [CheckoutController::class, 'webhook'], ['webhook']);

        $router->get('/admin', [DashboardController::class, 'index'], ['staff']);
        $router->get('/admin/analytics', [AnalyticsController::class, 'index'], ['staff']);

        $router->get('/admin/products', [AdminProductController::class, 'index'], ['staff']);
        $router->get('/admin/products/create', [AdminProductController::class, 'create'], ['staff']);
        $router->post('/admin/products', [AdminProductController::class, 'store'], ['staff']);
        $router->get('/admin/products/{id}/edit', [AdminProductController::class, 'edit'], ['staff']);
        $router->post('/admin/products/{id}', [AdminProductController::class, 'update'], ['staff']);
        $router->post('/admin/products/{id}/delete', [AdminProductController::class, 'destroy'], ['staff']);

        $router->get('/admin/products/{id}/variants', [AdminVariantController::class, 'index'], ['staff']);
        $router->post('/admin/products/{id}/variants', [AdminVariantController::class, 'store'], ['staff']);
        $router->post('/admin/variants/{id}/delete', [AdminVariantController::class, 'destroy'], ['staff']);

        $router->get('/admin/attributes', [AdminAttributeController::class, 'index'], ['staff']);
        $router->post('/admin/attributes', [AdminAttributeController::class, 'store'], ['staff']);
        $router->post('/admin/attributes/{id}/values', [AdminAttributeController::class, 'storeValue'], ['staff']);

        $router->get('/admin/categories', [AdminCategoryController::class, 'index'], ['staff']);
        $router->get('/admin/categories/create', [AdminCategoryController::class, 'create'], ['staff']);
        $router->post('/admin/categories', [AdminCategoryController::class, 'store'], ['staff']);
        $router->get('/admin/categories/{id}/edit', [AdminCategoryController::class, 'edit'], ['staff']);
        $router->post('/admin/categories/{id}', [AdminCategoryController::class, 'update'], ['staff']);
        $router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'destroy'], ['staff']);

        $router->get('/admin/orders', [AdminOrderController::class, 'index'], ['staff']);
        $router->get('/admin/orders/{id}', [AdminOrderController::class, 'show'], ['staff']);
        $router->post('/admin/orders/{id}/fulfill', [AdminOrderController::class, 'fulfill'], ['staff']);
        $router->post('/admin/orders/{id}/cancel', [AdminOrderController::class, 'cancel'], ['staff']);

        $router->get('/admin/reviews', [AdminReviewController::class, 'index'], ['staff']);
        $router->post('/admin/reviews/{id}/approve', [AdminReviewController::class, 'approve'], ['staff']);
        $router->post('/admin/reviews/{id}/reject', [AdminReviewController::class, 'reject'], ['staff']);

        $router->get('/admin/coupons', [AdminCouponController::class, 'index'], ['admin']);
        $router->get('/admin/coupons/create', [AdminCouponController::class, 'create'], ['admin']);
        $router->post('/admin/coupons', [AdminCouponController::class, 'store'], ['admin']);
        $router->post('/admin/coupons/{id}/delete', [AdminCouponController::class, 'destroy'], ['admin']);

        $router->get('/admin/users', [AdminUserController::class, 'index'], ['admin']);
        $router->post('/admin/users/{id}/role', [AdminUserController::class, 'updateRole'], ['admin']);
    }
}
