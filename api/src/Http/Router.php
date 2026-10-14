<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Router minimo por metodo + patron con {param}.
 * Sin framework: suficiente para /api/v1 y facil de seguir en clase.
 */
final class Router
{
    /** @var list<array{method:string,pattern:string,handler:callable,middleware:list<callable>}> */
    private array $routes = [];

    /** @param callable(Request, array<string,string>): Response $handler */
    /** @param list<callable> $middleware */
    public function add(string $method, string $pattern, callable $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function patch(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('PATCH', $pattern, $handler, $middleware);
    }

    public function put(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('PUT', $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $pattern, $handler, $middleware);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            $params = $this->match($route['pattern'], $request->path);
            if ($params === null) {
                continue;
            }

            $handler = $route['handler'];
            // Middleware en onion: el ultimo envuelve al handler
            foreach (array_reverse($route['middleware']) as $mw) {
                $next = $handler;
                $handler = static function (Request $req, array $p) use ($mw, $next): Response {
                    return $mw($req, $p, $next);
                };
            }

            return $handler($request, $params);
        }

        return Response::error('not_found', 'Ruta no encontrada', 404);
    }

    /** @return array<string, string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }
        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}
