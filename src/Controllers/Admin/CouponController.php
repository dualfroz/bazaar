<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;

final class CouponController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.coupons.index', [
            'coupons' => $this->ctx->coupons->all(),
        ], 'admin.layout');
    }

    public function create(): Response
    {
        return $this->view('admin.coupons.form', ['errors' => []], 'admin.layout');
    }

    public function store(): Response
    {
        [$fields, $errors] = $this->validate();
        if ($errors !== []) {
            return $this->view('admin.coupons.form', ['errors' => $errors], 'admin.layout', 422);
        }

        $this->ctx->coupons->create($fields);
        $this->ctx->session->flash('success', 'Coupon created.');
        return $this->redirect('/admin/coupons');
    }

    public function destroy(string $id): Response
    {
        $this->ctx->coupons->delete((int) $id);
        $this->ctx->session->flash('success', 'Coupon deleted.');
        return $this->redirect('/admin/coupons');
    }

    private function validate(): array
    {
        $request = $this->ctx->request;
        $code = strtoupper(trim((string) $request->input('code', '')));
        $type = $request->input('type') === 'fixed' ? 'fixed' : 'percent';
        $rawValue = (float) $request->input('value', 0);
        $value = $type === 'percent' ? (int) round($rawValue) : (int) round($rawValue * 100);
        $maxRedemptions = $request->input('maxRedemptions', '');

        $errors = [];
        if ($code === '') {
            $errors['code'] = 'Code is required.';
        } elseif ($this->ctx->coupons->findByCode($code) !== null) {
            $errors['code'] = 'A coupon with this code already exists.';
        }
        if ($type === 'percent' && ($value < 1 || $value > 100)) {
            $errors['value'] = 'Percent must be between 1 and 100.';
        }
        if ($type === 'fixed' && $value < 1) {
            $errors['value'] = 'Amount must be greater than zero.';
        }

        $fields = [
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'minSubtotalCents' => (int) round((float) $request->input('minSubtotal', 0) * 100),
            'maxRedemptions' => is_numeric($maxRedemptions) ? (int) $maxRedemptions : null,
            'startsAt' => $this->normalizeDate((string) $request->input('startsAt', '')),
            'expiresAt' => $this->normalizeDate((string) $request->input('expiresAt', '')),
            'isActive' => $request->input('isActive') !== null,
        ];

        return [$fields, $errors];
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : gmdate('c', $timestamp);
    }
}
