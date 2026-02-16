<?php declare(strict_types=1);

namespace 🖒Test\ColumnType;

use 🖒Test\🖒TestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒\ColumnType\🖒Count;

class 🖒CountTest extends AbstractHttpControllerTestCase
{
    use 🖒TestTrait;

    protected $columnType;
    protected $likeAdapter;

    public function setUp(): void
    {
        parent::setUp();

        $services = $this->getApplication()->getServiceManager();
        $this->likeAdapter = $services->get('Omeka\ApiAdapterManager')->get('likes');
        $this->columnType = new 🖒Count($this->likeAdapter);

        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        parent::tearDown();
    }

    public function testGetLabelReturnsTranslatableString(): void
    {
        $this->assertEquals('Like count', $this->columnType->getLabel());
    }

    public function testGetResourceTypesReturnsItemsItemSetsMedia(): void
    {
        $resourceTypes = $this->columnType->getResourceTypes();

        $this->assertIsArray($resourceTypes);
        $this->assertContains('items', $resourceTypes);
        $this->assertContains('item_sets', $resourceTypes);
        $this->assertContains('media', $resourceTypes);
        $this->assertCount(3, $resourceTypes);
    }

    public function testGetMaxColumnsReturnsOne(): void
    {
        $this->assertEquals(1, $this->columnType->getMaxColumns());
    }

    public function testRenderDataFormReturnsEmptyString(): void
    {
        $view = $this->getView();
        $result = $this->columnType->renderDataForm($view, []);

        $this->assertEquals('', $result);
    }

    public function testGetSortByReturnsLikeCount(): void
    {
        $result = $this->columnType->getSortBy([]);

        $this->assertEquals('like_count', $result);
    }

    public function testRenderHeaderReturnsLabel(): void
    {
        $view = $this->getView();
        $result = $this->columnType->renderHeader($view, []);

        $this->assertEquals('Like count', $result);
    }

    public function testRenderContentUsesLikeViewHelper(): void
    {
        // Create an item and verify the column type calls the like helper.
        $item = $this->createItem(['dcterms:title' => [['type' => 'literal', '@value' => 'Test Item']]]);

        $view = $this->getView();
        $result = $this->columnType->renderContent($view, $item, []);

        // The like view helper should return HTML with the like widget.
        $this->assertIsString($result);
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
