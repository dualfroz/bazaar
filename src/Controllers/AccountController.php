<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Response;

final class AccountController extends Controller
{
    public function orders(): Response
    {
        return $this->view('account.orders', [
            'orders' => $this->ctx->orders->forUser($this->ctx->auth->id()),
        ]);
    }

    public function show(string $id): Response
    {
        $order = $this->ctx->orders->findForUser((int) $id, $this->ctx->auth->id());
        if ($order === null) {
            return $this->notFound();
        }

        return $this->view('account.order', [
            'order' => $order,
            'items' => $this->ctx->orders->items((int) $id),
        ]);
    }
}
