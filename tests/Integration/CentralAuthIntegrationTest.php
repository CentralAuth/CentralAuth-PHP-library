<?php

namespace CentralAuth\OAuth2\Client\Test\Integration;

use CentralAuth\OAuth2\Client\Provider\CentralAuth;
use CentralAuth\OAuth2\Client\Provider\CentralAuthResourceOwner;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the complete OAuth2 flow with CentralAuth
 */
class CentralAuthIntegrationTest extends TestCase
{
  /**
   * @var CentralAuth
   */
  protected $provider;

  /**
   * @var array
   */
  protected $providerOptions;

  protected function setUp(): void
  {
    $this->providerOptions = [
      'clientId' => 'test-client-id',
      'clientSecret' => 'test-client-secret',
      'redirectUri' => 'https://myapp.com/callback',
      'authorization_url' => 'https://centralauth.example.com/oauth/authorize',
      'token_url' => 'https://centralauth.example.com/oauth/token',
      'resource_owner_details_url' => 'https://centralauth.example.com/api/user',
      'domain' => 'myapp.com'
    ];

    $this->provider = new CentralAuth($this->providerOptions);
  }

  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function testCompleteAuthorizationFlow()
  {
    // Step 1: Generate authorization URL
    $authUrl = $this->provider->getAuthorizationUrl([
      'scope' => ['read', 'write'],
      'state' => 'test-state-value'
    ]);

    $this->assertStringContainsString('https://centralauth.example.com/oauth/authorize', $authUrl);
    $this->assertStringContainsString('client_id=test-client-id', $authUrl);
    $this->assertStringContainsString('redirect_uri=' . urlencode('https://myapp.com/callback'), $authUrl);
    $this->assertStringContainsString('response_type=code', $authUrl);
    $this->assertStringContainsString('scope=read%2Cwrite', $authUrl); // OAuth2 client uses comma separator
    $this->assertStringContainsString('state=test-state-value', $authUrl);

    // Step 2: Verify state matches
    $this->assertEquals('test-state-value', $this->provider->getState());

    // Note: In a real integration test, you would make actual HTTP calls or use a more sophisticated mock
    // For now, we're testing the URL generation and state management which are the key public interfaces
    $this->assertTrue(true); // Test passes if we reach here without errors
  }

  public function testResourceOwnerDetailsUrlGeneration()
  {
    // Test that the resource owner details URL is generated correctly with domain
    $accessToken = new AccessToken(['access_token' => 'test-access-token']);
    $url = $this->provider->getResourceOwnerDetailsUrl($accessToken);

    $this->assertEquals('https://centralauth.example.com/api/user?domain=myapp.com', $url);
  }

  public function testResourceOwnerCreation()
  {
    // Test that we can create a resource owner from response data
    $userResponse = [
      'id' => 'user-123',
      'email' => 'testuser@example.com',
      'gravatar' => 'https://gravatar.com/avatar/hash123',
      'created_at' => '2023-01-01T00:00:00Z',
      'permissions' => ['read', 'write']
    ];

    $accessToken = new AccessToken(['access_token' => 'test-access-token']);

    // Use reflection to test the protected createResourceOwner method
    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('createResourceOwner');
    $method->setAccessible(true);

    $resourceOwner = $method->invoke($this->provider, $userResponse, $accessToken);

    $this->assertInstanceOf(CentralAuthResourceOwner::class, $resourceOwner);
    $this->assertEquals('user-123', $resourceOwner->getId());
    $this->assertEquals('testuser@example.com', $resourceOwner->getEmail());
    $this->assertEquals('https://gravatar.com/avatar/hash123', $resourceOwner->getGravatar());
    $this->assertNull($resourceOwner->getName()); // Should always be null

    // Verify all data is accessible via toArray
    $allData = $resourceOwner->toArray();
    $this->assertEquals($userResponse, $allData);
    $this->assertArrayHasKey('permissions', $allData);
    $this->assertEquals(['read', 'write'], $allData['permissions']);
  }

  public function testTokenExpiration()
  {
    // Test token expiration logic
    $expiredToken = new AccessToken([
      'access_token' => 'old-access-token',
      'refresh_token' => 'old-refresh-token',
      'expires' => time() - 1 // Expired token
    ]);

    $freshToken = new AccessToken([
      'access_token' => 'fresh-access-token',
      'expires' => time() + 3600 // Expires in 1 hour
    ]);

    $this->assertTrue($expiredToken->hasExpired());
    $this->assertFalse($freshToken->hasExpired());
  }
  public function testAuthorizationUrlWithCustomParameters()
  {
    $customParams = [
      'scope' => ['profile', 'email'],
      'state' => 'custom-state-123',
      'login_hint' => 'user@example.com',
      'prompt' => 'consent'
    ];

    $authUrl = $this->provider->getAuthorizationUrl($customParams);

    $this->assertStringContainsString('scope=profile%2Cemail', $authUrl); // OAuth2 client uses comma separator
    $this->assertStringContainsString('state=custom-state-123', $authUrl);
    $this->assertStringContainsString('login_hint=' . urlencode('user@example.com'), $authUrl);
    $this->assertStringContainsString('prompt=consent', $authUrl);
  }

  public function testProviderWithoutDomain()
  {
    $optionsWithoutDomain = $this->providerOptions;
    unset($optionsWithoutDomain['domain']);

    $provider = new CentralAuth($optionsWithoutDomain);
    $accessToken = new AccessToken(['access_token' => 'test-token']);

    $url = $provider->getResourceOwnerDetailsUrl($accessToken);

    // Since we removed domain from options and fixed constructor to not use redirectUri as fallback
    $this->assertEquals('https://centralauth.example.com/api/user', $url);
    $this->assertStringNotContainsString('domain=', $url);
  }

  public function testFullWorkflowUrlGeneration()
  {
    // Test the complete URL generation workflow

    // Step 1: Authorization URL generation
    $authUrl = $this->provider->getAuthorizationUrl(['state' => 'workflow-test']);
    $this->assertStringContainsString('https://centralauth.example.com/oauth/authorize', $authUrl);
    $this->assertStringContainsString('state=workflow-test', $authUrl);

    // Step 2: Verify base URLs are correct
    $this->assertEquals('https://centralauth.example.com/oauth/token', $this->provider->getBaseAccessTokenUrl([]));

    // Step 3: Test resource owner URL generation
    $token = new AccessToken(['access_token' => 'test-token']);
    $resourceUrl = $this->provider->getResourceOwnerDetailsUrl($token);
    $this->assertEquals('https://centralauth.example.com/api/user?domain=myapp.com', $resourceUrl);

    // Step 4: Test default scopes
    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('getDefaultScopes');
    $method->setAccessible(true);
    $scopes = $method->invoke($this->provider);
    $this->assertEmpty($scopes);
  }
}
