<?php declare(strict_types=1);

namespace 🖒Test;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\UserRepresentation;

/**
 * Shared test helpers for 🖒 module tests.
 */
trait 🖒TestTrait
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array List of created resource IDs for cleanup.
     */
    protected $createdResources = [];

    /**
     * @var array List of created like IDs for cleanup.
     */
    protected $createdLikes = [];

    /**
     * @var array List of created user IDs for cleanup.
     */
    protected $createdUsers = [];

    /**
     * Get the API manager.
     */
    protected function api(): ApiManager
    {
        return $this->getServiceLocator()->get('Omeka\ApiManager');
    }

    /**
     * Get the service locator.
     */
    protected function getServiceLocator(): ServiceLocatorInterface
    {
        if ($this->services === null) {
            $this->services = $this->getApplication()->getServiceManager();
        }
        return $this->services;
    }

    /**
     * Get the entity manager.
     */
    protected function getEntityManager()
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
    }

    /**
     * Login as admin user.
     */
    protected function loginAdmin(): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $adapter = $auth->getAdapter();
        $adapter->setIdentity('admin@example.com');
        $adapter->setCredential('root');
        $auth->authenticate();
    }

    /**
     * Login as a specific user.
     *
     * @param string $email User email.
     * @param string $password User password.
     */
    protected function loginAs(string $email, string $password = 'test'): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $adapter = $auth->getAdapter();
        $adapter->setIdentity($email);
        $adapter->setCredential($password);
        $auth->authenticate();
    }

    /**
     * Logout current user.
     */
    protected function logout(): void
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->clearIdentity();
    }

    /**
     * Get the current logged-in user.
     *
     * @return \Omeka\Entity\User|null
     */
    protected function getCurrentUser()
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        return $auth->getIdentity();
    }

    /**
     * Create a test user.
     *
     * @param string $email User email.
     * @param string $name User name.
     * @param string $role User role (default: researcher).
     * @return UserRepresentation
     */
    protected function createUser(string $email, string $name, string $role = 'researcher'): UserRepresentation
    {
        $response = $this->api()->create('users', [
            'o:email' => $email,
            'o:name' => $name,
            'o:role' => $role,
            'o:is_active' => true,
        ]);
        $user = $response->getContent();
        $this->createdUsers[] = $user->id();

        // Set password via entity manager.
        $entityManager = $this->getEntityManager();
        $userEntity = $entityManager->find(\Omeka\Entity\User::class, $user->id());
        $userEntity->setPassword('test');
        $entityManager->flush();

        return $user;
    }

    /**
     * Create a test item.
     *
     * @param array $data Item data with property terms as keys.
     * @return ItemRepresentation
     */
    protected function createItem(array $data = []): ItemRepresentation
    {
        $itemData = [];

        // Set default title if not provided.
        if (!isset($data['dcterms:title'])) {
            $data['dcterms:title'] = [['type' => 'literal', '@value' => 'Test Item']];
        }

        // Convert property terms to proper format.
        $easyMeta = $this->getServiceLocator()->get('Common\EasyMeta');

        foreach ($data as $term => $values) {
            // Skip non-property fields.
            if (strpos($term, ':') === false) {
                $itemData[$term] = $values;
                continue;
            }

            $propertyId = $easyMeta->propertyId($term);
            if (!$propertyId) {
                continue;
            }

            $itemData[$term] = [];
            foreach ($values as $value) {
                $valueData = [
                    'type' => $value['type'] ?? 'literal',
                    'property_id' => $propertyId,
                ];
                if (isset($value['@value'])) {
                    $valueData['@value'] = $value['@value'];
                }
                if (isset($value['@id'])) {
                    $valueData['@id'] = $value['@id'];
                }
                if (isset($value['o:label'])) {
                    $valueData['o:label'] = $value['o:label'];
                }
                $itemData[$term][] = $valueData;
            }
        }

        $response = $this->api()->create('items', $itemData);
        $item = $response->getContent();
        $this->createdResources[] = ['type' => 'items', 'id' => $item->id()];

        return $item;
    }

    /**
     * Create a like for a resource.
     *
     * @param int $resourceId Resource ID.
     * @param int|null $userId User ID (default: current user).
     * @param bool $liked True for like, false for dislike.
     * @return array Like data.
     */
    protected function createLike(int $resourceId, ?int $userId = null, bool $liked = true): array
    {
        if ($userId === null) {
            $currentUser = $this->getCurrentUser();
            $userId = $currentUser ? $currentUser->getId() : null;
        }

        if (!$userId) {
            throw new \RuntimeException('No user logged in to create like');
        }

        $entityManager = $this->getEntityManager();
        $user = $entityManager->find(\Omeka\Entity\User::class, $userId);
        $resource = $entityManager->find(\Omeka\Entity\Resource::class, $resourceId);

        $like = new \🖒\Entity\Like();
        $like->setOwner($user);
        $like->setResource($resource);
        $like->setLiked($liked);
        $like->setCreated(new \DateTime('now'));

        $entityManager->persist($like);
        $entityManager->flush();

        $this->createdLikes[] = $like->getId();

        return [
            'id' => $like->getId(),
            'owner_id' => $userId,
            'resource_id' => $resourceId,
            'liked' => $liked,
        ];
    }

    /**
     * Get the LikeAdapter instance.
     *
     * @return \🖒\Api\Adapter\LikeAdapter
     */
    protected function getLikeAdapter()
    {
        return $this->getServiceLocator()->get('Omeka\ApiAdapterManager')->get('likes');
    }

    /**
     * Get like counts for a resource using the adapter.
     *
     * @param int $resourceId Resource ID.
     * @return array with 'likes', 'dislikes', 'total' keys.
     */
    protected function getLikeCounts(int $resourceId): array
    {
        $adapter = $this->getLikeAdapter();
        return $adapter->getLikeCounts($resourceId);
    }

    /**
     * Get user's like status for a resource.
     *
     * @param int $resourceId Resource ID.
     * @param int $userId User ID.
     * @return bool|null null = not voted, true = liked, false = disliked.
     */
    protected function getUserLikeStatus(int $resourceId, int $userId): ?bool
    {
        $adapter = $this->getLikeAdapter();
        return $adapter->getUserLikeStatus($resourceId, $userId);
    }

    /**
     * Get a fixture file content.
     *
     * @param string $name Fixture filename.
     * @return string
     */
    protected function getFixture(string $name): string
    {
        $path = dirname(__DIR__) . '/fixtures/' . $name;
        if (!file_exists($path)) {
            throw new \RuntimeException("Fixture not found: $path");
        }
        return file_get_contents($path);
    }

    /**
     * Get the path to the fixtures directory.
     */
    protected function getFixturesPath(): string
    {
        return dirname(__DIR__) . '/fixtures';
    }

    /**
     * Clean up created resources after test.
     */
    protected function cleanupResources(): void
    {
        $entityManager = $this->getEntityManager();

        // Delete created likes first.
        foreach ($this->createdLikes as $likeId) {
            try {
                $like = $entityManager->find(\🖒\Entity\Like::class, $likeId);
                if ($like) {
                    $entityManager->remove($like);
                }
            } catch (\Exception $e) {
                // Ignore errors during cleanup.
            }
        }
        $this->createdLikes = [];

        // Flush like deletions.
        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            // Ignore.
        }

        // Delete created items.
        foreach ($this->createdResources as $resource) {
            try {
                $this->api()->delete($resource['type'], $resource['id']);
            } catch (\Exception $e) {
                // Ignore errors during cleanup.
            }
        }
        $this->createdResources = [];

        // Delete created users.
        foreach ($this->createdUsers as $userId) {
            try {
                $this->api()->delete('users', $userId);
            } catch (\Exception $e) {
                // Ignore errors during cleanup.
            }
        }
        $this->createdUsers = [];
    }
}
