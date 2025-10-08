<?php

namespace CentralAuth\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * CentralAuth Resource Owner
 * 
 * This class represents an authenticated user (resource owner) from the CentralAuth service.
 * It implements the ResourceOwnerInterface from the League OAuth2 Client library and provides
 * access to user information returned by the CentralAuth API.
 */
class CentralAuthResourceOwner implements ResourceOwnerInterface
{
  /**
   * The raw response data from the CentralAuth API
   * @var array
   */
  protected $response;

  /**
   * Constructor for the CentralAuth resource owner
   * 
   * Initializes the resource owner with the response data received from
   * the CentralAuth API containing user information.
   * 
   * @param array $response The raw response data from the CentralAuth API
   */
  public function __construct(array $response)
  {
    $this->response = $response;
  }

  /**
   * Get the unique identifier for the resource owner
   * 
   * Returns the user's unique ID from CentralAuth.
   * 
   * @return string|int|null The user's unique identifier, or null if not available
   */
  public function getId()
  {
    return $this->response['id'] ?? null;
  }

  /**
   * Get the email address of the resource owner
   * 
   * Returns the user's email address if available in the API response.
   * 
   * @return string|null The user's email address, or null if not available
   */
  public function getEmail()
  {
    return $this->response['email'] ?? null;
  }

  /**
   * Get the display name of the resource owner
   * 
   * Returns null as name is not part of the response.
   * This method is part of the ResourceOwnerInterface contract.
   * 
   * @return string|null Always returns null in the current implementation
   */
  public function getName()
  {
    return null;
  }

  /**
   * Get the Gravatar URL for the resource owner
   * 
   * Returns the user's Gravatar image URL if available in the API response.
   * This is a CentralAuth-specific field for user avatar images.
   * 
   * @return string|null The user's Gravatar URL, or null if not available
   */
  public function getGravatar()
  {
    return $this->response['gravatar'] ?? null;
  }

  /**
   * Get all resource owner data as an array
   * 
   * Returns the complete raw response data from the CentralAuth API.
   * This method is required by the ResourceOwnerInterface and provides
   * access to all available user information beyond the standard getters.
   * 
   * @return array The complete user data from the API response
   */
  public function toArray()
  {
    return $this->response;
  }
}
