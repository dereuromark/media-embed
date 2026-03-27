<?php

declare(strict_types=1);

namespace MediaEmbed\Exception;

/**
 * Exception thrown when provider configuration is invalid.
 */
class ProviderConfigException extends MediaEmbedException {

	/**
	 * @var array<string, mixed>
	 */
	protected array $config;

	/**
	 * @param string $message Error message.
	 * @param array<string, mixed> $config The invalid configuration.
	 */
	public function __construct(string $message, array $config = []) {
		$this->config = $config;
		parent::__construct($message);
	}

	/**
	 * Get the invalid configuration.
	 *
	 * @return array<string, mixed>
	 */
	public function getConfig(): array {
		return $this->config;
	}

	/**
	 * Create exception for missing required field.
	 *
	 * @param string $field The missing field name.
	 * @param array<string, mixed> $config The configuration array.
	 * @return self
	 */
	public static function missingField(string $field, array $config = []): self {
		return new self(sprintf('Provider configuration is missing required field: %s', $field), $config);
	}

	/**
	 * Create exception for invalid field value.
	 *
	 * @param string $field The field name.
	 * @param mixed $value The invalid value.
	 * @param string $expectedType Expected type description.
	 * @param array<string, mixed> $config The configuration array.
	 * @return self
	 */
	public static function invalidField(string $field, mixed $value, string $expectedType, array $config = []): self {
		return new self(
			sprintf('Provider configuration field "%s" has invalid value. Expected %s.', $field, $expectedType),
			$config,
		);
	}

}
