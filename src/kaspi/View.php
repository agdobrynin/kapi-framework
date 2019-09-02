<?php

namespace Kaspi;

use Kaspi\Exception\Core\ConfigException;
use Kaspi\Exception\Core\ViewException;

class View
{
    private $config;
    private $viewPath;
    private $globalData = [];
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
            $this->viewPath = realpath($config->getViewPath()) . '/';
        } catch (ConfigException $exception) {
            throw new ViewException($exception->getMessage());
        }
        $this->useExtension = $config->getViewUseTemplateExtension();
    }

    /**
     * Позволяет добавить \Closure объект и выолнить с помощью View::getExtension
     *
     * $view = new View(...);
     * $view->addExtension('my-ext', function(?$param) {
     *      // Closure функция модет быть и без параметров,
     *      // но если они нужны их надо объявить они понадобятся при вызове
     *      // здесь код функции
     * });
     *
     * @param string $extName
     * @param callable $callable
     *
     * @return bool
     */
    public function addExtension(string $extName, callable $callable): bool
    {
        if (empty($this->extensions[$extName])) {
            $this->extensions[$extName] = $callable;
            return true;
        }
        return null;
    }

    public function addExt(string $extName, callable $callable): bool
    {
        return $this->addExtension($extName, $callable);
    }

    /**
     * Позволяет извлечь \Closure объект и выполнять его
     *
     * В шаблоне вызвать если нет параметров
     * $this->getExtension('my-ext')();
     * если параметры есть
     * $this->getExtension('my-ext')('param1' [[, 'param2'], ...]);
     *
     * @param string $extName
     *
     * @return \Closure|null
     */
    public function getExtension(string $extName): ?\Closure
    {
        if (!empty($this->extensions[$extName])) {
            return $this->extensions[$extName];
        }
        return null;
    }

    public function getExt(string $extName): ?\Closure
    {
        return $this->getExtension($extName);
    }

    public function addGlobalData(string $key, $data): void
    {
        $this->globalData[$key] = $data;
    }

    public function include(string $fileName, array $data = []): void
    {
        $data = array_merge($data, $this->globalData);
        extract($data, EXTR_OVERWRITE);
        include $this->viewPath . $fileName;
    }

    /**
     * Расширение существующего шаблона из текущего.
     *
     * @param string $layout путь к расширяемому шаблону
     * @param array $data    переменные передаваемые в шаблон
     */
    private function layout(string $layout, array $data = []): void
    {
        $this->layout->template = $layout;
        $this->layout->data = $data;
    }

    /**
     * Объявление секции которую можно использовать по имени в расширяеомо шаблоне.
     *
     * @param string $sectionName Имя секции отличное от дефолтной
     */
    private function sectionStart(string $sectionName): void
    {
        ob_start();
        $this->sections[$sectionName] = '';
    }

    private function sectionEnd()
    {
        $lastSectionName = key(array_slice($this->sections, -1, 1, true));
        $this->sections[$lastSectionName] = ob_get_contents();
        ob_end_clean();
    }

    private function section(?string $sectionName = null): void
    {
        if (empty($sectionName)) {
            $sectionName = self::DEFAULT_SECTION;
        }
        echo $this->sections[$sectionName];
    }

    public function render(string $template, array $data = []): string
    {
        $template = $this->viewPath . $template . ($this->useExtension ? '' : '.php');
        if (file_exists($template)) {
            $this->layout = new \StdClass();
            $data = array_merge($data, $this->globalData);
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
        throw new ViewException('View does not exist: ' . $template);
    }
}
