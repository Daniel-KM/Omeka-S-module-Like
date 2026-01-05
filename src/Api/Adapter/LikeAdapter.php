<?php declare(strict_types=1);

namespace 🖒\Api\Adapter;

use Common\Api\Adapter\CommonAdapterTrait;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use 🖒\Api\Representation\LikeRepresentation;
use 🖒\Entity\Like;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class LikeAdapter extends AbstractEntityAdapter
{
    use CommonAdapterTrait;

    protected $sortFields = [
        'id' => 'id',
        'owner_id' => 'owner',
        'resource_id' => 'resource',
        'liked' => 'liked',
        'created' => 'created',
        'modified' => 'modified',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'owner' => 'owner',
        'resource' => 'resource',
        'liked' => 'liked',
        'created' => 'created',
        'modified' => 'modified',
    ];

    /**
     * Query fields for CommonAdapterTrait.
     *
     * @var array
     */
    protected $queryFields = [
        'id' => [
            'owner_id' => 'owner',
            'resource_id' => 'resource',
        ],
        'bool' => [
            'liked' => 'liked',
        ],
        'datetime' => [
            'created_before' => ['<', 'created'],
            'created_after' => ['>', 'created'],
            'created_until' => ['≤', 'created'],
            'created_since' => ['≥', 'created'],
            'modified_before' => ['<', 'modified'],
            'modified_after' => ['>', 'modified'],
            'modified_until' => ['≤', 'modified'],
            'modified_since' => ['≥', 'modified'],
        ],
    ];

    public function getResourceName()
    {
        return 'likes';
    }

    public function getRepresentationClass()
    {
        return LikeRepresentation::class;
    }

    public function getEntityClass()
    {
        return Like::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query): void
    {
        // Use CommonAdapterTrait for standard query fields.
        $this->buildQueryFields($qb, $query);

        $expr = $qb->expr();

        // Filter by resource type (items, item_sets, media).
        if (!empty($query['resource_type'])) {
            $resourceTypes = is_array($query['resource_type'])
                ? $query['resource_type']
                : [$query['resource_type']];

            $resourceClasses = [];
            $classMap = [
                'items' => \Omeka\Entity\Item::class,
                'item_sets' => \Omeka\Entity\ItemSet::class,
                'media' => \Omeka\Entity\Media::class,
            ];

            foreach ($resourceTypes as $type) {
                if (isset($classMap[$type])) {
                    $resourceClasses[] = $classMap[$type];
                }
            }

            if ($resourceClasses) {
                $resourceAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.resource',
                    $resourceAlias
                );
                $qb->andWhere($expr->in(
                    $resourceAlias . ' INSTANCE OF',
                    $resourceClasses
                ));
            }
        }

        // Filter by item set (for items only).
        if (!empty($query['item_set_id'])) {
            $itemSetIds = is_array($query['item_set_id'])
                ? $query['item_set_id']
                : [$query['item_set_id']];
            $itemSetIds = array_filter(array_map('intval', $itemSetIds));

            if ($itemSetIds) {
                $resourceAlias = $this->createAlias();
                $itemSetAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.resource',
                    $resourceAlias
                );
                $qb->innerJoin(
                    $resourceAlias . '.itemSets',
                    $itemSetAlias,
                    'WITH',
                    $expr->in($itemSetAlias . '.id', $itemSetIds)
                );
            }
        }
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore): void
    {
        /** @var \🖒\Entity\Like $entity */
        $data = $request->getContent();

        if ($this->shouldHydrate($request, 'o:owner')) {
            $owner = $this->getAdapter('users')
                ->findEntity($data['o:owner']['o:id'] ?? $data['o:owner'] ?? null);
            $entity->setOwner($owner);
        }

        if ($this->shouldHydrate($request, 'o:resource')) {
            $resource = $this->getAdapter('resources')
                ->findEntity($data['o:resource']['o:id'] ?? $data['o:resource'] ?? null);
            $entity->setResource($resource);
        }

        if ($this->shouldHydrate($request, 'o-module-🖒:liked')) {
            $entity->setLiked((bool) ($data['o-module-🖒:liked'] ?? true));
        }

        if (Request::CREATE === $request->getOperation()) {
            $entity->setCreated(new DateTime('now'));
        } else {
            $entity->setModified(new DateTime('now'));
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore): void
    {
        /** @var \🖒\Entity\Like $entity */

        // Owner is required.
        if (!$entity->getOwner()) {
            $errorStore->addError('o:owner', 'A like must have an owner.'); // @translate
        }

        // Resource is required.
        if (!$entity->getResource()) {
            $errorStore->addError('o:resource', 'A like must have a resource.'); // @translate
        }

        // Check uniqueness (owner + resource combination).
        if ($entity->getOwner() && $entity->getResource()) {
            $criteria = [
                'owner' => $entity->getOwner(),
                'resource' => $entity->getResource(),
            ];

            $existingLike = $this->getEntityManager()
                ->getRepository(Like::class)
                ->findOneBy($criteria);

            if ($existingLike && $existingLike->getId() !== $entity->getId()) {
                $errorStore->addError('o:resource', 'This user already has a like/dislike on this resource.'); // @translate
            }
        }
    }

    /**
     * Get like counts for a resource.
     *
     * @return array with keys 'likes', 'dislikes', 'total'
     */
    public function getLikeCounts(int $resourceId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
            SELECT
                SUM(CASE WHEN liked = 1 THEN 1 ELSE 0 END) AS likes,
                SUM(CASE WHEN liked = 0 THEN 1 ELSE 0 END) AS dislikes,
                COUNT(*) AS total
            FROM `like`
            WHERE resource_id = :resource_id
        SQL;

        $result = $conn->executeQuery($sql, ['resource_id' => $resourceId])->fetchAssociative();

        return [
            'likes' => (int) ($result['likes'] ?? 0),
            'dislikes' => (int) ($result['dislikes'] ?? 0),
            'total' => (int) ($result['total'] ?? 0),
        ];
    }

    /**
     * Get the user's like status for a resource.
     *
     * @return bool|null null = not voted, true = liked, false = disliked
     */
    public function getUserLikeStatus(int $resourceId, int $userId): ?bool
    {
        $like = $this->getEntityManager()
            ->getRepository(Like::class)
            ->findOneBy([
                'resource' => $resourceId,
                'owner' => $userId,
            ]);

        return $like ? $like->isLiked() : null;
    }

    /**
     * Toggle or set a like/dislike for a user on a resource.
     *
     * @param int $resourceId
     * @param int $userId
     * @param bool|null $liked null to remove, true/false to set
     * @return array with 'action' (created, updated, deleted) and 'liked' status
     */
    public function toggleLike(int $resourceId, int $userId, ?bool $liked): array
    {
        $entityManager = $this->getEntityManager();
        $repository = $entityManager->getRepository(Like::class);

        $existingLike = $repository->findOneBy([
            'resource' => $resourceId,
            'owner' => $userId,
        ]);

        // Remove like if liked is null or if clicking on same state.
        if ($liked === null || ($existingLike && $existingLike->isLiked() === $liked)) {
            if ($existingLike) {
                $entityManager->remove($existingLike);
                $entityManager->flush();
                return ['action' => 'deleted', 'liked' => null];
            }
            return ['action' => 'none', 'liked' => null];
        }

        if ($existingLike) {
            $existingLike
                ->setLiked($liked)
                ->setModified(new DateTime('now'));
            $entityManager->flush();
            return ['action' => 'updated', 'liked' => $liked];
        }

        // Create new like.
        $user = $entityManager->find(\Omeka\Entity\User::class, $userId);
        $resource = $entityManager->find(\Omeka\Entity\Resource::class, $resourceId);

        if (!$user || !$resource) {
            return ['action' => 'error', 'liked' => null];
        }

        $like = new Like();
        $like
            ->setOwner($user)
            ->setResource($resource)
            ->setLiked($liked)
            ->setCreated(new DateTime('now'))
        ;

        $entityManager->persist($like);
        $entityManager->flush();

        return ['action' => 'created', 'liked' => $liked];
    }
}
