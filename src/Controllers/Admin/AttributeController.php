<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;

final class AttributeController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.attributes.index', [
            'attributes' => $this->ctx->attributes->withValues(),
        ], 'admin.layout');
    }

    public function store(): Response
    {
        $name = trim((string) $this->ctx->request->input('name', ''));
        if ($name === '') {
            $this->ctx->session->flash('error', 'Attribute name is required.');
            return $this->redirect('/admin/attributes');
        }
        $slug = slugify($name);
        if ($this->ctx->attributes->findBySlug($slug) === null) {
            $this->ctx->attributes->create($name, $slug);
            $this->ctx->session->flash('success', 'Attribute created.');
        } else {
            $this->ctx->session->flash('error', 'That attribute already exists.');
        }
        return $this->redirect('/admin/attributes');
    }

    public function storeValue(string $id): Response
    {
        $attributeId = (int) $id;
        $attribute = $this->ctx->attributes->find($attributeId);
        if ($attribute === null) {
            return $this->notFound();
        }

        $value = trim((string) $this->ctx->request->input('value', ''));
        if ($value === '') {
            $this->ctx->session->flash('error', 'Value is required.');
            return $this->redirect('/admin/attributes');
        }

        $slug = slugify($value);
        if ($this->ctx->attributes->findValueBySlug($attributeId, $slug) === null) {
            $this->ctx->attributes->createValue($attributeId, $value, $slug);
            $this->ctx->session->flash('success', 'Value added.');
        } else {
            $this->ctx->session->flash('error', 'That value already exists for this attribute.');
        }
        return $this->redirect('/admin/attributes');
    }
}
