<?php

namespace Bazaar\Core;

final class RouteMatch
{
    public function __construct(
        public readonly ?array $handler,
        public readonly array $params,
        public readonly array $middleware,
        public readonly int $status
    ) {
    }

    public function found(): bool
    {
        return $this->status === 200 && $this->handler !== null;
    }
}
