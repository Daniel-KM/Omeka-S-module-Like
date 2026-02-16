<?php declare(strict_types=1);

namespace 🖒Test\Api\Representation;

use DateTime;
use 🖒Test\🖒TestTrait;
use Omeka\Test\AbstractHttpControllerTestCase;
use 🖒\Entity\Like;

class LikeRepresentationTest extends AbstractHttpControllerTestCase
{
    use 🖒TestTrait;

    protected $user;
    protected $item;
    protected $likeAdapter;
    protected $em;

    public function setUp(): void
    {
        parent::setUp();

        $services = $this->getApplication()->getServiceManager();
        $this->em = $services->get('Omeka\EntityManager');
        $this->likeAdapter = $services->get('Omeka\ApiAdapterManager')->get('likes');

        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupResources();
        parent::tearDown();
    }

    protected function createTestUser(): \Omeka\Api\Representation\UserRepresentation
    {
        $email = 'reptest' . uniqid() . '@example.com';
        return $this->createUser($email, 'Test User');
    }

    protected function createTestItem(): \Omeka\Api\Representation\ItemRepresentation
    {
        return $this->createItem(['dcterms:title' => [['type' => 'literal', '@value' => 'Test Item']]]);
    }

    protected function createLikeEntity(int $resourceId, int $userId, bool $liked = true): Like
    {
        $userEntity = $this->em->find(\Omeka\Entity\User::class, $userId);
        $resourceEntity = $this->em->find(\Omeka\Entity\Item::class, $resourceId);

        $like = new Like();
        $like->setOwner($userEntity);
        $like->setResource($resourceEntity);
        $like->setLiked($liked);
        $like->setCreated(new DateTime());
        $this->em->persist($like);
        $this->em->flush();

        $this->createdLikes[] = $like->getId();

        return $like;
    }

    public function testOwnerReturnsUserRepresentation(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $owner = $representation->owner();
        $this->assertNotNull($owner);
        $this->assertEquals($user->id(), $owner->id());
        $this->assertEquals('Test User', $owner->name());
    }

    public function testResourceReturnsResourceRepresentation(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $resource = $representation->resource();
        $this->assertNotNull($resource);
        $this->assertEquals($item->id(), $resource->id());
    }

    public function testLikedReturnsTrue(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $this->assertTrue($representation->liked());
    }

    public function testLikedReturnsFalse(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), false);

        $representation = $this->likeAdapter->getRepresentation($like);

        $this->assertFalse($representation->liked());
    }

    public function testCreatedReturnsDateTime(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $created = $representation->created();
        $this->assertInstanceOf(DateTime::class, $created);
    }

    public function testModifiedReturnsNullInitially(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $this->assertNull($representation->modified());
    }

    public function testModifiedReturnsDateTimeAfterUpdate(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        // Update the like.
        $like->setLiked(false);
        $like->setModified(new DateTime());
        $this->em->persist($like);
        $this->em->flush();

        $representation = $this->likeAdapter->getRepresentation($like);
        $this->assertInstanceOf(DateTime::class, $representation->modified());
    }

    public function testDisplayStatusReturnsThumbUpForLike(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $this->assertEquals('🖒', $representation->displayStatus());
    }

    public function testDisplayStatusReturnsThumbDownForDislike(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), false);

        $representation = $this->likeAdapter->getRepresentation($like);

        $this->assertEquals('🖓', $representation->displayStatus());
    }

    public function testGetJsonLdReturnsCorrectStructure(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $jsonLd = $representation->getJsonLd();

        $this->assertIsArray($jsonLd);
        $this->assertArrayHasKey('o:id', $jsonLd);
        $this->assertArrayHasKey('o:owner', $jsonLd);
        $this->assertArrayHasKey('o:resource', $jsonLd);
        $this->assertArrayHasKey('o-module-🖒:liked', $jsonLd);
        $this->assertArrayHasKey('o:created', $jsonLd);
        $this->assertArrayHasKey('o:modified', $jsonLd);
    }

    public function testGetJsonLdTypeReturnsCorrectType(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $this->assertEquals('o-module-🖒:Like', $representation->getJsonLdType());
    }

    public function testGetControllerNameReturnsLike(): void
    {
        $user = $this->createTestUser();
        $item = $this->createTestItem();
        $like = $this->createLikeEntity($item->id(), $user->id(), true);

        $representation = $this->likeAdapter->getRepresentation($like);

        $this->assertEquals('like', $representation->getControllerName());
    }
}
