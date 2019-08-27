<?php

namespace Kaspi;

use Kaspi\Exception\ViewException;

class View
{
    private $viewPath;
    private $globalData = [];

    public function __construct(Config $config)
    {
        $this->viewPath = realpath($config->getViewPath()).'/';
    }

    public function addGlobalData(string $key, $data): void
    {
        $this->globalData[$key] = $data;
    }

    public function render(string $template, array $data = []): string
    {
        $template = $this->viewPath.$template;
        if (file_exists($template)) {
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
            $data = array_merge($data, $this->globalData);
            extract($data, EXTR_OVERWRITE);
            ob_start();
            include $template;
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }
        throw new ViewException('View does not exist: '.$template);
    }
}
