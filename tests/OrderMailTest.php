<?php

namespace Bazaar\Tests;

use Bazaar\Core\Session;
use Bazaar\Mail\LogMailer;
use Bazaar\Repositories\OrderRepository;
use Bazaar\Repositories\ProductRepository;
use Bazaar\Repositories\UserRepository;
use Bazaar\Repositories\VariantRepository;
use Bazaar\Services\Cart;
use Bazaar\Services\OrderService;

final class OrderMailTest extends DatabaseTestCase
{
    private string $logPath;
    private LogMailer $mailer;
    private OrderService $service;
    private OrderRepository $orders;
    private ProductRepository $products;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logPath = sys_get_temp_dir() . '/bazaar-mail-' . uniqid() . '.log';
        $this->mailer = new LogMailer($this->logPath);
        $this->orders = new OrderRepository($this->db);
        $this->products = new ProductRepository($this->db);
        $this->service = new OrderService(
            $this->db,
            $this->orders,
            $this->products,
            new VariantRepository($this->db),
            null,
            $this->mailer
        );
        $this->userId = (new UserRepository($this->db))->create('mailbuyer@bazaar.test', 'hash', 'Mail Buyer');
    }

    protected function tearDown(): void
    {
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }
    }

    private function order(): int
    {
        $productId = $this->seedProduct('Teapot', 4200, 10);
        $cart = new Cart(new Session(), $this->products);
        $cart->add($productId, 2);
        return $this->service->createFromCart($this->userId, $cart, 'usd');
    }

    public function testNoMailBeforePayment(): void
    {
        $this->order();
        $this->assertCount(0, $this->mailer->sent());
    }

    public function testConfirmationSentOnPaid(): void
    {
        $orderId = $this->order();
        $this->service->markPaid($orderId);

        $sent = $this->mailer->sent();
        $this->assertCount(1, $sent);
        $this->assertSame('mailbuyer@bazaar.test', $sent[0]->to);
        $this->assertStringContainsString('order #' . $orderId, $sent[0]->subject);
        $this->assertStringContainsString('Teapot', $sent[0]->body);
    }

    public function testStatusUpdateSentOnFulfilled(): void
    {
        $orderId = $this->order();
        $this->service->markPaid($orderId);
        $this->service->markFulfilled($orderId);

        $sent = $this->mailer->sent();
        $this->assertCount(2, $sent);
        $this->assertStringContainsString('fulfilled', $sent[1]->subject);
    }

    public function testLogMailerWritesToDisk(): void
    {
        $orderId = $this->order();
        $this->service->markPaid($orderId);

        $this->assertFileExists($this->logPath);
        $this->assertStringContainsString('mailbuyer@bazaar.test', file_get_contents($this->logPath));
    }
}
