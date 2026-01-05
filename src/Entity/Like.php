<?php declare(strict_types=1);

namespace 🖒\Entity;

use DateTime;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Resource;
use Omeka\Entity\User;

/**
 * Like entity for tracking user likes/dislikes on resources.
 *
 * Three-state system:
 * - No row: User has not voted;
 * - Row with liked=true: User liked;
 * - Row with liked=false: User disliked.
 *
 * @Entity
 * @Table(
 *     name="`like`",
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"owner_id", "resource_id"}
 *         )
 *     },
 *     indexes={
 *         @Index(
 *             columns={"liked", "resource_id"}
 *         )
 *     }
 * )
 */
class Like extends AbstractEntity
{
    /**
     * @var int
     *
     * @Id
     * @Column(
     *     type="integer"
     * )
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var User
     *
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User",
     *     fetch="LAZY"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $owner;

    /**
     * @var Resource
     *
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Resource",
     *     fetch="LAZY"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $resource;

    /**
     * @var bool
     *
     * @Column(
     *     type="boolean",
     *     nullable=false
     * )
     */
    protected $liked;

    /**
     * @var DateTime
     *
     * @Column(
     *     type="datetime",
     *     nullable=false
     * )
     */
    protected $created;

    /**
     * @var DateTime|null
     *
     * @Column(
     *     type="datetime",
     *     nullable=true
     * )
     */
    protected $modified;

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setResource(Resource $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public function getResource(): Resource
    {
        return $this->resource;
    }

    public function setLiked(bool $liked): self
    {
        $this->liked = $liked;
        return $this;
    }

    public function isLiked(): bool
    {
        return $this->liked;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setModified(?DateTime $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): ?DateTime
    {
        return $this->modified;
    }
}
