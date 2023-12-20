<?php

namespace Tests\Unit\View;

use Kaspi\Config;
use Kaspi\Exception\Core\ViewException;
use Kaspi\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    /** @var Config  */
    protected $config = null;

    protected function setUp(): void
    {
        $this->config = new Config([
            'view' => [
                'path' => __DIR__.'/../../templates',
                'useExtension' => false,
            ]
        ]);
    }

    public function testEmptyConfigFail(): void
    {
        $this->expectException(ViewException::class);
        new View(new Config([]));
    }

    public function testSuccessConstructor(): void
    {
        $this->assertEquals($this->config, (new View($this->config))->getConfig());
    }

    public function testAddExtension(): void
    {
        $view = new View($this->config);

        $res1 = $view->addExtension('sum', static function(): string { return mt_rand();});

        $this->assertTrue($res1);

        $res1 = $view->addExtension('sum', static function(): string {return mt_rand();});

        $this->assertFalse($res1);
    }

    public function testAddExtensionAndGetExtension(): void
    {
        $view = new View($this->config);
        $res1 = $view->addExtension(
            'sum',
            static function (array $nums): int {
                $sum = 0;

                foreach ($nums as $num) {
                    $sum += $num;
                }

                return $sum;
            }
        );
        $res2 = $view->addExt('pi', static function () { return 3.14; });

        $this->assertTrue($res1);
        $this->assertTrue($res2);

        $this->assertEquals(6, $view->getExt('sum', [1,2, 3]));
        $this->assertEquals(3.14, $view->getExtension('pi'));
        $this->assertNull($view->getExt('not-defined-extension'));
    }

    public function testGlobalData(): void
    {
        $view = new View($this->config);
        $view->addGlobalData('hello', 'Hello world');
        $content = $view->render('template_for_global_data');

        $this->assertEquals('Hello world', $content);

        $view->addGlobalData('hello', 'Hello php');
        $content = $view->render('template_for_global_data');
        $this->assertEquals('Hello php', $content);
    }

    public function testShareData(): void
    {
        $view = new View($this->config);
        $view->shareData('hello', 'Hello world');
        $content = $view->render('template_for_global_data');

        $this->assertEquals('Hello world', $content);

        $view->shareData('hello', 'Hello php');
        $content = $view->render('template_for_global_data');
        $this->assertEquals('Hello php', $content);
    }

    public function testIncludeInTemplate(): void
    {
        $view = new View($this->config);
        $view->shareData('shareText', 'into PHPUnit');
        $content = $view->render('template_with_include');

        $this->assertEquals('This template made by me - into PHPUnit', $content);
    }

    public function testIncludeInTemplateNotFound(): void
    {
        $view = new View($this->config);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Include template does not exist');

        $view->render('template_with_include_not_found');
    }

    public function testTemplateNotFound(): void
    {
        $view = new View($this->config);

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('View does not exist: template_not_found');

        $view->render('template_not_found');
    }

    public function testLayoutDefaultSection(): void
    {
        $view = new View($this->config);
        $content = $view->render('template_extended_layout_main_default');

        $this->assertEquals(
            "Test of test\n Data from template into main layout.\n",
            $content
        );
    }

    public function testLayoutNamedSection(): void
    {
        $view = new View($this->config);
        $content = $view->render('template_extended_layout_main_section_js');

        $this->assertEquals('<script>alert(1);</script>', $content);
    }
}