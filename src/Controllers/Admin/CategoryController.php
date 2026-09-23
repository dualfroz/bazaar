<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;

final class CategoryController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.categories.index', [
            'categories' => $this->ctx->categories->withProductCounts(),
        ], 'admin.layout');
    }

    public function create(): Response
    {
        return $this->view('admin.categories.form', [
            'category' => null,
            'errors' => [],
        ], 'admin.layout');
    }

    public function store(): Response
    {
        [$fields, $errors] = $this->validate();
        if ($errors !== []) {
            return $this->view('admin.categories.form', [
                'category' => $fields,
                'errors' => $errors,
            ], 'admin.layout', 422);
        }

        $this->ctx->categories->create($fields['name'], $fields['slug'], $fields['description']);
        $this->ctx->session->flash('success', $this->t('admin.flash.categoryCreated'));
        return $this->redirect('/admin/categories');
    }

    public function edit(string $id): Response
    {
        $category = $this->ctx->categories->find((int) $id);
        if ($category === null) {
            return $this->notFound();
        }

        return $this->view('admin.categories.form', [
            'category' => $category,
            'errors' => [],
        ], 'admin.layout');
    }

    public function update(string $id): Response
    {
        $category = $this->ctx->categories->find((int) $id);
        if ($category === null) {
            return $this->notFound();
        }

        [$fields, $errors] = $this->validate();
        if ($errors !== []) {
            return $this->view('admin.categories.form', [
                'category' => array_merge($category, $fields),
                'errors' => $errors,
            ], 'admin.layout', 422);
        }

        $this->ctx->categories->update((int) $id, $fields['name'], $fields['slug'], $fields['description']);
        $this->ctx->session->flash('success', $this->t('admin.flash.categoryUpdated'));
        return $this->redirect('/admin/categories');
    }

    public function destroy(string $id): Response
    {
        $this->ctx->categories->delete((int) $id);
        $this->ctx->session->flash('success', $this->t('admin.flash.categoryDeleted'));
        return $this->redirect('/admin/categories');
    }

    private function validate(): array
    {
        $request = $this->ctx->request;
        $name = trim((string) $request->input('name', ''));
        $slug = trim((string) $request->input('slug', ''));
        if ($slug === '') {
            $slug = slugify($name);
        }

        $errors = [];
        if ($name === '') {
            $errors['name'] = $this->t('admin.errors.nameRequired');
        }

        $fields = [
            'name' => $name,
            'slug' => $slug,
            'description' => trim((string) $request->input('description', '')),
        ];

        return [$fields, $errors];
    }
}
