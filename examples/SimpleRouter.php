<?php

/**
 * Tiny HTTP router. Lives in examples/ on purpose: it's a copy-paste
 * reference, not part of the library API. Replace it with nikic/fast-route,
 * Symfony Routing, or whatever you already use.
 */

declare(strict_types=1);

final class SimpleRouter
{
    /** @var array<int, array{method: string, pattern: string, regex: string, paramNames: string[], handler: callable}> */
    private $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $names = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            function ($m) use (&$names) {
                $names[] = $m[1];
                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $path
        );
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#',
            'paramNames' => $names,
            'handler' => $handler,
        ];
    }

    /**
     * Dispatch a request. Returns true if a route matched (even on error),
     * false if no route matched.
     */
    public function dispatch(string $method, string $path): bool
    {
        $method = strtoupper($method);
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }
            $params = [];
            foreach ($route['paramNames'] as $name) {
                $params[$name] = $matches[$name] ?? null;
            }
            ($route['handler'])($params);
            return true;
        }
        return false;
    }
}
