<?php

namespace Bazaar\Repositories;

use Bazaar\Core\Database;

final class CouponRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->select('SELECT * FROM coupons ORDER BY createdAt DESC, id DESC');
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM coupons WHERE id = ?', [$id]);
    }

    public function findByCode(string $code): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM coupons WHERE code = ?',
            [strtoupper(trim($code))]
        );
    }

    public function create(array $fields): int
    {
        return $this->db->insert(
            'INSERT INTO coupons
                (code, type, value, minSubtotalCents, maxRedemptions, startsAt, expiresAt, isActive, createdAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                strtoupper(trim($fields['code'])),
                $fields['type'],
                $fields['value'],
                $fields['minSubtotalCents'],
                $fields['maxRedemptions'],
                $fields['startsAt'],
                $fields['expiresAt'],
                $fields['isActive'] ? 1 : 0,
                gmdate('c'),
            ]
        );
    }

    public function update(int $id, array $fields): void
    {
        $this->db->statement(
            'UPDATE coupons SET
                type = ?, value = ?, minSubtotalCents = ?, maxRedemptions = ?,
                startsAt = ?, expiresAt = ?, isActive = ?
             WHERE id = ?',
            [
                $fields['type'],
                $fields['value'],
                $fields['minSubtotalCents'],
                $fields['maxRedemptions'],
                $fields['startsAt'],
                $fields['expiresAt'],
                $fields['isActive'] ? 1 : 0,
                $id,
            ]
        );
    }

    public function recordRedemption(int $id): void
    {
        $this->db->statement(
            'UPDATE coupons SET redeemedCount = redeemedCount + 1 WHERE id = ?',
            [$id]
        );
    }

    public function releaseRedemption(int $id): void
    {
        $this->db->statement(
            'UPDATE coupons SET redeemedCount = redeemedCount - 1 WHERE id = ? AND redeemedCount > 0',
            [$id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->statement('DELETE FROM coupons WHERE id = ?', [$id]);
    }
}
