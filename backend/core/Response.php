<?php
// Fichier: /backend/core/Response.php

class Response {
    public static function success($data = null, string $message = 'Succès', int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => ['timestamp' => date('c'), 'version' => '1.0'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error(string $message = 'Erreur', int $code = 400, $errors = null): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
            'meta'    => ['timestamp' => date('c'), 'version' => '1.0'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function paginated($data, $page, $perPage, $total, string $message = 'Succès'): void {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => [
                'timestamp'  => date('c'),
                'version'    => '1.0',
                'pagination' => [
                    'page'        => (int)$page,
                    'per_page'    => (int)$perPage,
                    'total'       => (int)$total,
                    'total_pages' => (int)ceil($total / max(1, $perPage)),
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
