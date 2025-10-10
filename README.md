# CentralAuth OAuth2 Provider for league/oauth2-client

[![Packagist Version](https://img.shields.io/packagist/v/centralauth/oauth2-centralauth.svg)](https://packagist.org/packages/centralauth/oauth2-centralauth)
[![License](https://img.shields.io/packagist/l/centralauth/oauth2-centralauth.svg)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/centralauth/oauth2-centralauth.svg)](https://www.php.net/)

>A lightweight, focused OAuth 2.0 provider for integrating CentralAuth with the PHP League's [`league/oauth2-client`](https://github.com/thephpleague/oauth2-client). It wraps CentralAuth-specific behavior so your application code stays clean and portable.

--- 

## 🎯 Live Demo
A demo implementation is available at [https://php-example.centralauth.com](https://php-example.centralauth.com) ([source code](https://github.com/CentralAuth/CentralAuth-PHP-example)).

---

## ✨ Features
* Authorization Code flow (incl. PKCE support inherited from base library)
* CentralAuth-specific user info retrieval
* Automatic Basic auth header for user info (clientId:clientSecret)
* Adds `?domain=` query parameter to user info endpoint
* Custom headers automatically included: `auth-ip`, `user-agent`
* Small surface area – minimal assumptions, easy to extend

---

## ✅ Requirements
| Component            | Version             |
| -------------------- | ------------------- |
| PHP                  | 7.4+ (works on 8.x) |
| league/oauth2-client | ^2.8                |

---

## 📦 Install
```bash
composer require centralauth/oauth2-centralauth
```

---

## 🔧 Configuration Options
| Option                       | Required | Description                                                        |
| ---------------------------- | -------- | ------------------------------------------------------------------ |
| `client_id`                  | Yes      | CentralAuth OAuth client ID                                        |
| `client_secret`              | Yes      | CentralAuth OAuth client secret                                    |
| `redirect_uri`               | Yes      | Your app callback URL (must match configured value)                |
| `authorization_url`          | Yes      | CentralAuth login/authorization endpoint                           |
| `token_url`                  | Yes      | CentralAuth token/verify endpoint                                  |
| `resource_owner_details_url` | Yes      | CentralAuth user info endpoint                                     |
| `domain`                     | No       | Overrides domain passed as `?domain=` (defaults to `redirect_uri`) |

---

## 🚀 Quick Start
```php
use CentralAuth\OAuth2\Client\Provider\CentralAuth;

session_start(); // required for state persistence

$provider = new CentralAuth([
  'clientId' => getenv('CENTRALAUTH_CLIENT_ID'),
  'clientSecret' => getenv('CENTRALAUTH_CLIENT_SECRET'),
  'redirectUri' => 'https://your-app.example/oauth/callback',
  'authorization_url' => 'https://centralauth.com/login',
  'token_url' => 'https://centralauth.com/api/v1/verify',
  'resource_owner_details_url' => 'https://centralauth.com/api/v1/userinfo'
]);

if (!isset($_GET['code'])) {
  $authUrl = $provider->getAuthorizationUrl();
  $_SESSION['oauth2state'] = $provider->getState();
  header('Location: ' . $authUrl);
  exit;
}

if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth2state'] ?? null)) {
  unset($_SESSION['oauth2state']);
  exit('Invalid state');
}

$token = $provider->getAccessToken('authorization_code', [
  'code' => $_GET['code']
]);

$resourceOwner = $provider->getResourceOwner($token);
$user = $resourceOwner->toArray();

// Use $user['email'], $user['id'], etc.
```

---

## 🧠 Why Not `GenericProvider`?
CentralAuth requires a **non-standard user info retrieval pattern**:
1. HTTP Method: `POST`
2. Body: raw access token string (not JSON, not form-encoded)
3. Headers:
   * `Authorization: Basic base64(clientId:clientSecret)`
   * `auth-ip: <requester IP>`
   * `user-agent: <browser UA>`
4. `?domain=` query parameter appended to the user info URL

`GenericProvider` would require re-implementing this logic inline for every project—this package encapsulates it cleanly.

---

## 👤 Resource Owner
Example returned fields:
```json
{
  "id": "12345",
  "email": "user@example.com",
  "gravatar": "https://www.gravatar.com/avatar/..."
}
```
Provided helpers:
```php
$owner = $provider->getResourceOwner($token);
$owner->getId();
$owner->getEmail();
$owner->getGravatar();
$owner->toArray();
```

---

## ⚠️ Error Handling
`checkResponse()` normalizes HTTP errors. An `IdentityProviderException` is thrown containing:
* Exception message (error / error_description / raw body)
* HTTP status code
* Original response instance

Wrap sensitive operations:
```php
try {
  $token = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
} catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
  // Log and surface user-friendly message
}
```

---

## 🧪 Testing

This library includes comprehensive PHPUnit tests covering all functionality:

### Quick Test Validation
For a quick syntax and structure check:
```bash
./test-runner.sh
```

### Test Commands
```bash
# Run all tests (44 tests, fast and clean)
composer test

# Run only unit tests (37 tests)
composer test-unit

# Run only integration tests (7 tests)  
composer test-integration

# Generate coverage report (requires Xdebug or PCOV)
composer test-coverage
```

### Test Coverage
The test suite includes:
- **Unit tests**: Individual class and method testing (CentralAuth, CentralAuthResourceOwner)
- **Integration tests**: Complete OAuth2 workflow testing  
- **Error handling**: Various error scenarios and edge cases
- **No external dependencies**: All HTTP calls are mocked for reliable testing

For detailed testing instructions, see [TESTING.md](TESTING.md).

---

## 🛡 Security
Do NOT publish real credentials in code or VCS.
Report vulnerabilities privately.

---

## 📄 License
Released under the [MIT License](LICENSE).

---

## 📚 Documentation
For complete CentralAuth documentation and API reference, visit the [official docs](https://docs.centralauth.com).