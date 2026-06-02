<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Response;

final class CatalogController extends Controller
{
    public function index(): Response
    {
        $request = $this->ctx->request;

        $criteria = [
            'categorySlug' => (string) $request->query('category', ''),
            'term' => (string) $request->query('q', ''),
            'sort' => (string) $request->query('sort', 'newest'),
            'page' => max(1, (int) $request->query('page', 1)),
            'attributeSelections' => $this->parseAttributeSelections($request->query('attr', [])),
        ];
        $minPrice = $request->query('minPrice');
        $maxPrice = $request->query('maxPrice');
        if (is_numeric($minPrice)) {
            $criteria['minPriceCents'] = (int) round((float) $minPrice * 100);
        }
        if (is_numeric($maxPrice)) {
            $criteria['maxPriceCents'] = (int) round((float) $maxPrice * 100);
        }

        $result = $this->ctx->productSearch->search($criteria);
        $wishlisted = $this->ctx->auth->check()
            ? $this->ctx->wishlist->productIdsForUser((int) $this->ctx->auth->id())
            : [];

        return $this->view('catalog.index', [
            'result' => $result,
            'activeCategory' => $criteria['categorySlug'],
            'term' => $criteria['term'],
            'sort' => $criteria['sort'],
            'minPrice' => is_numeric($minPrice) ? $minPrice : '',
            'maxPrice' => is_numeric($maxPrice) ? $maxPrice : '',
            'wishlisted' => $wishlisted,
        ]);
    }

    public function show(string $slug): Response
    {
        $product = $this->ctx->products->findBySlug($slug);
        if ($product === null) {
            return $this->notFound();
        }

        $productId = (int) $product['id'];
        $userId = $this->ctx->auth->check() ? (int) $this->ctx->auth->id() : null;

        return $this->view('catalog.show', [
            'product' => $product,
            'variants' => $this->ctx->variants->activeForProduct($productId),
            'reviewSummary' => $this->ctx->reviews->summary($productId),
            'reviews' => $this->ctx->reviews->approvedForProduct($productId),
            'inWishlist' => $userId !== null && $this->ctx->wishlist->has($userId, $productId),
            'reviewEligibility' => $userId !== null
                ? $this->ctx->reviewService->eligibility($userId, $productId)
                : null,
        ]);
    }

    private function parseAttributeSelections(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $selections = [];
        foreach ($raw as $attributeSlug => $values) {
            $selections[(string) $attributeSlug] = array_values(array_filter(
                array_map('strval', (array) $values),
                static fn (string $value): bool => $value !== ''
            ));
        }
        return $selections;
    }
}
