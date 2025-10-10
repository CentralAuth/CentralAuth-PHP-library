<?php

namespace CentralAuth\OAuth2\Client\Test\Unit\Provider;

use CentralAuth\OAuth2\Client\Provider\CentralAuthResourceOwner;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the CentralAuth Resource Owner
 */
class CentralAuthResourceOwnerTest extends TestCase
{
  /**
   * @var array
   */
  protected $sampleResponse;

  protected function setUp(): void
  {
    $this->sampleResponse = [
      'id' => 12345,
      'email' => 'test@example.com',
      'gravatar' => 'https://gravatar.com/avatar/abc123def456',
      'username' => 'testuser',
      'created_at' => '2023-01-01T00:00:00Z',
      'updated_at' => '2023-12-01T12:00:00Z'
    ];
  }

  public function testConstructorStoresResponse()
  {
    $resourceOwner = new CentralAuthResourceOwner($this->sampleResponse);

    $this->assertEquals($this->sampleResponse, $resourceOwner->toArray());
  }

  public function testGetIdReturnsCorrectValue()
  {
    $resourceOwner = new CentralAuthResourceOwner($this->sampleResponse);

    $this->assertEquals(12345, $resourceOwner->getId());
  }

  public function testGetIdReturnsNullWhenNotSet()
  {
    $response = $this->sampleResponse;
    unset($response['id']);

    $resourceOwner = new CentralAuthResourceOwner($response);

    $this->assertNull($resourceOwner->getId());
  }

  public function testGetEmailReturnsCorrectValue()
  {
    $resourceOwner = new CentralAuthResourceOwner($this->sampleResponse);

    $this->assertEquals('test@example.com', $resourceOwner->getEmail());
  }

  public function testGetEmailReturnsNullWhenNotSet()
  {
    $response = $this->sampleResponse;
    unset($response['email']);

    $resourceOwner = new CentralAuthResourceOwner($response);

    $this->assertNull($resourceOwner->getEmail());
  }

  public function testGetNameAlwaysReturnsNull()
  {
    $resourceOwner = new CentralAuthResourceOwner($this->sampleResponse);

    $this->assertNull($resourceOwner->getName());
  }

  public function testGetNameReturnsNullEvenWithNameInResponse()
  {
    $response = $this->sampleResponse;
    $response['name'] = 'Test User';

    $resourceOwner = new CentralAuthResourceOwner($response);

    $this->assertNull($resourceOwner->getName());
  }

  public function testGetGravatarReturnsCorrectValue()
  {
    $resourceOwner = new CentralAuthResourceOwner($this->sampleResponse);

    $this->assertEquals('https://gravatar.com/avatar/abc123def456', $resourceOwner->getGravatar());
  }

  public function testGetGravatarReturnsNullWhenNotSet()
  {
    $response = $this->sampleResponse;
    unset($response['gravatar']);

    $resourceOwner = new CentralAuthResourceOwner($response);

    $this->assertNull($resourceOwner->getGravatar());
  }

  public function testToArrayReturnsCompleteResponse()
  {
    $resourceOwner = new CentralAuthResourceOwner($this->sampleResponse);

    $result = $resourceOwner->toArray();

    $this->assertIsArray($result);
    $this->assertEquals($this->sampleResponse, $result);
    $this->assertCount(6, $result);
  }

  public function testToArrayWithEmptyResponse()
  {
    $resourceOwner = new CentralAuthResourceOwner([]);

    $result = $resourceOwner->toArray();

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  public function testIdCanBeString()
  {
    $response = $this->sampleResponse;
    $response['id'] = 'user-uuid-12345';

    $resourceOwner = new CentralAuthResourceOwner($response);

    $this->assertEquals('user-uuid-12345', $resourceOwner->getId());
  }

  public function testIdCanBeInteger()
  {
    $response = $this->sampleResponse;
    $response['id'] = 98765;

    $resourceOwner = new CentralAuthResourceOwner($response);

    $this->assertEquals(98765, $resourceOwner->getId());
  }

  public function testEmailWithDifferentFormats()
  {
    $testEmails = [
      'simple@example.com',
      'user.name@example.com',
      'user+tag@example.co.uk',
      'user@sub.domain.com'
    ];

    foreach ($testEmails as $email) {
      $response = $this->sampleResponse;
      $response['email'] = $email;

      $resourceOwner = new CentralAuthResourceOwner($response);

      $this->assertEquals($email, $resourceOwner->getEmail());
    }
  }

  public function testGravatarWithDifferentUrls()
  {
    $testUrls = [
      'https://gravatar.com/avatar/hash123',
      'https://www.gravatar.com/avatar/hash456?s=200',
      'https://secure.gravatar.com/avatar/hash789?d=mm&r=g'
    ];

    foreach ($testUrls as $url) {
      $response = $this->sampleResponse;
      $response['gravatar'] = $url;

      $resourceOwner = new CentralAuthResourceOwner($response);

      $this->assertEquals($url, $resourceOwner->getGravatar());
    }
  }

  public function testWithMinimalResponse()
  {
    $minimalResponse = [
      'id' => 1
    ];

    $resourceOwner = new CentralAuthResourceOwner($minimalResponse);

    $this->assertEquals(1, $resourceOwner->getId());
    $this->assertNull($resourceOwner->getEmail());
    $this->assertNull($resourceOwner->getName());
    $this->assertNull($resourceOwner->getGravatar());
    $this->assertEquals($minimalResponse, $resourceOwner->toArray());
  }

  public function testWithAdditionalFields()
  {
    $extendedResponse = $this->sampleResponse;
    $extendedResponse['roles'] = ['user', 'admin'];
    $extendedResponse['preferences'] = ['theme' => 'dark', 'language' => 'en'];

    $resourceOwner = new CentralAuthResourceOwner($extendedResponse);

    // Standard fields still work
    $this->assertEquals(12345, $resourceOwner->getId());
    $this->assertEquals('test@example.com', $resourceOwner->getEmail());

    // All data is preserved in toArray
    $result = $resourceOwner->toArray();
    $this->assertArrayHasKey('roles', $result);
    $this->assertArrayHasKey('preferences', $result);
    $this->assertEquals(['user', 'admin'], $result['roles']);
    $this->assertEquals(['theme' => 'dark', 'language' => 'en'], $result['preferences']);
  }
}
