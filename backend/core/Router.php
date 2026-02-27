<?php
// Fichier: /backend/core/Router.php

class Router {
    private array $routes = [];
    private array $globalMiddlewares = [];

    public function get($path, $handler, $middlewares = [])    { $this->addRoute('GET',    $path, $handler, $middlewares); }
    public function post($path, $handler, $middlewares = [])   { $this->addRoute('POST',   $path, $handler, $middlewares); }
    public function put($path, $handler, $middlewares = [])    { $this->addRoute('PUT',    $path, $handler, $middlewares); }
    public function delete($path, $handler, $middlewares = []) { $this->addRoute('DELETE', $path, $handler, $middlewares); }

    private function addRoute($method, $path, $handler, $middlewares = []) {
        $this->routes[] = compact('method', 'path', 'handler', 'middlewares');
    }

    public function addMiddleware($middleware) {
        $this->globalMiddlewares[] = $middleware;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];

        // ─── Récupère l'URI de la route ───────────────────────────────────────
        // Stratégie 1 : paramètre ?route=/api/... (sans mod_rewrite)
        if (!empty($_GET['route'])) {
            $uri = '/' . ltrim($_GET['route'], '/');

        // Stratégie 2 : PATH_INFO
        } elseif (!empty($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];

        // Stratégie 3 : REQUEST_URI (mod_rewrite actif)
        } else {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            // Extrait la partie /api/... quelle que soit la profondeur du dossier
            if (preg_match('#(/api/.*)$#', $uri, $m)) {
                $uri = $m[1];
            }
        }

        $uri = rtrim($uri, '/') ?: '/';

        // ─── Cherche la route ──────────────────────────────────────────────────
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $pattern = $this->toPattern($route['path']);
            if (!preg_match($pattern, $uri, $matches)) continue;

            array_shift($matches);

            // Middlewares globaux
            foreach ($this->globalMiddlewares as $mw) {
                (new $mw())->handle();
            }

            // Middlewares de route
            $user = null;
            foreach ($route['middlewares'] as $mw) {
                $result = (new $mw())->handle();
                if ($result && isset($result['id'])) {
                    $user = $result;
                }
            }

            // Appel du contrôleur
            if (is_array($route['handler'])) {
                [$controllerClass, $action] = $route['handler'];
                $controller = new $controllerClass();
                if ($user) $controller->setUser($user);
                call_user_func_array([$controller, $action], $matches);
            } else {
                call_user_func_array($route['handler'], $matches);
            }
            return;
        }

        // 404
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => "Route non trouvee : $method $uri",
            'tip'     => "Verifiez que mod_rewrite est actif, ou que l'URL commence bien par /api/",
        ]);
        exit;
    }

    private function toPattern($path): string {
        $p = preg_replace('/\{[^\}]+\}/', '([^/]+)', $path);
        return '#^' . $p . '$#';
    }
}
