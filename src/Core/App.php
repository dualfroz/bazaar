<?php

namespace Bazaar\Core;

use Bazaar\Mail\LogMailer;
use Bazaar\Repositories\AttributeRepository;
use Bazaar\Repositories\CategoryRepository;
use Bazaar\Repositories\CouponRepository;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\ReviewRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Bazaar\Repositories\WishlistRepository;
use Bazaar\Routes;
use Bazaar\Services\Analytics;
use Bazaar\Services\Auth;
use Bazaar\Services\Cart;
use Bazaar\Services\CouponService;
use Bazaar\Services\OrderService;
use Bazaar\Services\ProductSearch;
use Bazaar\Services\ReviewService;
use Bazaar\Support\Translator;

final class App
{
    private Router $router;
    private Context $context;

    public function boot(): void
    {
        Config::load(basePath('.env'));

        $session = new Session();
        $session->start();

        $database = Database::instance();
        $users = new UserRepository($database);
        $categories = new CategoryRepository($database);
        $products = new ProductRepository($database);
        $orders = new OrderRepository($database);
        $attributes = new AttributeRepository($database);
        $variants = new VariantRepository($database);
        $coupons = new CouponRepository($database);
        $reviews = new ReviewRepository($database);
        $wishlist = new WishlistRepository($database);

        $auth = new Auth($session, $users);
        $cart = new Cart($session, $products, $variants);
        $mailer = new LogMailer(basePath('storage/logs/mail.log'));
        $orderService = new OrderService($database, $orders, $products, $variants, $coupons, $mailer);
        $couponService = new CouponService($coupons);
        $reviewService = new ReviewService($database, $reviews);
        $productSearch = new ProductSearch($database, $attributes, $variants, $reviews, $categories);
        $analytics = new Analytics($database);
        $csrf = new Csrf($session);
        $view = new View(basePath('views'));

        $request = Request::capture();
        $translator = new Translator(basePath('resources/lang'), $this->resolveLocale($request, $session));

        $this->context = new Context(
            $request,
            $session,
            $csrf,
            $view,
            $auth,
            $cart,
            $orderService,
            $users,
            $categories,
            $products,
            $orders,
            $attributes,
            $variants,
            $coupons,
            $reviews,
            $wishlist,
            $couponService,
            $reviewService,
            $productSearch,
            $analytics,
            $translator
        );

        $view->share('view', $view);
        $view->share('currentUser', $auth->user());
        $view->share('isAdmin', $auth->isAdmin());
        $view->share('isStaff', $auth->isStaff());
        $view->share('can', fn (string $capability): bool => $auth->can($capability));
        $view->share('csrfToken', $csrf->token());
        $view->share('cartCount', $cart->count());
        $view->share('wishlistCount', $auth->check() ? $wishlist->count((int) $auth->id()) : 0);
        $view->share('navCategories', $categories->all());
        $view->share('flashes', $session->pullFlash());
        $view->share('appName', 'Bazaar');
        $view->share('currency', (string) Config::get('CURRENCY', 'usd'));
        $view->share('stripeConfigured', Config::stripeConfigured());
        $view->share('locale', $translator->locale());
        $view->share('supportedLocales', Translator::supported);
        $view->share('t', fn (string $key, array $replacements = []): string => $translator->get($key, $replacements));
        $view->share('couponCode', (string) $session->get('couponCode', ''));

        $this->router = new Router();
        Routes::define($this->router);
    }

    private function resolveLocale(Request $request, Session $session): string
    {
        $requested = (string) $request->query('lang', '');
        if ($requested !== '' && Translator::isSupported($requested)) {
            $session->put('locale', $requested);
        }
        $locale = (string) $session->get('locale', 'en');
        return Translator::isSupported($locale) ? $locale : 'en';
    }

    public function run(): void
    {
        $this->dispatch()->send();
    }

    private function dispatch(): Response
    {
        $request = $this->context->request;
        $match = $this->router->match($request);

        if (!$match->found()) {
            return $this->context->view->render('errors.404', [], 'layout', $match->status === 405 ? 405 : 404);
        }

        if ($request->isPost() && !in_array('webhook', $match->middleware, true)) {
            if (!$this->context->csrf->verify((string) $request->input('csrfToken', ''))) {
                return $this->context->view->render('errors.419', [], 'layout', 419);
            }
        }

        $guard = $this->guard($match->middleware);
        if ($guard !== null) {
            return $guard;
        }

        [$class, $method] = $match->handler;
        $controller = new $class($this->context);
        return $controller->{$method}(...array_values($match->params));
    }

    private function guard(array $middleware): ?Response
    {
        $auth = $this->context->auth;

        if (in_array('guest', $middleware, true) && $auth->check()) {
            return Response::redirect('/');
        }

        if (in_array('auth', $middleware, true) && !$auth->check()) {
            $this->context->session->flash('error', 'Please sign in to continue.');
            return Response::redirect('/login');
        }

        if (in_array('staff', $middleware, true)) {
            if (!$auth->check()) {
                return Response::redirect('/login');
            }
            if (!$auth->isStaff()) {
                return $this->context->view->render('errors.403', [], 'layout', 403);
            }
        }

        if (in_array('admin', $middleware, true)) {
            if (!$auth->check()) {
                return Response::redirect('/login');
            }
            if (!$auth->isAdmin()) {
                return $this->context->view->render('errors.403', [], 'layout', 403);
            }
        }

        return null;
    }
}
