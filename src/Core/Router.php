<?php

namespace Bazaar\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, array $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $this->compile($path),
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function compile(string $path): string
    {
        $regex = preg_replace('#\{([a-zA-Z]+)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    public function match(Request $request): RouteMatch
    {
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->path(), $captured)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method()) {
                continue;
            }
            $params = array_filter($captured, 'is_string', ARRAY_FILTER_USE_KEY);
            return new RouteMatch($route['handler'], $params, $route['middleware'], 200);
        }

        return new RouteMatch(null, [], [], $pathMatched ? 405 : 404);
    }
}
