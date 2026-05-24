<?php

namespace Bazaar\Core;

final class View
{
    private array $shared = [];

    public function __construct(private readonly string $viewsPath)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function render(string $template, array $data = [], string $layout = 'layout', int $status = 200): Response
    {
        $content = $this->renderPartial($template, $data);
        $page = $this->renderPartial($layout, array_merge($data, ['content' => $content]));
        return Response::html($page, $status);
    }

    public function renderPartial(string $template, array $data = []): string
    {
        $file = $this->viewsPath . '/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract($this->shared, EXTR_SKIP);
        extract($data, EXTR_OVERWRITE);

        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
