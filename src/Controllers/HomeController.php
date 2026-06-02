<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Response;

final class HomeController extends Controller
{
    public function index(): Response
    {
        return $this->view('home', [
            'featured' => $this->ctx->products->featured(6),
            'categories' => $this->ctx->categories->withProductCounts(),
        ]);
    }
}
