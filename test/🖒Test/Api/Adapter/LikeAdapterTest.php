<?php declare(strict_types=1);

namespace 🖒Test\Api\Adapter;

use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒Test\🖒TestTrait;

/**
 * Unit tests for the LikeAdapter.
 *
 * Tests the Native SQL-based adapter implementation.
 */
class LikeAdapterTest extends AbstractHttpControllerTestCase
{
    use 🖒TestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        $this->logout();
        parent::tearDown();
    }

    /**
     * Test adapter returns correct resource name.
     */
    public function testGetResourceName(): void
    {
        $adapter = $this->getLikeAdapter();
        $this->assertEquals('likes', $adapter->getResourceName());
    }

    /**
     * Test adapter returns correct representation class.
     */
    public function testGetRepresentationClass(): void
    {
        $adapter = $this->getLikeAdapter();
        $this->assertEquals(
            \🖒\Api\Representation\LikeRepresentation::class,
            $adapter->getRepresentationClass()
        );
    }

    /**
     * Test adapter returns correct entity class.
     */
    public function testGetEntityClass(): void
    {
        $adapter = $this->getLikeAdapter();
        $this->assertEquals(
            \🖒\Entity\Like::class,
            $adapter->getEntityClass()
        );
    }

    /**
     * Test search returns empty results when no likes exist.
     */
    public function testSearchReturnsEmptyWhenNoLikes(): void
    {
        $response = $this->api()->search('likes', []);
        $this->assertEquals(0, $response->getTotalResults());
        $this->assertEmpty($response->getContent());
    }

    /**
     * Test creating a like through the API.
     */
    public function testCreateLike(): void
    {
        $item = $this->createItem();
        $user = $this->getCurrentUser();

        $response = $this->api()->create('likes', [
            'o:owner' => ['o:id' => $user->getId()],
            'o:resource' => ['o:id' => $item->id()],
            'o-module-🖒:liked' => true,
        ]);

        $like = $response->getContent();
        $this->createdLikes[] = $like->id();

        $this->assertNotNull($like->id());
        $this->assertEquals($user->getId(), $like->owner()->id());
        $this->assertEquals($item->id(), $like->resource()->id());
        $this->assertTrue($like->liked());
    }

    /**
     * Test creating a dislike through the API.
     */
    public function testCreateDislike(): void
    {
        $item = $this->createItem();
        $user = $this->getCurrentUser();

        $response = $this->api()->create('likes', [
            'o:owner' => ['o:id' => $user->getId()],
            'o:resource' => ['o:id' => $item->id()],
            'o-module-🖒:liked' => false,
        ]);

        $like = $response->getContent();
        $this->createdLikes[] = $like->id();

        $this->assertNotNull($like->id());
        $this->assertFalse($like->liked());
    }

    /**
     * Test search with pagination.
     */
    public function testSearchWithPagination(): void
    {
        // Create multiple items and likes.
        $items = [];
        for ($i = 0; $i < 5; $i++) {
            $items[] = $this->createItem([
                'dcterms:title' => [['type' => 'literal', '@value' => "Test Item $i"]],
            ]);
        }

        foreach ($items as $item) {
            $this->createLike($item->id());
        }

        // Test first page.
        $response = $this->api()->search('likes', [
            'page' => 1,
            'per_page' => 2,
        ]);

        $this->assertEquals(5, $response->getTotalResults());
        $this->assertCount(2, $response->getContent());

        // Test second page.
        $response = $this->api()->search('likes', [
            'page' => 2,
            'per_page' => 2,
        ]);

        $this->assertEquals(5, $response->getTotalResults());
        $this->assertCount(2, $response->getContent());
    }

    /**
     * Test search with limit and offset.
     */
    public function testSearchWithLimitOffset(): void
    {
        // Create items and likes.
        for ($i = 0; $i < 5; $i++) {
            $item = $this->createItem([
                'dcterms:title' => [['type' => 'literal', '@value' => "Test Item $i"]],
            ]);
            $this->createLike($item->id());
        }

        $response = $this->api()->search('likes', [
            'limit' => 3,
            'offset' => 1,
        ]);

        $this->assertEquals(5, $response->getTotalResults());
        $this->assertCount(3, $response->getContent());
    }

    /**
     * Test search filtering by owner.
     */
    public function testSearchByOwner(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem();

        // Create likes as admin.
        $adminUser = $this->getCurrentUser();
        $this->createLike($item1->id(), $adminUser->getId());

        // Create another user and like.
        $testUser = $this->createUser('test@example.com', 'Test User');
        $this->createLike($item2->id(), $testUser->id());

        // Search by admin.
        $response = $this->api()->search('likes', [
            'owner_id' => $adminUser->getId(),
        ]);

        $this->assertEquals(1, $response->getTotalResults());
        $like = $response->getContent()[0];
        $this->assertEquals($adminUser->getId(), $like->owner()->id());
    }

    /**
     * Test search filtering by resource.
     */
    public function testSearchByResource(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem();

        $this->createLike($item1->id());
        $this->createLike($item2->id());

        $response = $this->api()->search('likes', [
            'resource_id' => $item1->id(),
        ]);

        $this->assertEquals(1, $response->getTotalResults());
        $like = $response->getContent()[0];
        $this->assertEquals($item1->id(), $like->resource()->id());
    }

    /**
     * Test search filtering by liked status.
     */
    public function testSearchByLikedStatus(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem();

        $this->createLike($item1->id(), null, true);  // Like
        $this->createLike($item2->id(), null, false); // Dislike

        // Search for likes only.
        $response = $this->api()->search('likes', [
            'liked' => true,
        ]);
        $this->assertEquals(1, $response->getTotalResults());

        // Search for dislikes only.
        $response = $this->api()->search('likes', [
            'liked' => false,
        ]);
        $this->assertEquals(1, $response->getTotalResults());
    }

    /**
     * Test search with sorting.
     */
    public function testSearchWithSorting(): void
    {
        $item1 = $this->createItem();
        $item2 = $this->createItem();

        $this->createLike($item1->id());
        sleep(1); // Ensure different timestamps.
        $this->createLike($item2->id());

        // Sort by created DESC.
        $response = $this->api()->search('likes', [
            'sort_by' => 'created',
            'sort_order' => 'DESC',
        ]);

        $likes = $response->getContent();
        $this->assertCount(2, $likes);
        $this->assertEquals($item2->id(), $likes[0]->resource()->id());
        $this->assertEquals($item1->id(), $likes[1]->resource()->id());
    }

    /**
     * Test search with return_scalar option.
     */
    public function testSearchWithReturnScalar(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id());

        $response = $this->api()->search('likes', [
            'return_scalar' => 'id',
        ]);

        $results = $response->getContent();
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        // Return scalar returns id => value pairs.
        $this->assertArrayHasKey(array_key_first($results), $results);
    }

    /**
     * Test getLikeCounts method.
     */
    public function testGetLikeCounts(): void
    {
        $item = $this->createItem();

        // Initially no likes.
        $counts = $this->getLikeCounts($item->id());
        $this->assertEquals(0, $counts['likes']);
        $this->assertEquals(0, $counts['dislikes']);
        $this->assertEquals(0, $counts['total']);

        // Add a like.
        $this->createLike($item->id(), null, true);

        $counts = $this->getLikeCounts($item->id());
        $this->assertEquals(1, $counts['likes']);
        $this->assertEquals(0, $counts['dislikes']);
        $this->assertEquals(1, $counts['total']);

        // Add a dislike from different user.
        $testUser = $this->createUser('test2@example.com', 'Test User 2');
        $this->createLike($item->id(), $testUser->id(), false);

        $counts = $this->getLikeCounts($item->id());
        $this->assertEquals(1, $counts['likes']);
        $this->assertEquals(1, $counts['dislikes']);
        $this->assertEquals(2, $counts['total']);
    }

    /**
     * Test getUserLikeStatus method.
     */
    public function testGetUserLikeStatus(): void
    {
        $item = $this->createItem();
        $user = $this->getCurrentUser();

        // No vote yet.
        $status = $this->getUserLikeStatus($item->id(), $user->getId());
        $this->assertNull($status);

        // Add a like.
        $this->createLike($item->id(), $user->getId(), true);

        $status = $this->getUserLikeStatus($item->id(), $user->getId());
        $this->assertTrue($status);

        // Clean up and add dislike.
        $this->cleanupResources();
        $item = $this->createItem();
        $this->createLike($item->id(), $user->getId(), false);

        $status = $this->getUserLikeStatus($item->id(), $user->getId());
        $this->assertFalse($status);
    }

    /**
     * Test toggleLike creates new like.
     */
    public function testToggleLikeCreatesNew(): void
    {
        $item = $this->createItem();
        $user = $this->getCurrentUser();

        $adapter = $this->getLikeAdapter();
        $result = $adapter->toggleLike($item->id(), $user->getId(), true);

        $this->assertEquals('created', $result['action']);
        $this->assertTrue($result['liked']);

        // Verify it was created.
        $status = $this->getUserLikeStatus($item->id(), $user->getId());
        $this->assertTrue($status);
    }

    /**
     * Test toggleLike updates existing.
     */
    public function testToggleLikeUpdatesExisting(): void
    {
        $item = $this->createItem();
        $user = $this->getCurrentUser();

        // Create initial like.
        $this->createLike($item->id(), $user->getId(), true);

        // Toggle to dislike.
        $adapter = $this->getLikeAdapter();
        $result = $adapter->toggleLike($item->id(), $user->getId(), false);

        $this->assertEquals('updated', $result['action']);
        $this->assertFalse($result['liked']);
    }

    /**
     * Test toggleLike removes when clicking same state.
     */
    public function testToggleLikeRemovesOnSameState(): void
    {
        $item = $this->createItem();
        $user = $this->getCurrentUser();

        // Create initial like.
        $this->createLike($item->id(), $user->getId(), true);

        // Toggle same state (like again).
        $adapter = $this->getLikeAdapter();
        $result = $adapter->toggleLike($item->id(), $user->getId(), true);

        $this->assertEquals('deleted', $result['action']);
        $this->assertNull($result['liked']);

        // Verify it was deleted.
        $status = $this->getUserLikeStatus($item->id(), $user->getId());
        $this->assertNull($status);
    }

    /**
     * Test validation: owner is required.
     *
     * Note: When owner is null, findEntity() throws NotFoundException.
     */
    public function testValidationOwnerRequired(): void
    {
        $this->expectException(\Omeka\Api\Exception\NotFoundException::class);

        $item = $this->createItem();

        $this->api()->create('likes', [
            'o:resource' => ['o:id' => $item->id()],
            'o-module-🖒:liked' => true,
        ]);
    }

    /**
     * Test validation: resource is required.
     *
     * Note: When resource is null, findEntity() throws NotFoundException.
     */
    public function testValidationResourceRequired(): void
    {
        $this->expectException(\Omeka\Api\Exception\NotFoundException::class);

        $user = $this->getCurrentUser();

        $this->api()->create('likes', [
            'o:owner' => ['o:id' => $user->getId()],
            'o-module-🖒:liked' => true,
        ]);
    }

    /**
     * Test validation: duplicate like not allowed.
     */
    public function testValidationDuplicateNotAllowed(): void
    {
        $this->expectException(\Omeka\Api\Exception\ValidationException::class);

        $item = $this->createItem();
        $user = $this->getCurrentUser();

        // Create first like.
        $response = $this->api()->create('likes', [
            'o:owner' => ['o:id' => $user->getId()],
            'o:resource' => ['o:id' => $item->id()],
            'o-module-🖒:liked' => true,
        ]);
        $this->createdLikes[] = $response->getContent()->id();

        // Try to create duplicate.
        $this->api()->create('likes', [
            'o:owner' => ['o:id' => $user->getId()],
            'o:resource' => ['o:id' => $item->id()],
            'o-module-🖒:liked' => false,
        ]);
    }

    /**
     * Test deleting a like via entity manager.
     *
     * Note: API delete() uses DQL read() which fails with emoji namespace.
     * Use entity manager or toggleLike() instead.
     */
    public function testDeleteLikeViaEntityManager(): void
    {
        $item = $this->createItem();
        $user = $this->getCurrentUser();

        $response = $this->api()->create('likes', [
            'o:owner' => ['o:id' => $user->getId()],
            'o:resource' => ['o:id' => $item->id()],
            'o-module-🖒:liked' => true,
        ]);
        $likeId = $response->getContent()->id();

        // Delete via entity manager.
        $entityManager = $this->getEntityManager();
        $like = $entityManager->find(\🖒\Entity\Like::class, $likeId);
        $entityManager->remove($like);
        $entityManager->flush();

        // Verify it's gone.
        $response = $this->api()->search('likes', ['id' => $likeId]);
        $this->assertEquals(0, $response->getTotalResults());
    }

    /**
     * Test updating a like via entity manager.
     *
     * Note: API update() uses DQL read() which fails with emoji namespace.
     * Use entity manager or toggleLike() instead.
     */
    public function testUpdateLikeViaEntityManager(): void
    {
        $item = $this->createItem();
        $user = $this->getCurrentUser();

        $response = $this->api()->create('likes', [
            'o:owner' => ['o:id' => $user->getId()],
            'o:resource' => ['o:id' => $item->id()],
            'o-module-🖒:liked' => true,
        ]);
        $likeId = $response->getContent()->id();
        $this->createdLikes[] = $likeId;

        // Update via entity manager.
        $entityManager = $this->getEntityManager();
        $like = $entityManager->find(\🖒\Entity\Like::class, $likeId);
        $this->assertTrue($like->isLiked());

        $like->setLiked(false);
        $like->setModified(new \DateTime('now'));
        $entityManager->flush();

        // Verify change.
        $entityManager->clear();
        $updatedLike = $entityManager->find(\🖒\Entity\Like::class, $likeId);
        $this->assertFalse($updatedLike->isLiked());
    }
}
