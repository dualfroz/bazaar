<?php

namespace Bazaar\Services;

use Bazaar\Core\Session;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\VariantRepository;

final class Cart
{
    private const KEY = 'cart';

    public function __construct(
        private readonly Session $session,
        private readonly ProductRepository $products,
        private readonly ?VariantRepository $variants = null
    ) {
    }

    public function add(int $productId, int $quantity, ?int $variantId = null): void
    {
        if ($quantity < 1) {
            return;
        }
        $resolved = $this->resolve($productId, $variantId);
        if ($resolved === null) {
            return;
        }

        $lines = $this->raw();
        $key = $this->lineKey($productId, $variantId);
        $desired = ($lines[$key]['quantity'] ?? 0) + $quantity;
        $quantity = min($desired, $resolved['availableStock']);
        if ($quantity < 1) {
            unset($lines[$key]);
        } else {
            $lines[$key] = ['productId' => $productId, 'variantId' => $variantId, 'quantity' => $quantity];
        }
        $this->persist($lines);
    }

    public function update(int $productId, int $quantity, ?int $variantId = null): void
    {
        $lines = $this->raw();
        $key = $this->lineKey($productId, $variantId);
        if ($quantity < 1) {
            unset($lines[$key]);
            $this->persist($lines);
            return;
        }
        $resolved = $this->resolve($productId, $variantId);
        if ($resolved === null) {
            unset($lines[$key]);
            $this->persist($lines);
            return;
        }
        $lines[$key] = [
            'productId' => $productId,
            'variantId' => $variantId,
            'quantity' => min($quantity, $resolved['availableStock']),
        ];
        $this->persist($lines);
    }

    public function remove(int $productId, ?int $variantId = null): void
    {
        $lines = $this->raw();
        unset($lines[$this->lineKey($productId, $variantId)]);
        $this->persist($lines);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function raw(): array
    {
        $lines = $this->session->get(self::KEY, []);
        return is_array($lines) ? $lines : [];
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    public function count(): int
    {
        $total = 0;
        foreach ($this->raw() as $line) {
            $total += (int) $line['quantity'];
        }
        return $total;
    }

    public function lines(): array
    {
        $lines = [];
        foreach ($this->raw() as $line) {
            $resolved = $this->resolve((int) $line['productId'], $line['variantId']);
            if ($resolved === null) {
                continue;
            }
            $quantity = (int) $line['quantity'];
            $lines[] = [
                'productId' => (int) $line['productId'],
                'variantId' => $line['variantId'],
                'product' => $resolved['product'],
                'variant' => $resolved['variant'],
                'name' => $resolved['product']['name'],
                'variantLabel' => $resolved['variantLabel'],
                'unitPriceCents' => $resolved['unitPriceCents'],
                'availableStock' => $resolved['availableStock'],
                'quantity' => $quantity,
                'lineTotalCents' => $resolved['unitPriceCents'] * $quantity,
            ];
        }
        return $lines;
    }

    public function totalCents(): int
    {
        $total = 0;
        foreach ($this->lines() as $line) {
            $total += $line['lineTotalCents'];
        }
        return $total;
    }

    private function resolve(int $productId, ?int $variantId): ?array
    {
        $product = $this->products->findActive($productId);
        if ($product === null) {
            return null;
        }

        if ($variantId !== null && $this->variants !== null) {
            $variant = $this->variants->findActive($variantId);
            if ($variant === null || (int) $variant['productId'] !== $productId) {
                return null;
            }
            $options = $this->variants->options($variantId);
            return [
                'product' => $product,
                'variant' => $variant,
                'variantLabel' => $this->variants->label($options),
                'unitPriceCents' => (int) $variant['priceCents'],
                'availableStock' => (int) $variant['stockQuantity'],
            ];
        }

        return [
            'product' => $product,
            'variant' => null,
            'variantLabel' => '',
            'unitPriceCents' => (int) $product['priceCents'],
            'availableStock' => (int) $product['stockQuantity'],
        ];
    }

    private function lineKey(int $productId, ?int $variantId): string
    {
        return $variantId !== null ? 'v' . $variantId : 'p' . $productId;
    }

    private function persist(array $lines): void
    {
        if ($lines === []) {
            $this->session->forget(self::KEY);
            return;
        }
        $this->session->put(self::KEY, $lines);
    }
}
