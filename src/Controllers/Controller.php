<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Context;
use Bazaar\Core\Response;

abstract class Controller
{
    public function __construct(protected readonly Context $ctx)
    {
    }

    protected function view(string $template, array $data = [], string $layout = 'layout'): Response
    {
        return $this->ctx->view->render($template, $data, $layout);
    }

    protected function redirect(string $location): Response
    {
        return Response::redirect($location);
    }

    protected function back(string $fallback = '/'): Response
    {
        $referer = $this->ctx->request->header('Referer');
        return Response::redirect($referer ?? $fallback);
    }

    protected function notFound(): Response
    {
        return $this->ctx->view->render('errors.404', [], 'layout', 404);
    }
}
