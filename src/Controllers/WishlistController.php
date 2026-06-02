<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Response;

final class WishlistController extends Controller
{
    public function index(): Response
    {
        return $this->view('account.wishlist', [
            'products' => $this->ctx->wishlist->forUser((int) $this->ctx->auth->id()),
        ]);
    }

    public function toggle(): Response
    {
        $productId = $this->ctx->request->integer('productId');
        $product = $this->ctx->products->findActive($productId);
        if ($product === null) {
            return $this->back('/products');
        }

        $saved = $this->ctx->wishlist->toggle((int) $this->ctx->auth->id(), $productId);
        $this->ctx->session->flash('success', $saved ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
        return $this->back('/products/' . $product['slug']);
    }
}
