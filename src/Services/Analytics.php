<?php

namespace Bazaar\Services;

use Bazaar\Core\Database;

final class Analytics
{
    public function __construct(private readonly Database $db)
    {
    }

    public function salesByDay(int $days = 14): array
    {
        $rows = $this->db->select(
            "SELECT substr(COALESCE(paidAt, createdAt), 1, 10) AS day,
                    COUNT(*) AS orderCount,
                    COALESCE(SUM(totalCents), 0) AS revenueCents
             FROM orders
             WHERE status IN ('paid', 'fulfilled')
             GROUP BY day
             ORDER BY day ASC"
        );

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[$row['day']] = [
                'orderCount' => (int) $row['orderCount'],
                'revenueCents' => (int) $row['revenueCents'],
            ];
        }

        $series = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = gmdate('Y-m-d', strtotime("-{$offset} days"));
            $series[] = [
                'day' => $day,
                'orderCount' => $byDay[$day]['orderCount'] ?? 0,
                'revenueCents' => $byDay[$day]['revenueCents'] ?? 0,
            ];
        }
        return $series;
    }

    public function topProducts(int $limit = 5): array
    {
        return $this->db->select(
            "SELECT oi.productId,
                    oi.productName AS name,
                    SUM(oi.quantity) AS unitsSold,
                    SUM(oi.quantity * oi.unitPriceCents) AS revenueCents
             FROM orderItems oi
             JOIN orders o ON o.id = oi.orderId
             WHERE o.status IN ('paid', 'fulfilled')
             GROUP BY oi.productName
             ORDER BY unitsSold DESC, revenueCents DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function averageOrderValueCents(): int
    {
        return (int) round((float) $this->db->scalar(
            "SELECT COALESCE(AVG(totalCents), 0) FROM orders WHERE status IN ('paid', 'fulfilled')"
        ));
    }

    public function unitsSold(): int
    {
        return (int) $this->db->scalar(
            "SELECT COALESCE(SUM(oi.quantity), 0)
             FROM orderItems oi
             JOIN orders o ON o.id = oi.orderId
             WHERE o.status IN ('paid', 'fulfilled')"
        );
    }

    public function couponUsage(): array
    {
        return $this->db->select(
            "SELECT couponCode, COUNT(*) AS orderCount, COALESCE(SUM(discountCents), 0) AS discountCents
             FROM orders
             WHERE couponCode IS NOT NULL AND couponCode != ''
             GROUP BY couponCode
             ORDER BY orderCount DESC"
        );
    }
}
