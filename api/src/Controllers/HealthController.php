<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use PDOException;

final class HealthController
{
    public function __invoke(Request $request, array $params): Response
    {
        $db = 'ok';
        try {
            Database::pdo()->query('SELECT 1');
        } catch (PDOException) {
            $db = 'error';
        }

        $status = $db === 'ok' ? 'ok' : 'degraded';
        $code = $db === 'ok' ? 200 : 503;

        return Response::json(['status' => $status, 'db' => $db], $code);
    }
}
