<?php

namespace CentralAuth\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

/**
 * CentralAuth OAuth2 Provider
 * 
 * This class implements the OAuth2 authorization flow for CentralAuth services.
 * It extends the League OAuth2 Client AbstractProvider to provide CentralAuth-specific
 * functionality for authorization, token exchange, and resource owner details retrieval.
 */
class CentralAuth extends AbstractProvider
{
  /**
   * The base URL for OAuth2 authorization requests
   * @var string
   */
  protected $baseAuthorizeUrl;

  /**
   * The base URL for OAuth2 token requests
   * @var string
   */
  protected $baseTokenUrl;

  /**
   * The URL for retrieving resource owner details
   * @var string
   */
  protected $resourceOwnerDetailsUrl;

  /**
   * The domain parameter used in resource owner details requests
   * @var string
   */
  protected $domain;

  /**
   * Constructor for the CentralAuth provider
   * 
   * Initializes the CentralAuth OAuth2 provider with the given configuration options.
   * Supports flexible option naming (both snake_case and camelCase variants).
   * 
   * @param array $options Configuration options including:
   *   - authorization_url|urlAuthorize: The authorization endpoint URL
   *   - token_url|urlAccessToken: The token endpoint URL  
   *   - resource_owner_details_url|urlResourceOwnerDetails: The user info endpoint URL
   *   - domain|redirectUri|redirect_uri: The domain parameter for requests
   *   - clientId|client_id: The OAuth2 client ID
   *   - clientSecret|client_secret: The OAuth2 client secret
   *   - redirectUri|redirect_uri: The OAuth2 redirect URI
   * @param array $collaborators Additional collaborators for the OAuth2 client
   */
  public function __construct(array $options = [], array $collaborators = [])
  {
    $this->baseAuthorizeUrl = $options['authorization_url'] ?? $options['urlAuthorize'] ?? '';
    $this->baseTokenUrl = $options['token_url'] ?? $options['urlAccessToken'] ?? '';
    $this->resourceOwnerDetailsUrl = $options['resource_owner_details_url'] ?? $options['urlResourceOwnerDetails'] ?? '';
    $this->domain = $options['domain'] ?? null;

    parent::__construct([
      'clientId' => $options['clientId'] ?? $options['client_id'] ?? '',
      'clientSecret' => $options['clientSecret'] ?? $options['client_secret'] ?? '',
      'redirectUri' => $options['redirectUri'] ?? $options['redirect_uri'] ?? '',
    ], $collaborators);
  }

  /**
   * Get the base authorization URL
   * 
   * Returns the URL where users will be redirected to authorize the application.
   * This is the first step in the OAuth2 authorization code flow.
   * 
   * @return string The base authorization URL
   */
  public function getBaseAuthorizationUrl()
  {
    return $this->baseAuthorizeUrl;
  }

  /**
   * Get the base access token URL
   * 
   * Returns the URL where the authorization code will be exchanged for an access token.
   * This is used in the second step of the OAuth2 authorization code flow.
   * 
   * @param array $params Additional parameters for the token request (not used in this implementation)
   * @return string The base access token URL
   */
  public function getBaseAccessTokenUrl(array $params)
  {
    return $this->baseTokenUrl;
  }

  /**
   * Get the resource owner details URL
   * 
   * Returns the URL for retrieving information about the authenticated user.
   * Optionally appends a domain parameter if one is configured.
   * 
   * @param AccessToken $token The access token (not used in URL construction but required by interface)
   * @return string The resource owner details URL with optional domain parameter
   */
  public function getResourceOwnerDetailsUrl(AccessToken $token)
  {
    $domainParam = $this->domain ? '?domain=' . urlencode($this->domain) : '';
    return $this->resourceOwnerDetailsUrl . $domainParam;
  }

  /**
   * Get the default scopes for this provider
   * 
   * Returns an array of default OAuth2 scopes that should be requested
   * if no specific scopes are provided. CentralAuth uses no default scopes.
   * 
   * @return array Empty array as CentralAuth has no default scopes
   */
  protected function getDefaultScopes()
  {
    return [];
  }

  /**
   * Check the response for errors
   * 
   * Validates the HTTP response and throws an IdentityProviderException
   * if the response indicates an error (status code >= 400).
   * 
   * @param ResponseInterface $response The HTTP response object
   * @param mixed $data The parsed response data
   * @throws IdentityProviderException If the response indicates an error
   */
  protected function checkResponse(ResponseInterface $response, $data)
  {
    $status = $response->getStatusCode();
    if ($status >= 400) {
      $message = 'Unknown error';
      if (is_array($data)) {
        $message = $data['error_description'] ?? $data['error'] ?? $data['message'] ?? json_encode($data);
      } elseif (is_string($data)) {
        $message = $data;
      }
      throw new IdentityProviderException($message, $status, $response);
    }
  }

  /**
   * Create a resource owner from the response data
   * 
   * Creates and returns a CentralAuthResourceOwner instance populated
   * with the user data from the API response.
   * 
   * @param array $response The parsed response data containing user information
   * @param AccessToken $token The access token (not used but required by interface)
   * @return CentralAuthResourceOwner The resource owner instance
   */
  protected function createResourceOwner(array $response, AccessToken $token)
  {
    return new CentralAuthResourceOwner($response);
  }

  /**
   * Get authorization headers for API requests
   * 
   * Generates the appropriate Authorization header for authenticated API requests.
   * If a token is provided, creates a Bearer token header.
   * 
   * @param string|AccessToken|null $token The access token (string or AccessToken object)
   * @return array Authorization headers array
   */
  protected function getAuthorizationHeaders($token = null)
  {
    if ($token) {
      $t = is_string($token) ? $token : $token->getToken();
      return ['Authorization' => 'Bearer ' . $t];
    }
    return [];
  }

  /**
   * Fetch resource owner details from the CentralAuth API
   * 
   * Makes an authenticated request to retrieve detailed information about
   * the resource owner (authenticated user). Uses Basic authentication
   * with client credentials and includes client IP and user agent information.
   * 
   * @param AccessToken $token The access token for the authenticated user
   * @return array The parsed response containing user details
   * @throws \UnexpectedValueException If the response is not a valid array
   */
  protected function fetchResourceOwnerDetails(AccessToken $token)
  {
    $url = $this->getResourceOwnerDetailsUrl($token);
    $authHeader = 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret);
    $headers = [
      'Authorization' => $authHeader,
      'auth-ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'],
      'user-agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CentralAuth-OAuth2-Client'
    ];
    $request = $this->getRequest(self::METHOD_POST, $url, [
      'headers' => $headers,
      'body' => $token->getToken()
    ]);
    $parsed = $this->getParsedResponse($request);
    if (!is_array($parsed)) {
      throw new \UnexpectedValueException('Invalid user info response');
    }
    return $parsed;
  }
}
