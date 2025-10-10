# Testing Guide for CentralAuth PHP Library

This guide explains how to run the tests for the CentralAuth OAuth2 provider library.

## Prerequisites

Make sure you have Composer installed and run:

```bash
composer install
```

This will install all required dependencies including PHPUnit, Mockery, and other testing tools.

## Quick Validation

For a fast environment check and syntax validation (works without PHP extensions):

```bash
./test-runner.sh
```

This script checks:
- ✅ PHP availability and version
- ✅ Composer installation 
- ✅ Dependencies installed
- ✅ Source code syntax validation
- ✅ Test file syntax validation
- ✅ PHPUnit availability

## Running Tests

### Quick Commands (Recommended)

```bash
# Run all tests - fast, clean, no warnings (44 tests)
composer test

# Run only unit tests (37 tests - CentralAuth + CentralAuthResourceOwner)
composer test-unit

# Run only integration tests (7 tests - OAuth2 workflows)
composer test-integration

# Generate code coverage report (requires Xdebug or PCOV extension)
composer test-coverage
```

### Direct PHPUnit Commands

If you prefer using PHPUnit directly:

```bash
# All tests with no coverage
./vendor/bin/phpunit --no-coverage

# Specific test suites
./vendor/bin/phpunit --testsuite "Unit Tests" --no-coverage
./vendor/bin/phpunit --testsuite "Integration Tests" --no-coverage

# Specific test files
./vendor/bin/phpunit tests/Unit/Provider/CentralAuthTest.php --no-coverage
./vendor/bin/phpunit tests/Unit/Provider/CentralAuthResourceOwnerTest.php --no-coverage
./vendor/bin/phpunit tests/Integration/CentralAuthIntegrationTest.php --no-coverage
```

### Code Coverage

To generate coverage reports, you need either **Xdebug** or **PCOV** extension:

```bash
# Install Xdebug (Ubuntu/Debian)
sudo apt-get install php-xdebug

# Then run coverage
composer test-coverage
```

**Note**: If no coverage driver is installed, `composer test-coverage` will show a helpful error message with installation instructions.

## Test Structure

### Unit Tests (`tests/Unit/`)

- **CentralAuthTest.php**: Tests for the main `CentralAuth` provider class
  - Constructor with different option formats (camelCase, snake_case)
  - URL generation methods (authorization, token, resource owner details)
  - Error handling and response checking
  - Authorization headers generation
  - Resource owner details fetching

- **CentralAuthResourceOwnerTest.php**: Tests for the `CentralAuthResourceOwner` class
  - All getter methods (`getId()`, `getEmail()`, `getName()`, `getGravatar()`)
  - Data handling with various response formats
  - The `toArray()` method functionality

### Integration Tests (`tests/Integration/`)

- **CentralAuthIntegrationTest.php**: Full OAuth2 workflow tests
  - Complete authorization flow simulation
  - Token exchange and refresh scenarios
  - Resource owner details retrieval with proper headers
  - Error handling across the entire workflow
  - Real-world usage scenarios

## What the Tests Cover

### CentralAuth Provider Tests
- ✅ Constructor flexibility (multiple option formats)
- ✅ URL generation (authorization, token, user details)
- ✅ Domain parameter handling in URLs
- ✅ Default scopes (empty array)
- ✅ Response error checking with various error formats
- ✅ Resource owner creation
- ✅ Authorization header generation
- ✅ Resource owner details fetching with proper authentication
- ✅ Server variable handling (IP, User-Agent)

### CentralAuthResourceOwner Tests
- ✅ All getter methods return correct values or null
- ✅ `getName()` always returns null (as per implementation)
- ✅ Support for different ID types (string, integer)
- ✅ Email format validation
- ✅ Gravatar URL handling
- ✅ Complete response data preservation via `toArray()`
- ✅ Handling of minimal and extended response data

### Integration Tests
- ✅ Complete OAuth2 authorization flow
- ✅ Authorization URL generation with custom parameters
- ✅ Token exchange (authorization code → access token)
- ✅ Token refresh workflow
- ✅ Resource owner details retrieval with proper headers
- ✅ Error handling at each step
- ✅ Domain parameter usage
- ✅ Real-world workflow simulation

## Test Statistics

- **Total Tests**: 44 tests with 100 assertions
- **Unit Tests**: 37 tests (19 CentralAuth + 18 CentralAuthResourceOwner)
- **Integration Tests**: 7 tests (OAuth2 workflow scenarios)
- **Execution Time**: ~0.014 seconds (very fast)
- **Test Files**: 3 test classes with comprehensive coverage

## Testing Best Practices

1. **Mocking**: Tests use Mockery to mock HTTP responses and avoid real network calls
2. **Isolation**: Each test is independent and doesn't affect others
3. **Coverage**: Tests cover both successful scenarios and error conditions
4. **Assertions**: Tests validate both return values and behavior
5. **Edge Cases**: Tests include edge cases like missing data, different formats, etc.

## Continuous Integration

The test suite is designed to be CI-friendly:

- Uses in-memory testing (no external dependencies)
- Generates JUnit XML output for CI systems
- Provides code coverage metrics
- Fast execution with minimal dependencies

## Adding New Tests

When adding new functionality:

1. Add unit tests for individual methods/classes in `tests/Unit/`
2. Add integration tests for complete workflows in `tests/Integration/`
3. Follow the existing naming conventions
4. Use proper docblocks and descriptive test method names
5. Mock external dependencies to ensure test isolation

## Troubleshooting

### Common Issues

1. **Missing dependencies**: Run `composer install` to install testing dependencies
2. **Permission errors**: Ensure the `coverage/` directory is writable
3. **Memory issues**: Increase PHP memory limit if needed: `php -d memory_limit=512M vendor/bin/phpunit`

### Debug Mode

To run tests with more verbose output:

```bash
./vendor/bin/phpunit --verbose --debug
```

To see which tests are being skipped or have issues:

```bash
./vendor/bin/phpunit --testdox
```