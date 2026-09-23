<?php

namespace Bazaar\Core;

final class Response
{
    private array $headers = [];

    public function __construct(
        private string $content = '',
        private int $status = 200
    ) {
    }

    public static function html(string $content, int $status = 200): Response
    {
        $response = new self($content, $status);
        $response->headers['Content-Type'] = 'text/html; charset=UTF-8';
        return $response;
    }

    public static function json(array $payload, int $status = 200): Response
    {
        $response = new self(json_encode($payload, JSON_UNESCAPED_SLASHES), $status);
        $response->headers['Content-Type'] = 'application/json';
        return $response;
    }

    public static function redirect(string $location, int $status = 302): Response
    {
        $response = new self('', $status);
        $response->headers['Location'] = $location;
        return $response;
    }

    public function withHeader(string $name, string $value): Response
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->content;
    }
}
