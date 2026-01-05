<?php declare(strict_types=1);

namespace 🖒\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class 🖒Button implements ResourcePageBlockLayoutInterface
{
    public function getLabel(): string
    {
        return '🖒: Button'; // @translate
    }

    public function getCompatibleResourceNames(): array
    {
        return [
            'items',
            'item_sets',
            'media',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource): string
    {
        return $view->partial('common/resource-page-block-layout/🖒-button', [
            'resource' => $resource,
        ]);
    }
}
