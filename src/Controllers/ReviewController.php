<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Response;

final class ReviewController extends Controller
{
    public function store(): Response
    {
        $request = $this->ctx->request;
        $productId = $request->integer('productId');
        $product = $this->ctx->products->findActive($productId);
        if ($product === null) {
            return $this->notFound();
        }

        $userId = (int) $this->ctx->auth->id();
        if (!$this->ctx->reviewService->canReview($userId, $productId)) {
            $this->ctx->session->flash('error', $this->ctx->translator->get('product.reviewGateBuy'));
            return $this->redirect('/products/' . $product['slug']);
        }

        $rating = max(1, min(5, $request->integer('rating', 5)));
        $title = trim((string) $request->input('title', ''));
        $body = trim((string) $request->input('body', ''));

        $this->ctx->reviewService->submit($userId, $productId, $rating, $title, $body);
        $this->ctx->session->flash('success', $this->ctx->translator->get('product.reviewPending'));
        return $this->redirect('/products/' . $product['slug']);
    }
}
