<?php declare(strict_types=1);

namespace 🖒\Api\Representation;

use DateTime;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\UserRepresentation;

class LikeRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'like';
    }

    public function getJsonLdType()
    {
        return 'o-module-🖒:Like';
    }

    public function getJsonLd()
    {
        $owner = $this->owner();
        $resource = $this->resource();
        $modified = $this->modified();

        return [
            'o:id' => $this->id(),
            'o:owner' => $owner ? $owner->getReference() : null,
            'o:resource' => $resource ? $resource->getReference() : null,
            'o-module-🖒:liked' => $this->liked(),
            'o:created' => $this->getDateTime($this->created()),
            'o:modified' => $modified ? $this->getDateTime($modified) : null,
        ];
    }

    public function owner(): ?UserRepresentation
    {
        $owner = $this->resource->getOwner();
        return $owner
            ? $this->getAdapter('users')->getRepresentation($owner)
            : null;
    }

    public function resource(): ?AbstractResourceEntityRepresentation
    {
        $resource = $this->resource->getResource();
        if (!$resource) {
            return null;
        }

        $resourceAdapter = $this->getAdapter('resources');
        return $resourceAdapter->getRepresentation($resource);
    }

    public function liked(): bool
    {
        return $this->resource->isLiked();
    }

    public function created(): DateTime
    {
        return $this->resource->getCreated();
    }

    public function modified(): ?DateTime
    {
        return $this->resource->getModified();
    }

    /**
     * Get displayable status.
     */
    public function displayStatus(): string
    {
        return $this->liked()
            ? '🖒'
            : '🖓';
    }

    /**
     * Get the admin URL for this like.
     */
    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/like-id',
            [
                'id' => $this->id(),
                'action' => $action,
            ],
            ['force_canonical' => $canonical]
        );
    }
}
