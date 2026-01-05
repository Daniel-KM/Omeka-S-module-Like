<?php declare(strict_types=1);

namespace 🖒\Api\Adapter;

use Common\Api\Adapter\CommonAdapterTrait;
use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Laminas\EventManager\Event;
use 🖒\Api\Representation\LikeRepresentation;
use 🖒\Entity\Like;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Api\Response;
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
        // Note: This method is not used for this adapter since we override
        // search() to use native SQL (Doctrine DQL parser doesn't support
        // Unicode namespace characters like emoji).
    }

    /**
     * {@inheritDoc}
     *
     * Override to use Native SQL instead of DQL.
     *
     * Doctrine's DQL parser doesn't support Unicode characters (like emoji)
     * in namespace names. The official Doctrine approach for such cases is
     * to use Native SQL queries with ResultSetMapping.
     *
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/native-sql.html
     */
    public function search(Request $request)
    {
        $query = $request->getContent();

        // Set default query parameters (same as parent).
        if (!isset($query['page'])) {
            $query['page'] = null;
        }
        if (!isset($query['per_page'])) {
            $query['per_page'] = null;
        }
        if (!isset($query['limit'])) {
            $query['limit'] = null;
        }
        if (!isset($query['offset'])) {
            $query['offset'] = null;
        }
        if (!isset($query['sort_by'])) {
            $query['sort_by'] = null;
        }
        if (isset($query['sort_order'])
            && in_array(strtoupper($query['sort_order']), ['ASC', 'DESC'])
        ) {
            $query['sort_order'] = strtoupper($query['sort_order']);
        } else {
            $query['sort_order'] = 'ASC';
        }
        if (!isset($query['return_scalar'])) {
            $query['return_scalar'] = null;
        }

        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        // Build SQL query.
        $baseSql = 'FROM `like` l';
        $joins = '';
        $params = [];
        $whereClauses = [];

        // Filter by owner.
        if (!empty($query['owner_id'])) {
            $whereClauses[] = 'l.owner_id = :owner_id';
            $params['owner_id'] = (int) $query['owner_id'];
        }

        // Filter by resource.
        if (!empty($query['resource_id'])) {
            $whereClauses[] = 'l.resource_id = :resource_id';
            $params['resource_id'] = (int) $query['resource_id'];
        }

        // Filter by liked status.
        if (isset($query['liked']) && $query['liked'] !== '') {
            $whereClauses[] = 'l.liked = :liked';
            $params['liked'] = (bool) $query['liked'] ? 1 : 0;
        }

        // Filter by resource type.
        if (!empty($query['resource_type'])) {
            $resourceTypes = is_array($query['resource_type'])
                ? $query['resource_type']
                : [$query['resource_type']];

            $discriminatorMap = [
                'items' => 'Omeka\\Entity\\Item',
                'item_sets' => 'Omeka\\Entity\\ItemSet',
                'media' => 'Omeka\\Entity\\Media',
            ];

            $types = [];
            foreach ($resourceTypes as $type) {
                if (isset($discriminatorMap[$type])) {
                    $types[] = $conn->quote($discriminatorMap[$type]);
                }
            }

            if ($types) {
                $joins .= ' INNER JOIN resource r ON l.resource_id = r.id';
                $whereClauses[] = 'r.resource_type IN (' . implode(',', $types) . ')';
            }
        }

        // Filter by item set.
        if (!empty($query['item_set_id'])) {
            $itemSetIds = is_array($query['item_set_id'])
                ? $query['item_set_id']
                : [$query['item_set_id']];
            $itemSetIds = array_filter(array_map('intval', $itemSetIds));

            if ($itemSetIds) {
                $joins .= ' INNER JOIN item_item_set iis ON l.resource_id = iis.item_id';
                $whereClauses[] = 'iis.item_set_id IN (' . implode(',', $itemSetIds) . ')';
            }
        }

        // Filter by ID(s).
        if (!empty($query['id'])) {
            $ids = is_array($query['id']) ? $query['id'] : [$query['id']];
            $ids = array_filter(array_map('intval', $ids));
            if ($ids) {
                $whereClauses[] = 'l.id IN (' . implode(',', $ids) . ')';
            }
        }

        $whereClause = $whereClauses ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

        // Trigger the api.search.query event.
        // Note: Since we use native SQL, QueryBuilder is not available.
        // Listeners can modify $query via the event if needed.
        $event = new Event('api.search.query', $this, [
            'queryBuilder' => null,
            'request' => $request,
        ]);
        $this->getEventManager()->triggerEvent($event);

        // Pagination.
        $limit = null;
        $offset = null;
        if (is_numeric($query['page'])) {
            $paginator = $this->getServiceLocator()->get('Omeka\Paginator');
            $paginator->setCurrentPage((int) $query['page']);
            if (is_numeric($query['per_page'])) {
                $paginator->setPerPage((int) $query['per_page']);
            }
            $limit = $paginator->getPerPage();
            $offset = $paginator->getOffset();
        } elseif (is_numeric($query['limit'])) {
            $limit = (int) $query['limit'];
            if (is_numeric($query['offset'])) {
                $offset = (int) $query['offset'];
            }
        }

        // Determine if we need count query (same logic as parent).
        $countQueryDefault = $limit !== null || ($offset !== null && $offset > 0);
        $countQuery = $request->getOption('countQuery', $countQueryDefault);

        // Count total if needed.
        $totalResults = 0;
        if ($countQuery) {
            $countSql = 'SELECT COUNT(DISTINCT l.id) ' . $baseSql . $joins . $whereClause;
            $totalResults = (int) $conn->executeQuery($countSql, $params)->fetchOne();
        }

        // Sorting.
        $sortBy = $query['sort_by'] ?? 'id';
        $sortOrder = $query['sort_order'];
        $validSortFields = ['id', 'owner_id', 'resource_id', 'liked', 'created', 'modified'];
        if (in_array($sortBy, $validSortFields)) {
            $orderBy = " ORDER BY l.$sortBy $sortOrder, l.id $sortOrder";
        } elseif ($sortBy === 'random') {
            $orderBy = ' ORDER BY RAND()';
        } else {
            $orderBy = " ORDER BY l.id $sortOrder";
        }

        // Handle return_scalar.
        $scalarField = $request->getOption('returnScalar');
        if (!$scalarField && $query['return_scalar']) {
            if (!array_key_exists($query['return_scalar'], $this->scalarFields)) {
                throw new \Omeka\Api\Exception\BadRequestException(sprintf(
                    $this->getTranslator()->translate('The "%1$s" field is not available in the %2$s adapter class.'),
                    $query['return_scalar'], get_class($this)
                ));
            }
            $scalarField = $query['return_scalar'];
            $request->setOption('returnScalar', $scalarField);
        }

        if ($scalarField) {
            $scalarSql = "SELECT l.id, l.$scalarField " . $baseSql . $joins . $whereClause . $orderBy;
            if ($limit !== null) {
                $scalarSql .= " LIMIT $limit";
                if ($offset !== null) {
                    $scalarSql .= " OFFSET $offset";
                }
            }
            $results = $conn->executeQuery($scalarSql, $params)->fetchAllKeyValue();
            $response = new Response($results);
            $response->setTotalResults($countQuery ? $totalResults : count($results));
            return $response;
        }

        // Trigger the api.search.query.finalize event.
        $event = new Event('api.search.query.finalize', $this, [
            'queryBuilder' => null,
            'request' => $request,
        ]);
        $this->getEventManager()->triggerEvent($event);

        // Build final SQL.
        $sql = 'SELECT l.* ' . $baseSql . $joins . $whereClause . $orderBy;
        if ($limit !== null) {
            $sql .= " LIMIT $limit";
            if ($offset !== null) {
                $sql .= " OFFSET $offset";
            }
        }

        // Execute query and fetch entities.
        $entities = [];
        if ($limit !== 0) {
            $rsm = new ResultSetMapping();
            $rsm->addEntityResult(Like::class, 'l');
            $rsm->addFieldResult('l', 'id', 'id');
            $rsm->addFieldResult('l', 'liked', 'liked');
            $rsm->addFieldResult('l', 'created', 'created');
            $rsm->addFieldResult('l', 'modified', 'modified');
            $rsm->addMetaResult('l', 'owner_id', 'owner_id');
            $rsm->addMetaResult('l', 'resource_id', 'resource_id');

            $nativeQuery = $em->createNativeQuery($sql, $rsm);
            foreach ($params as $key => $value) {
                $nativeQuery->setParameter($key, $value);
            }

            $entities = $nativeQuery->getResult();
        }

        $response = new Response($entities);
        $response->setTotalResults($countQuery ? $totalResults : count($entities));

        return $response;
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

        // Use the like_count view for efficient aggregation.
        $sql = 'SELECT `likes`, `dislikes`, `total` FROM `like_count` WHERE `resource_id` = :resource_id';
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
     * @param bool $allowChangeVote whether changing/removing vote is allowed
     * @return array with 'action' (created, updated, deleted, denied) and 'liked' status
     */
    public function toggleLike(int $resourceId, int $userId, ?bool $liked, bool $allowChangeVote = true): array
    {
        $entityManager = $this->getEntityManager();
        $repository = $entityManager->getRepository(Like::class);

        $existingLike = $repository->findOneBy([
            'resource' => $resourceId,
            'owner' => $userId,
        ]);

        // If user already voted and changing vote is not allowed, deny the action.
        if ($existingLike && !$allowChangeVote) {
            return ['action' => 'denied', 'liked' => $existingLike->isLiked()];
        }

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
