<?php

namespace Kaspi;

use Kaspi\Exception\Core\ConfigException;
use Kaspi\Exception\Core\ViewException;

class View
{
    private $config;
    private $viewPath;
    private $sharedData = [];
    private $layout;
    private $sections;
    private $useExtension;
    /** @var array of \Closure */
    protected $extensions = [];

    public const DEFAULT_SECTION = 'content';

    public function __construct(Config $config)
    {
        $this->config = $config;

        try {
            $this->viewPath = realpath($config->getViewPath()).'/';
        } catch (ConfigException $exception) {
            throw new ViewException($exception->getMessage());
        }

        $this->useExtension = $config->getViewUseTemplateExtension();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Позволяет добавить \Closure объект и выполнить с помощью View::getExtension.
     *
     * $view = new View(...);
     * $view->addExtension('my-ext', function(?$param) {
     *      // Closure функция модет быть и без параметров,
     *      // но если они нужны их надо объявить они понадобятся при вызове
     *      // здесь код функции
     * });
     */
    public function addExtension(string $extName, \Closure $callable): bool
    {
        if (empty($this->extensions[$extName])) {
            $this->extensions[$extName] = $callable;

            return true;
        }

        return false;
    }

    public function addExt(string $extName, \Closure $callable): bool
    {
        return $this->addExtension($extName, $callable);
    }

    /**
     * Позволяет получить результат экстеншена.
     *
     * В шаблоне вызвать если нет параметров
     * $this->getExtension('my-ext');
     * если параметры есть
     * $this->getExtension('my-ext', 'param1' [[, 'param2'], ...]);
     *
     * @return null|mixed
     */
    public function getExtension(string $extName, ...$arg)
    {
        if (empty($this->extensions[$extName])) {
            return null;
        }

        $func = $this->extensions[$extName];
        if (count($arg)) {
            return $func(...$arg);
        }

        return $func();
    }

    public function getExt(string $extName, ...$arg)
    {
        return $this->getExtension($extName, ...$arg);
    }

    /**
     * @deprecated будет запрещена в следующих версиях
     * @see View::shareData()
     */
    public function addGlobalData(string $key, $data): void
    {
        $this->sharedData[$key] = $data;
    }

    public function shareData(string $key, $data): void
    {
        $this->sharedData[$key] = $data;
    }

    protected function include(string $templateIn, array $data = []): void
    {
        $template = realpath($this->viewPath.$templateIn.($this->useExtension ? '' : '.php'));

        if (file_exists($template)) {
            $data = array_merge($data, $this->sharedData);
            extract($data, EXTR_OVERWRITE);
            include $template;

            return;
        }

        ob_end_clean();

        throw new ViewException('Include template does not exist: '.$templateIn);
    }

    /**
     * Расширение существующего шаблона из текущего.
     *
     * @param string $layout путь к расширяемому шаблону
     * @param array  $data   переменные передаваемые в шаблон
     */
    protected function layout(string $layout, array $data = []): void
    {
        $this->layout->template = $layout;
        $this->layout->data = $data;
    }

    /**
     * Объявление секции которую можно использовать по имени в расширяеомо шаблоне.
     *
     * @param string $sectionName Имя секции отличное от дефолтной
     */
    protected function sectionStart(string $sectionName): void
    {
        ob_start();
        $this->sections[$sectionName] = '';
    }

    protected function sectionEnd(): void
    {
        $lastSectionName = key(array_slice($this->sections, -1, 1, true));
        $this->sections[$lastSectionName] = ob_get_clean();
    }

    protected function section(?string $sectionName = null): void
    {
        if (empty($sectionName)) {
            $sectionName = self::DEFAULT_SECTION;
        }

        echo $this->sections[$sectionName];
    }

    public function render(string $templatePath, array $data = []): string
    {
        $template = $this->viewPath.$templatePath.($this->useExtension ? '' : '.php');

        if (file_exists($template)) {
            $this->layout = new \StdClass();
            $data = array_merge($data, $this->sharedData);
            extract($data, EXTR_OVERWRITE);

            ob_start();

            include $template;

            $this->sections[self::DEFAULT_SECTION] = ob_get_contents();

            if (!empty($this->layout->template)) {
                $this->render($this->layout->template, array_merge($data, $this->layout->data));
            }

            $content = $this->sections[self::DEFAULT_SECTION];

            ob_end_clean();

            return $content;
        }

        throw new ViewException('View does not exist: '.$templatePath);
    }
}
