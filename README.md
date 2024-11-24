<h1 align="center">Matomo API Client</h1>

[![Packagist](https://img.shields.io/packagist/v/tomkyle/boilerplate-php.svg?style=flat)](https://packagist.org/packages/tomkyle/boilerplate-php )
[![PHP version](https://img.shields.io/packagist/php-v/tomkyle/boilerplate-php.svg)](https://packagist.org/packages/tomkyle/boilerplate-php )
[![PHP Composer](https://github.com/tomkyle/boilerplate-php/actions/workflows/php.yml/badge.svg)](https://github.com/tomkyle/boilerplate-php/actions/workflows/php.yml)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)

**A PHP client library for interacting with the Matomo API, providing robust and extensible functionality for managing and querying Matomo analytics data.**

## Features

- Supports retry logic with `RetryingMatomoApiClient` for handling transient failures.
- Caching with `Psr6CacheMatomoApiClient` to minimize API calls and improve performance.
- Fully PSR-compliant with PSR-6 (Caching), PSR-3 (Logging), PSR-17 (HTTP Factories), and PSR-18 (HTTP Client).
- Extensible via the `MatomoApiClientInterface` for standardized requests to the Matomo API.

## Installation

Install via Composer:

```bash
composer require tomkyle/matomo-api-client
```

## Usage

### Basic API Client

Class **MatomoApiClient** is the primary client for sending requests to the Matomo API. The API response is returned as an associative array:

```php
use tomkyle\MatomoApiClient\MatomoApiClient;
use GuzzleHttp\Psr7\Uri;

// Initialize the client
$uri = new Uri('https://your-matomo-domain.com');
$defaults = ['token_auth' => 'your-api-token'];
$client = new MatomoApiClient($uri, $defaults);

// Send a request
$params = ['idSite' => '1', 'period' => 'day', 'date' => 'today'];
$response = $client->request($params, 'VisitsSummary.get');

print_r($response);
```

### The Interface

All classes in this package implement the **MatomoApiClientInterface** interface with a **request** method which returns the API response as an associative array. The method accepts the Matomo API parameters as well as an optional API method—See [Matomo docs](https://developer.matomo.org/api-reference/reporting-api) for more information:

```php
/**
 * @param  array<string,string>  $params API params
 * @return array<string|int,mixed> API result
 */
public function request(array $params, string $method = null): array;
```

### Retrying Client

The **RetryingMatomoApiClient** wraps the above *MatomoApiClient* instance. When a request fails due to server load, it will retry the request up to a specified number of attempts.

```php
use tomkyle\MatomoApiClient\RetryingMatomoApiClient;

// Maximum 3 attempts, 5 seconds wait
$retryingClient = new RetryingMatomoApiClient($client, 3, 5); 

// Send a request
$params = ['idSite' => '1', 'period' => 'day', 'date' => 'today'];
$response = $retryingClient->request($params, 'VisitsSummary.get');
```

### Caching Client

The **Psr6CacheMatomoApiClient** wraps any *MatomoApiClientInterface* instance and integrates with any PSR-6 compliant cache pool.

```php
use tomkyle\MatomoApiClient\Psr6CacheMatomoApiClient;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$cachePool = new FilesystemAdapter();
$cachingClient = new Psr6CacheMatomoApiClient($cachePool, $client);

$response = $cachingClient->request($params, 'VisitsSummary.get');
```

## Exceptions

- **MatomoApiClientException:** Thrown for errors during API requests or response handling.

---

## License

This library is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

## Links

- [Matomo API Documentation](https://developer.matomo.org/api-reference/reporting-api)
- [GitHub Repository](https://github.com/tomkyle/matomo-api-client)

---

## Development

Run `npm update` to install development helpers. Watch the file system for PHP code changes using `npm run watch` and see what *phpstan, phpcs, Rector,* and *PhpUnit* say. See [package.json](package.json) for a list of all watch and test tasks.

```bash
$ npm run watch
```





