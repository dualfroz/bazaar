<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;

final class VariantController extends Controller
{
    public function index(string $id): Response
    {
        $product = $this->ctx->products->find((int) $id);
        if ($product === null) {
            return $this->notFound();
        }

        return $this->view('admin.variants.index', [
            'product' => $product,
            'variants' => $this->ctx->variants->forProduct((int) $id),
            'attributes' => $this->ctx->attributes->withValues(),
        ], 'admin.layout');
    }

    public function store(string $id): Response
    {
        $productId = (int) $id;
        $product = $this->ctx->products->find($productId);
        if ($product === null) {
            return $this->notFound();
        }

        $request = $this->ctx->request;
        $sku = trim((string) $request->input('sku', ''));
        if ($sku === '') {
            $sku = slugify($product['name']) . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
        }
        $priceCents = (int) round((float) $request->input('price', 0) * 100);
        $stock = $request->integer('stockQuantity');

        $variantId = $this->ctx->variants->create($productId, $sku, $priceCents, max(0, $stock));

        $options = $request->input('option', []);
        if (is_array($options)) {
            foreach ($options as $attributeId => $valueId) {
                if (is_numeric($valueId) && (int) $valueId > 0) {
                    $this->ctx->variants->setOption($variantId, (int) $attributeId, (int) $valueId);
                }
            }
        }

        $this->ctx->session->flash('success', 'Variant added.');
        return $this->redirect('/admin/products/' . $productId . '/variants');
    }

    public function destroy(string $id): Response
    {
        $variant = $this->ctx->variants->find((int) $id);
        if ($variant === null) {
            return $this->notFound();
        }
        $this->ctx->variants->delete((int) $id);
        $this->ctx->session->flash('success', 'Variant removed.');
        return $this->redirect('/admin/products/' . (int) $variant['productId'] . '/variants');
    }
}
