<?php declare(strict_types=1);

namespace 🖒Test\Entity;

use DateTime;
use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒Test\🖒TestTrait;
use 🖒\Entity\Like;

/**
 * Unit tests for the Like entity.
 */
class LikeTest extends AbstractHttpControllerTestCase
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
     * Test entity can be instantiated.
     */
    public function testCanInstantiate(): void
    {
        $like = new Like();
        $this->assertInstanceOf(Like::class, $like);
    }

    /**
     * Test setting and getting owner.
     */
    public function testSetGetOwner(): void
    {
        $entityManager = $this->getEntityManager();
        $user = $this->getCurrentUser();
        $userEntity = $entityManager->find(\Omeka\Entity\User::class, $user->getId());

        $like = new Like();
        $result = $like->setOwner($userEntity);

        // Test fluent interface.
        $this->assertSame($like, $result);
        $this->assertSame($userEntity, $like->getOwner());
    }

    /**
     * Test setting and getting resource.
     */
    public function testSetGetResource(): void
    {
        $item = $this->createItem();
        $entityManager = $this->getEntityManager();
        $resourceEntity = $entityManager->find(\Omeka\Entity\Resource::class, $item->id());

        $like = new Like();
        $result = $like->setResource($resourceEntity);

        $this->assertSame($like, $result);
        $this->assertSame($resourceEntity, $like->getResource());
    }

    /**
     * Test setting and getting liked state (true).
     */
    public function testSetGetLikedTrue(): void
    {
        $like = new Like();
        $result = $like->setLiked(true);

        $this->assertSame($like, $result);
        $this->assertTrue($like->isLiked());
    }

    /**
     * Test setting and getting liked state (false).
     */
    public function testSetGetLikedFalse(): void
    {
        $like = new Like();
        $result = $like->setLiked(false);

        $this->assertSame($like, $result);
        $this->assertFalse($like->isLiked());
    }

    /**
     * Test setting and getting created date.
     */
    public function testSetGetCreated(): void
    {
        $now = new DateTime('now');

        $like = new Like();
        $result = $like->setCreated($now);

        $this->assertSame($like, $result);
        $this->assertSame($now, $like->getCreated());
    }

    /**
     * Test setting and getting modified date.
     */
    public function testSetGetModified(): void
    {
        $now = new DateTime('now');

        $like = new Like();
        $result = $like->setModified($now);

        $this->assertSame($like, $result);
        $this->assertSame($now, $like->getModified());
    }

    /**
     * Test modified can be null.
     */
    public function testModifiedCanBeNull(): void
    {
        $like = new Like();
        $like->setModified(null);

        $this->assertNull($like->getModified());
    }

    /**
     * Test entity persists correctly.
     */
    public function testEntityPersists(): void
    {
        $entityManager = $this->getEntityManager();
        $user = $this->getCurrentUser();
        $userEntity = $entityManager->find(\Omeka\Entity\User::class, $user->getId());

        $item = $this->createItem();
        $resourceEntity = $entityManager->find(\Omeka\Entity\Resource::class, $item->id());

        $like = new Like();
        $like->setOwner($userEntity);
        $like->setResource($resourceEntity);
        $like->setLiked(true);
        $like->setCreated(new DateTime('now'));

        $entityManager->persist($like);
        $entityManager->flush();

        $this->assertNotNull($like->getId());
        $this->createdLikes[] = $like->getId();

        // Clear and reload.
        $entityManager->clear();
        $reloaded = $entityManager->find(Like::class, $like->getId());

        $this->assertInstanceOf(Like::class, $reloaded);
        $this->assertEquals($user->getId(), $reloaded->getOwner()->getId());
        $this->assertEquals($item->id(), $reloaded->getResource()->getId());
        $this->assertTrue($reloaded->isLiked());
    }

    /**
     * Test entity cascade delete on user deletion.
     */
    public function testCascadeDeleteOnUserDeletion(): void
    {
        // Create a test user.
        $testUser = $this->createUser('cascade@example.com', 'Cascade Test');

        // Create an item and like as test user.
        $item = $this->createItem();
        $this->createLike($item->id(), $testUser->id());

        // Verify like exists.
        $response = $this->api()->search('likes', ['owner_id' => $testUser->id()]);
        $this->assertEquals(1, $response->getTotalResults());

        // Remove from cleanup list since deletion will cascade.
        $this->createdLikes = [];
        $this->createdUsers = array_diff($this->createdUsers, [$testUser->id()]);

        // Delete the user.
        $this->api()->delete('users', $testUser->id());

        // Verify like was cascade deleted.
        $response = $this->api()->search('likes', ['owner_id' => $testUser->id()]);
        $this->assertEquals(0, $response->getTotalResults());
    }

    /**
     * Test entity cascade delete on resource deletion.
     */
    public function testCascadeDeleteOnResourceDeletion(): void
    {
        $item = $this->createItem();
        $this->createLike($item->id());

        // Verify like exists.
        $response = $this->api()->search('likes', ['resource_id' => $item->id()]);
        $this->assertEquals(1, $response->getTotalResults());

        // Remove from cleanup lists since deletion will cascade.
        $this->createdLikes = [];
        $this->createdResources = [];

        // Delete the item.
        $this->api()->delete('items', $item->id());

        // Verify like was cascade deleted.
        $response = $this->api()->search('likes', ['resource_id' => $item->id()]);
        $this->assertEquals(0, $response->getTotalResults());
    }

    /**
     * Test unique constraint on owner + resource.
     */
    public function testUniqueConstraint(): void
    {
        $entityManager = $this->getEntityManager();
        $user = $this->getCurrentUser();
        $userEntity = $entityManager->find(\Omeka\Entity\User::class, $user->getId());

        $item = $this->createItem();
        $resourceEntity = $entityManager->find(\Omeka\Entity\Resource::class, $item->id());

        // Create first like.
        $like1 = new Like();
        $like1->setOwner($userEntity);
        $like1->setResource($resourceEntity);
        $like1->setLiked(true);
        $like1->setCreated(new DateTime('now'));

        $entityManager->persist($like1);
        $entityManager->flush();
        $this->createdLikes[] = $like1->getId();

        // Try to create duplicate.
        $like2 = new Like();
        $like2->setOwner($userEntity);
        $like2->setResource($resourceEntity);
        $like2->setLiked(false);
        $like2->setCreated(new DateTime('now'));

        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);
        $entityManager->persist($like2);
        $entityManager->flush();
    }

    /**
     * Test getId returns null before persist.
     */
    public function testGetIdReturnsNullBeforePersist(): void
    {
        $like = new Like();
        $this->assertNull($like->getId());
    }

    /**
     * Test fluent interface chain.
     */
    public function testFluentInterfaceChain(): void
    {
        $entityManager = $this->getEntityManager();
        $user = $this->getCurrentUser();
        $userEntity = $entityManager->find(\Omeka\Entity\User::class, $user->getId());

        $item = $this->createItem();
        $resourceEntity = $entityManager->find(\Omeka\Entity\Resource::class, $item->id());
        $now = new DateTime('now');

        $like = (new Like())
            ->setOwner($userEntity)
            ->setResource($resourceEntity)
            ->setLiked(true)
            ->setCreated($now)
            ->setModified(null);

        $this->assertInstanceOf(Like::class, $like);
        $this->assertSame($userEntity, $like->getOwner());
        $this->assertSame($resourceEntity, $like->getResource());
        $this->assertTrue($like->isLiked());
        $this->assertSame($now, $like->getCreated());
        $this->assertNull($like->getModified());
    }
}
