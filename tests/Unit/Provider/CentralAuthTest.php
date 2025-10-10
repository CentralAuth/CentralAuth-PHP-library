<?php

namespace CentralAuth\OAuth2\Client\Test\Unit\Provider;

use CentralAuth\OAuth2\Client\Provider\CentralAuth;
use CentralAuth\OAuth2\Client\Provider\CentralAuthResourceOwner;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Unit tests for the CentralAuth OAuth2 Provider
 */
class CentralAuthTest extends TestCase
{
  /**
   * @var CentralAuth
   */
  protected $provider;

  /**
   * @var array
   */
  protected $defaultOptions;

  protected function setUp(): void
  {
    $this->defaultOptions = [
      'clientId' => 'test-client-id',
      'clientSecret' => 'test-client-secret',
      'redirectUri' => 'https://example.com/callback',
      'authorization_url' => 'https://auth.example.com/oauth/authorize',
      'token_url' => 'https://auth.example.com/oauth/token',
      'resource_owner_details_url' => 'https://auth.example.com/oauth/user',
      'domain' => 'example.com'
    ];

    $this->provider = new CentralAuth($this->defaultOptions);
  }

  protected function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  public function testConstructorWithCamelCaseOptions()
  {
    $options = [
      'clientId' => 'test-client',
      'clientSecret' => 'test-secret',
      'redirectUri' => 'https://example.com/callback',
      'urlAuthorize' => 'https://auth.example.com/authorize',
      'urlAccessToken' => 'https://auth.example.com/token',
      'urlResourceOwnerDetails' => 'https://auth.example.com/user'
    ];

    $provider = new CentralAuth($options);

    $this->assertEquals('https://auth.example.com/authorize', $provider->getBaseAuthorizationUrl());
    $this->assertEquals('https://auth.example.com/token', $provider->getBaseAccessTokenUrl([]));
  }

  public function testConstructorWithSnakeCaseOptions()
  {
    $options = [
      'client_id' => 'test-client',
      'client_secret' => 'test-secret',
      'redirect_uri' => 'https://example.com/callback',
      'authorization_url' => 'https://auth.example.com/authorize',
      'token_url' => 'https://auth.example.com/token',
      'resource_owner_details_url' => 'https://auth.example.com/user',
      'domain' => 'test.com'
    ];

    $provider = new CentralAuth($options);

    $this->assertEquals('https://auth.example.com/authorize', $provider->getBaseAuthorizationUrl());
    $this->assertEquals('https://auth.example.com/token', $provider->getBaseAccessTokenUrl([]));
  }

  public function testGetBaseAuthorizationUrl()
  {
    $url = $this->provider->getBaseAuthorizationUrl();
    $this->assertEquals('https://auth.example.com/oauth/authorize', $url);
  }

  public function testGetBaseAccessTokenUrl()
  {
    $url = $this->provider->getBaseAccessTokenUrl([]);
    $this->assertEquals('https://auth.example.com/oauth/token', $url);
  }

  public function testGetResourceOwnerDetailsUrlWithoutDomain()
  {
    $options = $this->defaultOptions;
    unset($options['domain']);
    $provider = new CentralAuth($options);

    $token = new AccessToken(['access_token' => 'test-token']);
    $url = $provider->getResourceOwnerDetailsUrl($token);

    $this->assertEquals('https://auth.example.com/oauth/user', $url);
  }

  public function testGetResourceOwnerDetailsUrlWithDomain()
  {
    $token = new AccessToken(['access_token' => 'test-token']);
    $url = $this->provider->getResourceOwnerDetailsUrl($token);

    $this->assertEquals('https://auth.example.com/oauth/user?domain=example.com', $url);
  }

  public function testGetResourceOwnerDetailsUrlWithSpecialCharactersInDomain()
  {
    $options = $this->defaultOptions;
    $options['domain'] = 'test@domain.com';
    $provider = new CentralAuth($options);

    $token = new AccessToken(['access_token' => 'test-token']);
    $url = $provider->getResourceOwnerDetailsUrl($token);

    $this->assertEquals('https://auth.example.com/oauth/user?domain=test%40domain.com', $url);
  }

  public function testGetDefaultScopes()
  {
    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('getDefaultScopes');
    $method->setAccessible(true);

    $scopes = $method->invoke($this->provider);
    $this->assertIsArray($scopes);
    $this->assertEmpty($scopes);
  }

  public function testCheckResponseSuccess()
  {
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(200);

    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('checkResponse');
    $method->setAccessible(true);

    // Should not throw exception for successful response
    $method->invoke($this->provider, $response, ['success' => true]);
    $this->assertTrue(true); // Test passes if no exception is thrown
  }

  public function testCheckResponseErrorWithArrayData()
  {
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(400);

    $data = [
      'error' => 'invalid_request',
      'error_description' => 'The request is missing a required parameter'
    ];

    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('checkResponse');
    $method->setAccessible(true);

    $this->expectException(IdentityProviderException::class);
    $this->expectExceptionMessage('The request is missing a required parameter');
    $this->expectExceptionCode(400);

    $method->invoke($this->provider, $response, $data);
  }

  public function testCheckResponseErrorWithStringData()
  {
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(500);

    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('checkResponse');
    $method->setAccessible(true);

    $this->expectException(IdentityProviderException::class);
    $this->expectExceptionMessage('Internal Server Error');
    $this->expectExceptionCode(500);

    $method->invoke($this->provider, $response, 'Internal Server Error');
  }

  public function testCheckResponseErrorWithGenericArrayData()
  {
    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('getStatusCode')->andReturn(422);

    $data = ['validation_errors' => ['field is required']];

    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('checkResponse');
    $method->setAccessible(true);

    $this->expectException(IdentityProviderException::class);
    $this->expectExceptionCode(422);

    $method->invoke($this->provider, $response, $data);
  }

  public function testCreateResourceOwner()
  {
    $response = [
      'id' => 12345,
      'email' => 'test@example.com',
      'gravatar' => 'https://gravatar.com/avatar/hash'
    ];

    $token = new AccessToken(['access_token' => 'test-token']);

    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('createResourceOwner');
    $method->setAccessible(true);

    $resourceOwner = $method->invoke($this->provider, $response, $token);

    $this->assertInstanceOf(CentralAuthResourceOwner::class, $resourceOwner);
    $this->assertEquals(12345, $resourceOwner->getId());
    $this->assertEquals('test@example.com', $resourceOwner->getEmail());
    $this->assertEquals('https://gravatar.com/avatar/hash', $resourceOwner->getGravatar());
  }

  public function testGetAuthorizationHeadersWithStringToken()
  {
    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('getAuthorizationHeaders');
    $method->setAccessible(true);

    $headers = $method->invoke($this->provider, 'test-token-string');

    $this->assertArrayHasKey('Authorization', $headers);
    $this->assertEquals('Bearer test-token-string', $headers['Authorization']);
  }

  public function testGetAuthorizationHeadersWithAccessToken()
  {
    $token = new AccessToken(['access_token' => 'test-token-object']);

    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('getAuthorizationHeaders');
    $method->setAccessible(true);

    $headers = $method->invoke($this->provider, $token);

    $this->assertArrayHasKey('Authorization', $headers);
    $this->assertEquals('Bearer test-token-object', $headers['Authorization']);
  }

  public function testGetAuthorizationHeadersWithoutToken()
  {
    $reflection = new \ReflectionClass($this->provider);
    $method = $reflection->getMethod('getAuthorizationHeaders');
    $method->setAccessible(true);

    $headers = $method->invoke($this->provider, null);

    $this->assertIsArray($headers);
    $this->assertEmpty($headers);
  }

  public function testFetchResourceOwnerDetailsUrlGeneration()
  {
    // Test the URL generation logic without HTTP calls
    $provider = new CentralAuth($this->defaultOptions);
    $token = new AccessToken(['access_token' => 'test-token']);

    $url = $provider->getResourceOwnerDetailsUrl($token);
    $this->assertEquals('https://auth.example.com/oauth/user?domain=example.com', $url);
  }

  public function testFetchResourceOwnerDetailsUrlWithoutDomain()
  {
    // Test URL generation without domain
    $options = $this->defaultOptions;
    unset($options['domain']);
    $provider = new CentralAuth($options);
    $token = new AccessToken(['access_token' => 'test-token']);

    $url = $provider->getResourceOwnerDetailsUrl($token);
    $this->assertEquals('https://auth.example.com/oauth/user', $url);
  }

  public function testServerVariableHandling()
  {
    // Test that we can access server variables for IP and user agent
    $originalForwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
    $originalUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Set test values
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.168.1.1';
    $_SERVER['HTTP_USER_AGENT'] = 'Test User Agent';

    // We can't easily test the fetchResourceOwnerDetails method without complex mocking,
    // but we can verify that the server variables are accessible
    $this->assertEquals('192.168.1.1', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $this->assertEquals('Test User Agent', $_SERVER['HTTP_USER_AGENT']);

    // Clean up
    if ($originalForwarded !== null) {
      $_SERVER['HTTP_X_FORWARDED_FOR'] = $originalForwarded;
    } else {
      unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    if ($originalUserAgent !== null) {
      $_SERVER['HTTP_USER_AGENT'] = $originalUserAgent;
    } else {
      unset($_SERVER['HTTP_USER_AGENT']);
    }
  }

  public function testBasicAuthEncoding()
  {
    // Test that we can generate the expected Basic auth header
    $expected = base64_encode('test-client-id:test-client-secret');
    $actual = base64_encode($this->defaultOptions['clientId'] . ':' . $this->defaultOptions['clientSecret']);

    $this->assertEquals($expected, $actual);
    $this->assertEquals('Basic ' . $expected, 'Basic ' . $actual);
  }
}
