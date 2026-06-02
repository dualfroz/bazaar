<?php

namespace Bazaar\Controllers;

use Bazaar\Core\Response;
use Bazaar\Support\Translator;

final class LocaleController extends Controller
{
    public function set(string $locale): Response
    {
        if (Translator::isSupported($locale)) {
            $this->ctx->session->put('locale', $locale);
        }
        return $this->back('/');
    }
}
