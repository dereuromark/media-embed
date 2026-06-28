<?php

declare(strict_types=1);

namespace MediaEmbed\Provider;

use URLify;

/**
 * Validates provider configuration arrays.
 */
final class ProviderValidator {

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
			$this->validateFetchMatch($provider, $label, $errors);
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
			if (!array_key_exists($field, $provider) || $provider[$field] === '' || $provider[$field] === []) {
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
			$slug = URLify::filter($name);
		}

		if (isset($slugs[$slug])) {
			$errors[] = $label . ': duplicate slug "' . $slug . '" already used by ' . $slugs[$slug];

			return;
		}

		$slugs[$slug] = $label;
	}

}
