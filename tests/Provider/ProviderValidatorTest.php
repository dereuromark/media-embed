<?php

namespace MediaEmbed\Test\Provider;

use MediaEmbed\Provider\ProviderValidator;
use PHPUnit\Framework\TestCase;

class ProviderValidatorTest extends TestCase {

	public function testValidateDefaultProviders(): void {
		$providers = include dirname(__DIR__, 2) . '/data/stubs.php';

		$validator = new ProviderValidator();

		$this->assertSame([], $validator->validate($providers));
	}

	public function testValidateReportsInvalidProviderData(): void {
		$providers = [
			[
				'name' => 'Broken',
				'website' => 'https://broken.example.com',
				'url-match' => ['broken\\.example\\.com/([0-9]+', ''],
				'embed-width' => [],
				'embed-height' => '360',
				'iframe-player' => 'javascript:alert($2)',
				'image-src' => '//broken.example.com/thumb/$0.jpg',
				'fetch-match' => '(',
				'status' => 'unknown',
				'category' => 'unknown',
				'example-url' => 'javascript:alert(1)',
				'notes' => '',
			],
			[
				'name' => 'Broken',
				'website' => '/relative',
				'url-match' => 'other\\.example\\.com/([0-9]+)',
				'embed-width' => 640,
				'embed-height' => 360,
				'iframe-player' => '//other.example.com/embed/$2',
			],
		];

		$validator = new ProviderValidator();
		$errors = $validator->validate($providers);

		$this->assertContains('Broken: field "embed-width" must be an integer or string', $errors);
		$this->assertContains('Broken: invalid regex in "url-match"', $errors);
		$this->assertContains('Broken: url-match entries must be non-empty strings', $errors);
		$this->assertContains('Broken: field "iframe-player" must be an absolute http(s) URL', $errors);
		$this->assertContains('Broken: field "image-src" contains an invalid placeholder', $errors);
		$this->assertContains('Broken: field "website" must be an absolute http(s) URL', $errors);
		$this->assertContains('Broken: invalid regex in "fetch-match"', $errors);
		$this->assertContains('Broken: field "status" must be one of: active, legacy, deprecated', $errors);
		$this->assertContains('Broken: field "category" must be one of: 3d, audio, social, streaming, video', $errors);
		$this->assertContains('Broken: field "example-url" must be an absolute http(s) URL', $errors);
		$this->assertContains('Broken: field "notes" must be a non-empty string', $errors);
		$this->assertContains('Broken: duplicate slug "broken" already used by Broken', $errors);
	}

	public function testValidateReportsNonStringTemplateFields(): void {
		$providers = [
			[
				'name' => 'Broken',
				'website' => ['https://broken.example.com'],
				'url-match' => 'broken\\.example\\.com/([0-9]+)',
				'embed-width' => 640,
				'embed-height' => 360,
				'iframe-player' => '//broken.example.com/embed/$2',
			],
		];

		$validator = new ProviderValidator();
		$errors = $validator->validate($providers);

		$this->assertContains('Broken: field "website" must be a non-empty URL string', $errors);
	}

	public function testValidateReportsNullRequiredField(): void {
		$providers = [
			[
				'name' => 'Broken',
				'website' => 'https://broken.example.com',
				'url-match' => 'broken\\.example\\.com/([0-9]+)',
				'embed-width' => 640,
				'embed-height' => 360,
				'iframe-player' => null,
			],
		];

		$validator = new ProviderValidator();
		$errors = $validator->validate($providers);

		$this->assertContains('Broken: missing required field "iframe-player"', $errors);
	}

}
