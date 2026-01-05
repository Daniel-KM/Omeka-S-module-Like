<?php declare(strict_types=1);

namespace 🖒\ColumnType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\ColumnType\ColumnTypeInterface;

class 🖒Count implements ColumnTypeInterface
{
    /**
     * @var \🖒\Api\Adapter\LikeAdapter
     */
    protected $likeAdapter;

    public function __construct($likeAdapter)
    {
        $this->likeAdapter = $likeAdapter;
    }

    public function getLabel(): string
    {
        return 'Like count'; // @translate
    }

    public function getResourceTypes(): array
    {
        return [
            'items',
            'item_sets',
            'media',
        ];
    }

    public function getMaxColumns(): ?int
    {
        return 1;
    }

    public function renderDataForm(PhpRenderer $view, array $data): string
    {
        return '';
    }

    public function getSortBy(array $data): ?string
    {
        // Note: Sorting by like count requires a custom query modification.
        // This returns null because the sort is handled via event listener.
        return 'like_count';
    }

    public function renderHeader(PhpRenderer $view, array $data): string
    {
        return $this->getLabel();
    }

    public function renderContent(PhpRenderer $view, AbstractEntityRepresentation $resource, array $data): ?string
    {
        // Render the full like widget (buttons + counts).
        return $view->🖒($resource);
    }
}
