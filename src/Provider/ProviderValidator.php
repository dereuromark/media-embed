<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

use MediaEmbed\Slugger\SluggerInterface;
use MediaEmbed\Slugger\UrlifySlugger;

/**
 * Validates provider configuration arrays.
 */
final class ProviderValidator {

	/**
	 * Slugger used to derive a slug from a provider name.
	 */
	private SluggerInterface $slugger;

	/**
	 * @param \MediaEmbed\Slugger\SluggerInterface|null $slugger
	 */
	public function __construct(?SluggerInterface $slugger = null) {
		$this->slugger = $slugger ?? new UrlifySlugger();
	}

	/**
	 * @var array<string>
	 */
	private const ALLOWED_STATUSES = [
		ProviderConfig::STATUS_ACTIVE,
		ProviderConfig::STATUS_LEGACY,
		ProviderConfig::STATUS_DEPRECATED,
	];

	/**
	 * @var array<string>
	 */
	private const ALLOWED_CATEGORIES = [
		ProviderConfig::CATEGORY_3D,
		ProviderConfig::CATEGORY_AUDIO,
		ProviderConfig::CATEGORY_SOCIAL,
		ProviderConfig::CATEGORY_STREAMING,
		ProviderConfig::CATEGORY_VIDEO,
	];

	/**
	 * @var array<string>
	 */
	private const REQUIRED_FIELDS = [
		'name',
		'website',
		'url-match',
		'embed-width',
		'embed-height',
		'iframe-player',
	];

	/**
	 * Validate provider configuration arrays.
	 *
	 * @param array<array<string, mixed>> $providers
	 * @return array<string> Validation errors.
	 */
	public function validate(array $providers): array {
		$errors = [];
		$slugs = [];

		foreach ($providers as $index => $provider) {
			$label = $this->label($provider, $index);
			$this->validateRequiredFields($provider, $label, $errors);
			$this->validateDimensions($provider, $label, $errors);
			$this->validateUrlMatches($provider, $label, $errors);
			$this->validateUrlTemplates($provider, $label, $errors);
			$this->validateFetchMatch($provider, $label, $errors);
			$this->validateMetadata($provider, $label, $errors);
			$this->validateSlug($provider, $label, $slugs, $errors);
		}

		return $errors;
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string|int $index
	 * @return string
	 */
	private function label(array $provider, string|int $index): string {
		$name = $provider['name'] ?? null;
		if (is_string($name) && $name !== '') {
			return $name;
		}

		return 'provider #' . $index;
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string $label
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateRequiredFields(array $provider, string $label, array &$errors): void {
		foreach (self::REQUIRED_FIELDS as $field) {
			if (!array_key_exists($field, $provider) || $provider[$field] === null || $provider[$field] === '' || $provider[$field] === []) {
				$errors[] = $label . ': missing required field "' . $field . '"';
			}
		}
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string $label
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateDimensions(array $provider, string $label, array &$errors): void {
		foreach (['embed-width', 'embed-height'] as $field) {
			if (!array_key_exists($field, $provider)) {
				continue;
			}
			if (!is_int($provider[$field]) && !is_string($provider[$field])) {
				$errors[] = $label . ': field "' . $field . '" must be an integer or string';
			}
		}
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string $label
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateUrlMatches(array $provider, string $label, array &$errors): void {
		if (!array_key_exists('url-match', $provider)) {
			return;
		}

		$patterns = is_array($provider['url-match']) ? $provider['url-match'] : [$provider['url-match']];
		foreach ($patterns as $pattern) {
			if (!is_string($pattern) || $pattern === '') {
				$errors[] = $label . ': url-match entries must be non-empty strings';

				continue;
			}
			$this->validateRegex($pattern, $label, 'url-match', $errors);
		}
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string $label
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateFetchMatch(array $provider, string $label, array &$errors): void {
		if (!array_key_exists('fetch-match', $provider)) {
			return;
		}

		if (!is_string($provider['fetch-match']) || $provider['fetch-match'] === '') {
			$errors[] = $label . ': fetch-match must be a non-empty string';

			return;
		}

		$this->validateRegex($provider['fetch-match'], $label, 'fetch-match', $errors);
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string $label
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateMetadata(array $provider, string $label, array &$errors): void {
		$this->validateStringEnum($provider, $label, 'status', self::ALLOWED_STATUSES, $errors);
		$this->validateStringEnum($provider, $label, 'category', self::ALLOWED_CATEGORIES, $errors);

		if (array_key_exists('example-url', $provider)) {
			if (!is_string($provider['example-url']) || $provider['example-url'] === '') {
				$errors[] = $label . ': field "example-url" must be a non-empty URL string';
			} else {
				$this->validateTemplateUrl($provider['example-url'], $label, 'example-url', $errors);
			}
		}

		if (array_key_exists('notes', $provider) && (!is_string($provider['notes']) || $provider['notes'] === '')) {
			$errors[] = $label . ': field "notes" must be a non-empty string';
		}
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string $label
	 * @param string $field
	 * @param array<string> $allowed
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateStringEnum(array $provider, string $label, string $field, array $allowed, array &$errors): void {
		if (!array_key_exists($field, $provider)) {
			return;
		}

		if (!is_string($provider[$field]) || !in_array($provider[$field], $allowed, true)) {
			$errors[] = $label . ': field "' . $field . '" must be one of: ' . implode(', ', $allowed);
		}
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string $label
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateUrlTemplates(array $provider, string $label, array &$errors): void {
		foreach (['website', 'iframe-player', 'image-src', 'privacy-player'] as $field) {
			if (!array_key_exists($field, $provider)) {
				continue;
			}

			if (!is_string($provider[$field]) || $provider[$field] === '') {
				$errors[] = $label . ': field "' . $field . '" must be a non-empty URL string';

				continue;
			}

			$this->validateTemplatePlaceholders($provider[$field], $label, $field, $errors);
			$this->validateTemplateUrl($provider[$field], $label, $field, $errors);
		}
	}

	/**
	 * @param string $template
	 * @param string $label
	 * @param string $field
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateTemplatePlaceholders(string $template, string $label, string $field, array &$errors): void {
		if (preg_match('/\\$0\\d*/', $template) !== 1) {
			return;
		}

		$errors[] = $label . ': field "' . $field . '" contains an invalid placeholder';
	}

	/**
	 * @param string $template
	 * @param string $label
	 * @param string $field
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateTemplateUrl(string $template, string $label, string $field, array &$errors): void {
		$url = preg_replace('/\\$[1-9][0-9]*/', 'placeholder', $template);
		if ($url === null) {
			$errors[] = $label . ': field "' . $field . '" must be an absolute http(s) URL';

			return;
		}

		if (str_starts_with($url, '//')) {
			$url = 'https:' . $url;
		}

		$parts = parse_url($url);
		$scheme = is_array($parts) ? ($parts['scheme'] ?? null) : null;
		$host = is_array($parts) ? ($parts['host'] ?? null) : null;

		if (($scheme === 'http' || $scheme === 'https') && is_string($host) && $host !== '') {
			return;
		}

		$errors[] = $label . ': field "' . $field . '" must be an absolute http(s) URL';
	}

	/**
	 * @param string $pattern
	 * @param string $label
	 * @param string $field
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateRegex(string $pattern, string $label, string $field, array &$errors): void {
		set_error_handler(static function (): bool {
			return true;
		});
		$result = preg_match('~' . $pattern . '~imu', '');
		restore_error_handler();

		if ($result === false) {
			$errors[] = $label . ': invalid regex in "' . $field . '"';
		}
	}

	/**
	 * @param array<string, mixed> $provider
	 * @param string $label
	 * @param array<string, string> $slugs
	 * @param array<string> $errors
	 * @return void
	 */
	private function validateSlug(array $provider, string $label, array &$slugs, array &$errors): void {
		$name = $provider['name'] ?? null;
		$slug = $provider['slug'] ?? null;
		if (!is_string($slug) || $slug === '') {
			if (!is_string($name) || $name === '') {
				return;
			}
			$slug = $this->slugger->slug($name);
		}

		if (isset($slugs[$slug])) {
			$errors[] = $label . ': duplicate slug "' . $slug . '" already used by ' . $slugs[$slug];

			return;
		}

		$slugs[$slug] = $label;
	}

}
