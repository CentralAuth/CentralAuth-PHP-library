<?php

namespace CentralAuth\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class CentralAuth extends AbstractProvider
{
  protected $baseAuthorizeUrl;
  protected $baseTokenUrl;
  protected $resourceOwnerDetailsUrl;
  protected $domain;

  public function __construct(array $options = [], array $collaborators = [])
  {
    $this->baseAuthorizeUrl = $options['authorization_url'] ?? $options['urlAuthorize'] ?? '';
    $this->baseTokenUrl = $options['token_url'] ?? $options['urlAccessToken'] ?? '';
    $this->resourceOwnerDetailsUrl = $options['resource_owner_details_url'] ?? $options['urlResourceOwnerDetails'] ?? '';
    $this->domain = $options['domain'] ?? $this->baseAuthorizeUrl;

    parent::__construct([
      'clientId' => $options['clientId'] ?? $options['client_id'] ?? '',
      'clientSecret' => $options['clientSecret'] ?? $options['client_secret'] ?? '',
      'redirectUri' => $options['redirectUri'] ?? $options['redirect_uri'] ?? '',
    ], $collaborators);
  }

  public function getBaseAuthorizationUrl()
  {
    return $this->baseAuthorizeUrl;
  }

  public function getBaseAccessTokenUrl(array $params)
  {
    return $this->baseTokenUrl;
  }

  public function getResourceOwnerDetailsUrl(AccessToken $token)
  {
    $domainParam = $this->domain ? '?domain=' . urlencode($this->domain) : '';
    return $this->resourceOwnerDetailsUrl . $domainParam;
  }

  protected function getDefaultScopes()
  {
    return [];
  }

  protected function checkResponse(ResponseInterface $response, $data)
  {
    $status = $response->getStatusCode();
    if ($status >= 400) {
      $message = 'Unknown error';
      if (is_array($data)) {
        $message = $data['error_description'] ?? $data['error'] ?? json_encode($data);
      } elseif (is_string($data)) {
        $message = $data;
      }
      throw new IdentityProviderException($message, $status, $response);
    }
  }

  protected function createResourceOwner(array $response, AccessToken $token)
  {
    return new CentralAuthResourceOwner($response);
  }

  protected function getAuthorizationHeaders($token = null)
  {
    if ($token) {
      $t = is_string($token) ? $token : $token->getToken();
      return ['Authorization' => 'Bearer ' . $t];
    }
    return [];
  }

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
