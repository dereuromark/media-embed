<?php

declare(strict_types=1);

namespace MediaEmbed\Test;

use MediaEmbed\Http\HttpClientInterface;
use MediaEmbed\MediaEmbed;
use MediaEmbed\Object\MediaObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use URLify;

class ProviderFixtureTest extends TestCase {

	/**
	 * @dataProvider providerFixtures
	 *
	 * @param array<string, string> $fixture
	 * @return void
	 */
	#[DataProvider('providerFixtures')]
	public function testProviderFixture(array $fixture): void {
		$responses = $this->fixtureResponses();
		$httpClient = new class ($responses) implements HttpClientInterface {

			/**
			 * @param array<string, string> $responses
			 */
			public function __construct(
				private readonly array $responses,
			) {
			}

			public function get(string $url, array $options = []): ?string {
				return $this->responses[$url] ?? null;
			}

		};

		$mediaEmbed = new MediaEmbed(httpClient: $httpClient);
		$object = $mediaEmbed->parseUrl($fixture['url']);

		$this->assertInstanceOf(MediaObject::class, $object);
		$this->assertSame($fixture['slug'], $object->slug());
		$this->assertSame($fixture['id'], $object->id());
		$this->assertSame($fixture['embedSrc'], $object->getEmbedSrc());
	}

	public function testEveryBundledProviderHasReleaseFixture(): void {
		$providers = include dirname(__DIR__) . '/data/stubs.php';
		$fixtures = include __DIR__ . '/Fixture/provider_urls.php';

		$fixtureUrls = array_column($fixtures, 'url', 'slug');
		$missing = [];
		foreach ($providers as $provider) {
			$slug = $provider['slug'] ?? URLify::filter($provider['name']);
			if (!isset($fixtureUrls[$slug])) {
				$missing[] = $slug;
			}
		}

		sort($missing);

		$this->assertSame([], $missing);
	}

	public function testEveryBundledProviderExampleUrlMatchesReleaseFixture(): void {
		$providers = include dirname(__DIR__) . '/data/stubs.php';
		$fixtures = include __DIR__ . '/Fixture/provider_urls.php';

		$fixtureUrls = array_column($fixtures, 'url', 'slug');
		$mismatches = [];
		foreach ($providers as $provider) {
			$slug = $provider['slug'] ?? URLify::filter($provider['name']);
			if (($provider['example-url'] ?? null) !== ($fixtureUrls[$slug] ?? null)) {
				$mismatches[] = $slug;
			}
		}

		sort($mismatches);

		$this->assertSame([], $mismatches);
	}

	/**
	 * @return array<string, array{array<string, string>}>
	 */
	public static function providerFixtures(): array {
		$fixtures = include __DIR__ . '/Fixture/provider_urls.php';
		$data = [];

		foreach ($fixtures as $fixture) {
			$data[$fixture['slug']] = [$fixture];
		}

		return $data;
	}

	/**
	 * @return array<string, string>
	 */
	private function fixtureResponses(): array {
		$fixtures = include __DIR__ . '/Fixture/provider_urls.php';
		$responses = [];

		foreach ($fixtures as $fixture) {
			if (isset($fixture['response'])) {
				$responses[$fixture['url']] = $fixture['response'];
			}
		}

		return $responses;
	}

}
