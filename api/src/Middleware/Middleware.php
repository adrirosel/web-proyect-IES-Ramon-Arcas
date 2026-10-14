<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Rbac;
use App\Auth\SessionAuth;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware reutilizables. Firma: (Request, params, next) => Response.
 */
final class Middleware
{
    /** Exige sesion valida. */
    public static function auth(): callable
    {
        return static function (Request $req, array $params, callable $next): Response {
            if (SessionAuth::user() === null) {
                return Response::error('unauthenticated', 'Sesion requerida', 401);
            }
            return $next($req, $params);
        };
    }

    /** Exige rol con permiso sobre el recurso nombrado. */
    public static function can(string $recurso): callable
    {
        return static function (Request $req, array $params, callable $next) use ($recurso): Response {
            $user = SessionAuth::user();
            if ($user === null) {
                return Response::error('unauthenticated', 'Sesion requerida', 401);
            }
            if (!Rbac::can($user['rol'], $recurso)) {
                return Response::error('forbidden', 'No tienes permiso sobre este recurso', 403);
            }
            return $next($req, $params);
        };
    }

    /** CSRF en metodos que cambian estado. Header X-CSRF-Token o body _csrf. */
    public static function csrf(): callable
    {
        return static function (Request $req, array $params, callable $next): Response {
            if (in_array($req->method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
                $token = $req->header('X-CSRF-Token') ?? (string) $req->input('_csrf', '');
                if (!SessionAuth::validateCsrf($token)) {
                    return Response::error('csrf_invalid', 'Token CSRF invalido o ausente', 403);
                }
            }
            return $next($req, $params);
        };
    }
}
