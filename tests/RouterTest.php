<?php

namespace Bazaar\Tests;

use Bazaar\Core\Request;
use Bazaar\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function request(string $method, string $path): Request
    {
        return new Request($method, $path, [], [], []);
    }

    public function testMatchesStaticRoute(): void
    {
        $router = new Router();
        $router->get('/products', ['CatalogController', 'index']);

        $match = $router->match($this->request('GET', '/products'));

        $this->assertTrue($match->found());
        $this->assertSame(['CatalogController', 'index'], $match->handler);
        $this->assertSame([], $match->params);
    }

    public function testExtractsNamedParameters(): void
    {
        $router = new Router();
        $router->get('/products/{slug}', ['CatalogController', 'show']);

        $match = $router->match($this->request('GET', '/products/walnut-cutting-board'));

        $this->assertTrue($match->found());
        $this->assertSame(['slug' => 'walnut-cutting-board'], $match->params);
    }

    public function testDoesNotBleedAcrossSlashes(): void
    {
        $router = new Router();
        $router->get('/products/{slug}', ['CatalogController', 'show']);

        $match = $router->match($this->request('GET', '/products/a/b'));

        $this->assertFalse($match->found());
        $this->assertSame(404, $match->status);
    }

    public function testMethodMismatchYields405(): void
    {
        $router = new Router();
        $router->get('/checkout', ['CheckoutController', 'index']);

        $match = $router->match($this->request('POST', '/checkout'));

        $this->assertFalse($match->found());
        $this->assertSame(405, $match->status);
    }

    public function testUnknownPathYields404(): void
    {
        $router = new Router();
        $router->get('/', ['HomeController', 'index']);

        $match = $router->match($this->request('GET', '/nope'));

        $this->assertFalse($match->found());
        $this->assertSame(404, $match->status);
    }

    public function testCarriesMiddleware(): void
    {
        $router = new Router();
        $router->get('/admin', ['DashboardController', 'index'], ['admin']);

        $match = $router->match($this->request('GET', '/admin'));

        $this->assertSame(['admin'], $match->middleware);
    }

    public function testMethodSpecificOverlappingPaths(): void
    {
        $router = new Router();
        $router->get('/cart', ['CartController', 'index']);
        $router->post('/cart/add', ['CartController', 'add']);

        $get = $router->match($this->request('GET', '/cart'));
        $post = $router->match($this->request('POST', '/cart/add'));

        $this->assertSame(['CartController', 'index'], $get->handler);
        $this->assertSame(['CartController', 'add'], $post->handler);
    }
}
