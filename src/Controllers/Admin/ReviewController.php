<?php

namespace Bazaar\Controllers\Admin;

use Bazaar\Controllers\Controller;
use Bazaar\Core\Response;

final class ReviewController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin.reviews.index', [
            'pending' => $this->ctx->reviews->moderationQueue('pending'),
            'approved' => $this->ctx->reviews->moderationQueue('approved'),
            'rejected' => $this->ctx->reviews->moderationQueue('rejected'),
        ], 'admin.layout');
    }

    public function approve(string $id): Response
    {
        $this->ctx->reviewService->approve((int) $id);
        $this->ctx->session->flash('success', $this->t('admin.flash.reviewApproved'));
        return $this->redirect('/admin/reviews');
    }

    public function reject(string $id): Response
    {
        $this->ctx->reviewService->reject((int) $id);
        $this->ctx->session->flash('success', $this->t('admin.flash.reviewRejected'));
        return $this->redirect('/admin/reviews');
    }
}
