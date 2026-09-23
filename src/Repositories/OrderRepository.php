<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class OrderRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function create(
        int $userId,
        int $totalCents,
        string $currency,
        int $discountCents = 0,
        ?string $couponCode = null,
        string $locale = 'en'
    ): int {
        return $this->db->insert(
            'INSERT INTO orders (userId, status, totalCents, currency, discountCents, couponCode, locale, createdAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, 'pending', $totalCents, $currency, $discountCents, $couponCode, $locale, gmdate('c')]
        );
    }

    public function addItem(
        int $orderId,
        int $productId,
        string $productName,
        int $unitPriceCents,
        int $quantity,
        ?int $variantId = null,
        string $variantLabel = ''
    ): void {
        $this->db->insert(
            'INSERT INTO orderItems (orderId, productId, productName, unitPriceCents, quantity, variantId, variantLabel)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$orderId, $productId, $productName, $unitPriceCents, $quantity, $variantId, $variantLabel]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM orders WHERE id = ?', [$id]);
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->db->selectOne('SELECT * FROM orders WHERE id = ? AND userId = ?', [$id, $userId]);
    }

    public function items(int $orderId): array
    {
        return $this->db->select('SELECT * FROM orderItems WHERE orderId = ? ORDER BY id ASC', [$orderId]);
    }

    public function forUser(int $userId): array
    {
        return $this->db->select(
            'SELECT * FROM orders WHERE userId = ? ORDER BY createdAt DESC, id DESC',
            [$userId]
        );
    }

    public function all(): array
    {
        return $this->db->select(
            'SELECT o.*, u.email AS userEmail
             FROM orders o
             LEFT JOIN users u ON u.id = o.userId
             ORDER BY o.createdAt DESC, o.id DESC'
        );
    }

    public function setStatus(int $id, string $status, ?string $timestampColumn = null, ?string $stripeSessionId = null): void
    {
        $sql = 'UPDATE orders SET status = ?';
        $bindings = [$status];
        if ($timestampColumn !== null) {
            $sql .= ", {$this->timestampColumn($timestampColumn)} = ?";
            $bindings[] = gmdate('c');
        }
        if ($stripeSessionId !== null) {
            $sql .= ', stripeSessionId = ?';
            $bindings[] = $stripeSessionId;
        }
        $sql .= ' WHERE id = ?';
        $bindings[] = $id;
        $this->db->statement($sql, $bindings);
    }

    public function transition(int $id, string $fromStatus, string $toStatus, string $timestampColumn): bool
    {
        return $this->db->statement(
            "UPDATE orders SET status = ?, {$this->timestampColumn($timestampColumn)} = ? WHERE id = ? AND status = ?",
            [$toStatus, gmdate('c'), $id, $fromStatus]
        ) === 1;
    }

    public function recordPaymentIntent(int $id, string $paymentIntentId): void
    {
        $this->db->statement('UPDATE orders SET paymentIntentId = ? WHERE id = ?', [$paymentIntentId, $id]);
    }

    public function recordRefund(int $id, string $stripeRefundId, int $refundedCents): void
    {
        $this->db->statement(
            'UPDATE orders SET stripeRefundId = ?, refundedCents = ?, refundedAt = ? WHERE id = ?',
            [$stripeRefundId, $refundedCents, gmdate('c'), $id]
        );
    }

    private function timestampColumn(string $timestampColumn): string
    {
        return match ($timestampColumn) {
            'paidAt' => 'paidAt',
            'fulfilledAt' => 'fulfilledAt',
            'cancelledAt' => 'cancelledAt',
            'failedAt' => 'failedAt',
        };
    }

    public function findByStripeSession(string $sessionId): ?array
    {
        return $this->db->selectOne('SELECT * FROM orders WHERE stripeSessionId = ?', [$sessionId]);
    }

    public function count(): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM orders');
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM orders WHERE status = ?', [$status]);
    }

    public function revenueCents(): int
    {
        return (int) $this->db->scalar(
            "SELECT COALESCE(SUM(totalCents), 0) FROM orders WHERE status IN ('paid', 'fulfilled')"
        );
    }

    public function recent(int $limit = 5): array
    {
        return $this->db->select(
            'SELECT o.*, u.email AS userEmail
             FROM orders o
             LEFT JOIN users u ON u.id = o.userId
             ORDER BY o.createdAt DESC, o.id DESC
             LIMIT ?',
            [$limit]
        );
    }
}
