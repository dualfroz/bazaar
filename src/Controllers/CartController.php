<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Response;

final class CartController extends Controller
{
    public function index(): Response
    {
        return $this->view('cart.index', $this->cartViewData());
    }

    public function add(): Response
    {
        $productId = $this->ctx->request->integer('productId');
        $quantity = max(1, $this->ctx->request->integer('quantity', 1));
        $variantId = $this->ctx->request->integer('variantId', 0) ?: null;
        $this->ctx->cart->add($productId, $quantity, $variantId);
        $this->ctx->session->flash('success', 'Item added to your cart.');
        return $this->redirect('/cart');
    }

    public function update(): Response
    {
        $productId = $this->ctx->request->integer('productId');
        $quantity = $this->ctx->request->integer('quantity', 1);
        $variantId = $this->ctx->request->integer('variantId', 0) ?: null;
        $this->ctx->cart->update($productId, $quantity, $variantId);
        return $this->redirect('/cart');
    }

    public function remove(): Response
    {
        $productId = $this->ctx->request->integer('productId');
        $variantId = $this->ctx->request->integer('variantId', 0) ?: null;
        $this->ctx->cart->remove($productId, $variantId);
        $this->ctx->session->flash('success', 'Item removed from your cart.');
        return $this->redirect('/cart');
    }

    public function applyCoupon(): Response
    {
        $code = trim((string) $this->ctx->request->input('code', ''));
        $result = $this->ctx->couponService->evaluate($code, $this->ctx->cart->totalCents());

        if ($result->isValid()) {
            $this->ctx->session->put('couponCode', $result->coupon['code']);
            $this->ctx->session->flash('success', $this->ctx->translator->get('cart.couponApplied', [
                'code' => $result->coupon['code'],
            ]));
        } else {
            $this->ctx->session->forget('couponCode');
            $this->ctx->session->flash('error', $result->message());
        }
        return $this->redirect('/cart');
    }

    public function removeCoupon(): Response
    {
        $this->ctx->session->forget('couponCode');
        $this->ctx->session->flash('success', 'Coupon removed.');
        return $this->redirect('/cart');
    }

    private function cartViewData(): array
    {
        $subtotalCents = $this->ctx->cart->totalCents();
        $discountCents = 0;
        $couponCode = (string) $this->ctx->session->get('couponCode', '');

        if ($couponCode !== '') {
            $result = $this->ctx->couponService->evaluate($couponCode, $subtotalCents);
            if ($result->isValid()) {
                $discountCents = $result->discountCents;
            } else {
                $this->ctx->session->forget('couponCode');
                $couponCode = '';
            }
        }

        return [
            'lines' => $this->ctx->cart->lines(),
            'subtotalCents' => $subtotalCents,
            'discountCents' => $discountCents,
            'totalCents' => $subtotalCents - $discountCents,
            'couponCode' => $couponCode,
        ];
    }
}
