<?php

namespace CentralAuth\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class CentralAuthResourceOwner implements ResourceOwnerInterface
{
  protected $response;

  public function __construct(array $response)
  {
    $this->response = $response;
  }

  public function getId()
  {
    return $this->response['id'] ?? $this->response['user_id'] ?? null;
  }

  public function getEmail()
  {
    return $this->response['email'] ?? null;
  }

  public function getName()
  {
    return $this->response['name'] ?? ($this->response['username'] ?? null);
  }

  public function getGravatar()
  {
    return $this->response['gravatar'] ?? null;
  }

  public function toArray()
  {
    return $this->response;
  }
}
