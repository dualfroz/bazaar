<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;

final class ProductController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.products.index', [
            'products' => $this->ctx->products->allForAdmin(),
        ], 'admin.layout');
    }

    public function create(): Response
    {
        return $this->view('admin.products.form', [
            'product' => null,
            'categories' => $this->ctx->categories->all(),
            'errors' => [],
        ], 'admin.layout');
    }

    public function store(): Response
    {
        [$fields, $errors] = $this->validate();
        if ($errors !== []) {
            return $this->view('admin.products.form', [
                'product' => $this->requestSnapshot(),
                'categories' => $this->ctx->categories->all(),
                'errors' => $errors,
            ], 'admin.layout', 422);
        }

        $this->ctx->products->create($fields);
        $this->ctx->session->flash('success', 'Product created.');
        return $this->redirect('/admin/products');
    }

    public function edit(string $id): Response
    {
        $product = $this->ctx->products->find((int) $id);
        if ($product === null) {
            return $this->notFound();
        }

        return $this->view('admin.products.form', [
            'product' => $product,
            'categories' => $this->ctx->categories->all(),
            'errors' => [],
        ], 'admin.layout');
    }

    public function update(string $id): Response
    {
        $product = $this->ctx->products->find((int) $id);
        if ($product === null) {
            return $this->notFound();
        }

        [$fields, $errors] = $this->validate();
        if ($errors !== []) {
            return $this->view('admin.products.form', [
                'product' => array_merge($product, $this->requestSnapshot()),
                'categories' => $this->ctx->categories->all(),
                'errors' => $errors,
            ], 'admin.layout', 422);
        }

        $this->ctx->products->update((int) $id, $fields);
        $this->ctx->session->flash('success', 'Product updated.');
        return $this->redirect('/admin/products');
    }

    public function destroy(string $id): Response
    {
        $this->ctx->products->delete((int) $id);
        $this->ctx->session->flash('success', 'Product deleted.');
        return $this->redirect('/admin/products');
    }

    private function validate(): array
    {
        $request = $this->ctx->request;
        $name = trim((string) $request->input('name', ''));
        $categoryId = $request->integer('categoryId');
        $priceCents = (int) round((float) $request->input('price', 0) * 100);
        $stockQuantity = $request->integer('stockQuantity');
        $slug = trim((string) $request->input('slug', ''));
        if ($slug === '') {
            $slug = slugify($name);
        }

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if ($this->ctx->categories->find($categoryId) === null) {
            $errors['categoryId'] = 'Select a valid category.';
        }
        if ($priceCents < 0) {
            $errors['price'] = 'Price cannot be negative.';
        }
        if ($stockQuantity < 0) {
            $errors['stockQuantity'] = 'Stock cannot be negative.';
        }

        $fields = [
            'categoryId' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) $request->input('description', '')),
            'priceCents' => $priceCents,
            'stockQuantity' => $stockQuantity,
            'imageUrl' => trim((string) $request->input('imageUrl', '')),
            'isActive' => $request->input('isActive') !== null,
        ];

        return [$fields, $errors];
    }

    private function requestSnapshot(): array
    {
        $request = $this->ctx->request;
        return [
            'id' => null,
            'name' => (string) $request->input('name', ''),
            'slug' => (string) $request->input('slug', ''),
            'description' => (string) $request->input('description', ''),
            'categoryId' => $request->integer('categoryId'),
            'priceCents' => (int) round((float) $request->input('price', 0) * 100),
            'stockQuantity' => $request->integer('stockQuantity'),
            'imageUrl' => (string) $request->input('imageUrl', ''),
            'isActive' => $request->input('isActive') !== null ? 1 : 0,
        ];
    }
}
