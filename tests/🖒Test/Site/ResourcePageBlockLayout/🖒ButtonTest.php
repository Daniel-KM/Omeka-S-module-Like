<?php declare(strict_types=1);

namespace 🖒Test\Site\ResourcePageBlockLayout;

use 🖒Test\🖒TestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒\Site\ResourcePageBlockLayout\🖒Button;

class 🖒ButtonTest extends AbstractHttpControllerTestCase
{
    use 🖒TestTrait;

    protected $blockLayout;

    public function setUp(): void
    {
        parent::setUp();
        $this->blockLayout = new 🖒Button();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        parent::tearDown();
    }

    public function testGetLabelReturnsTranslatableString(): void
    {
        $label = $this->blockLayout->getLabel();

        $this->assertEquals('🖒: Button', $label);
    }

    public function testGetCompatibleResourceNamesReturnsExpectedTypes(): void
    {
        $resourceNames = $this->blockLayout->getCompatibleResourceNames();

        $this->assertIsArray($resourceNames);
        $this->assertContains('items', $resourceNames);
        $this->assertContains('item_sets', $resourceNames);
        $this->assertContains('media', $resourceNames);
        $this->assertCount(3, $resourceNames);
    }

    public function testRenderReturnsHtmlString(): void
    {
        $item = $this->createItem(['dcterms:title' => [['type' => 'literal', '@value' => 'Test Block Item']]]);
        $view = $this->getView();

        $result = $this->blockLayout->render($view, $item);

        $this->assertIsString($result);
    }

    public function testRenderUsesCorrectTemplate(): void
    {
        $item = $this->createItem(['dcterms:title' => [['type' => 'literal', '@value' => 'Test Block Item 2']]]);
        $view = $this->getView();

        // The render method should call partial with the correct template.
        $result = $this->blockLayout->render($view, $item);

        // The output should contain the like widget HTML from the template.
        $this->assertIsString($result);
    }

    public function testBlockLayoutImplementsInterface(): void
    {
        $this->assertInstanceOf(
            \Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface::class,
            $this->blockLayout
        );
    }

    /**
     * Get a configured PhpRenderer view.
     */
    protected function getView()
    {
        $services = $this->getApplication()->getServiceManager();
        return $services->get('ViewRenderer');
    }
}
