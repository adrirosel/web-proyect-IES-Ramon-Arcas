<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Respuesta JSON uniforme. Nunca incluye stack traces al cliente.
 */
final class Response
{
    /** @param array<string, mixed>|list<mixed>|null $data */
    public function __construct(
        private readonly mixed $data,
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self($data, $status, $headers);
    }

    public static function noContent(): self
    {
        return new self(null, 204);
    }

    /**
     * Formato de error unico del contrato API.
     * @param array<string, string>|null $fields
     */
    public static function error(string $code, string $message, int $status, ?array $fields = null): self
    {
        $error = ['code' => $code, 'message' => $message];
        if ($fields !== null) {
            $error['fields'] = $fields;
        }
        return new self(['error' => $error], $status);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        if ($this->status === 204) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
