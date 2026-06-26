<section class="block">
    <div class="wrap" style="max-width:560px;text-align:center">
        <?php if ($order !== null && $paymentStatus === 'paid'): ?>
            <div class="empty" style="border-style:solid">
                <svg class="icon empty__icon" viewBox="0 0 24 24" style="color:var(--positive)"><path d="M20 6 9 17l-5-5"/></svg>
                <h1>Payment received</h1>
                <p class="muted">Thank you. Order <strong>#<?= (int) $order['id'] ?></strong> is confirmed and now marked as <strong>paid</strong>.</p>
                <div class="row gap-lg" style="justify-content:center" >
                    <a class="btn btn--primary mt" href="/account/orders/<?= (int) $order['id'] ?>">View order</a>
                    <a class="btn mt" href="/products">Keep shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty">
                <h1>Payment pending</h1>
                <p class="muted">We have not confirmed this payment yet. If you completed checkout, your order status will update shortly.</p>
                <a class="btn mt" href="/account/orders">Go to your orders</a>
            </div>
        <?php endif; ?>
    </div>
</section>
