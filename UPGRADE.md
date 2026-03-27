# Upgrade Guide

## Upgrading from 0.7 to 1.0

Version 1.0 modernizes the codebase with PHP 8.1+ features, removes deprecated methods, and drops Flash/object embed support (iframe-only).

### Breaking Changes

#### Removed Methods

| 0.7 Method | 1.0 Replacement |
|------------|-----------------|
| `addProvider(array $data)` | `addProviderConfig(ProviderConfig::fromArray($data))` |
| `getHost(string $slug)` | `getProvider(string $slug)` |
| `getHostOrFail(string $slug)` | `getProviderOrFail(string $slug)` |
| `setHosts(array $stubs)` | Use constructor with `ProviderLoaderInterface` or `addProviderConfig()` |
| `object(string $slug)` | `parseId(string $id, string $host)` or `getProvider(string $slug)` |

#### Removed Flash Support

The `<object>` embed mode has been removed. All embeds now use `<iframe>` exclusively.

Removed from provider configuration:
- `embed-src` - Flash player URL
- `flashvars` - Flash variables

If you have custom providers using these keys, remove them.

#### Property Visibility Changes

| Property | Change |
|----------|--------|
| `MediaObject::$config` | `public` → `protected` |
| `MediaEmbed::object()` | `public` → `protected` |
| `MediaEmbed::setHosts()` | Removed (was `public`) |

#### Type Changes

- `ProviderConfig::$embedWidth` - Now `int` (was `int|string`)
- `ProviderConfig::$embedHeight` - Now `int` (was `int|string`)

The `fromArray()` method automatically casts string values to integers.

### Migration Examples

#### Adding Custom Providers

```php
// 0.7
$mediaEmbed->addProvider([
    'name' => 'MyProvider',
    'website' => 'https://example.com',
    'url-match' => 'example\\.com/v/([a-z0-9]+)',
    'embed-width' => '640',
    'embed-height' => '360',
    'iframe-player' => '//example.com/embed/$2',
]);

// 1.0
use MediaEmbed\Provider\ProviderConfig;

$mediaEmbed->addProviderConfig(ProviderConfig::fromArray([
    'name' => 'MyProvider',
    'website' => 'https://example.com',
    'url-match' => 'example\\.com/v/([a-z0-9]+)',
    'embed-width' => '640',
    'embed-height' => '360',
    'iframe-player' => '//example.com/embed/$2',
]));

// Or using the DTO directly
$mediaEmbed->addProviderConfig(new ProviderConfig(
    name: 'MyProvider',
    website: 'https://example.com',
    urlMatch: 'example\\.com/v/([a-z0-9]+)',
    embedWidth: 640,
    embedHeight: 360,
    iframePlayer: '//example.com/embed/$2',
));
```

#### Getting Provider Information

```php
// 0.7
$host = $mediaEmbed->getHost('youtube');
$host = $mediaEmbed->getHostOrFail('youtube');

// 1.0
$provider = $mediaEmbed->getProvider('youtube');        // Returns ProviderConfig|null
$provider = $mediaEmbed->getProviderOrFail('youtube');  // Throws ProviderNotFoundException
```

#### Creating MediaObject by Host

```php
// 0.7
$object = $mediaEmbed->object('youtube');

// 1.0 - Use parseId with the video ID
$object = $mediaEmbed->parseId('dQw4w9WgXcQ', 'youtube');

// Or get provider config directly
$provider = $mediaEmbed->getProvider('youtube');
```

#### Bulk Loading Providers

```php
// 0.7
$mediaEmbed->setHosts($providers, $reset = true);

// 1.0 - Use constructor with custom loader
use MediaEmbed\Provider\ArrayLoader;

$mediaEmbed = new MediaEmbed(
    providerLoader: new ArrayLoader($providers),
);

// Or load from file
use MediaEmbed\Provider\JsonFileLoader;

$mediaEmbed = new MediaEmbed(
    providerLoader: new JsonFileLoader('/path/to/providers.json'),
);
```

### New Features in 1.0

#### Exception Handling

New `*OrFail()` methods throw specific exceptions:

```php
use MediaEmbed\Exception\InvalidUrlException;
use MediaEmbed\Exception\ProviderNotFoundException;
use MediaEmbed\Exception\FetchException;

try {
    $media = $mediaEmbed->parseUrlOrFail($url);
} catch (InvalidUrlException $e) {
    // URL not supported by any provider
} catch (FetchException $e) {
    // Provider requires fetch-match but HTTP request failed
}
```

#### Dependency Injection

Inject custom HTTP client for testing or caching:

```php
use MediaEmbed\Http\HttpClientInterface;

class CachingHttpClient implements HttpClientInterface {
    public function get(string $url, array $options = []): ?string {
        // Your implementation
    }
}

$mediaEmbed = new MediaEmbed(
    httpClient: new CachingHttpClient(),
);
```

#### Provider DTOs

Type-safe provider configuration:

```php
$provider = $mediaEmbed->getProvider('youtube');

echo $provider->name;           // "YouTube"
echo $provider->website;        // "https://www.youtube.com"
echo $provider->embedWidth;     // 480
echo $provider->embedHeight;    // 295
echo $provider->iframePlayer;   // "//www.youtube.com/embed/$2"

// Check capabilities
$provider->hasIframeSupport();    // true
$provider->hasThumbnailSupport(); // true
$provider->requiresFetch();       // false
```

#### Provider Collections

Work with filtered provider lists:

```php
$providers = $mediaEmbed->getProviders();                    // All providers
$providers = $mediaEmbed->getProviders(['youtube', 'vimeo']); // Filtered

foreach ($providers as $provider) {
    echo $provider->name;
}

$providers->has('youtube');  // true
$providers->get('youtube');  // ProviderConfig
$providers->slugs();         // ['youtube', 'vimeo', ...]
```
