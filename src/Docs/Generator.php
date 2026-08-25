<?php

declare(strict_types=1);

namespace MediaEmbed\Docs;

use MediaEmbed\MediaEmbed;

/**
 * @internal Only for internal docs generation.
 */
class Generator {

	use DiffTrait;

	/**
	 * @var int
	 */
	public const CODE_SUCCESS = 0;

	/**
	 * @var int
	 */
	public const CODE_ERROR = 1;

	protected bool $dryRun = false;

	/**
	 * @param array<string> $args
	 */
	public function __construct(array $args) {
		if (in_array('-d', $args, true)) {
			$this->dryRun = true;
		}
	}

	/**
	 * @return int
	 */
	public function generate(): int {
		$content = $this->build();
		$path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'supported.md';

		if (!$this->dryRun) {
			file_put_contents($path, $content);

			return static::CODE_SUCCESS;
		}

		$currentContent = file_get_contents($path) ?: '';
		$diff = $this->getDiff($currentContent, $content);
		if (!$diff) {
			return static::CODE_SUCCESS;
		}

		echo '--- diff ---' . PHP_EOL;
		echo $diff . PHP_EOL;
		echo '--- diff end ---' . PHP_EOL;

		return static::CODE_ERROR;
	}

	/**
	 * @return string
	 */
	protected function build(): string {
		$rows = [];
		$counts = [];
		$hosts = (new MediaEmbed())->getHosts();
		ksort($hosts);

		foreach ($hosts as $host) {
			$status = (string)($host['status'] ?? 'active');
			$category = (string)($host['category'] ?? 'video');
			$counts[$status] = ($counts[$status] ?? 0) + 1;
			$rows[] = '| ' . $this->name($host) . ' | ' . $status . ' | ' . $category . ' | ' . $this->capabilities($host) . ' | ' . $this->notes($host) . ' |';
		}

		ksort($counts);
		$summary = [];
		foreach ($counts as $status => $count) {
			$summary[] = $count . ' ' . $status;
		}

		$counter = count($rows) . ' services (' . implode(', ', $summary) . ')';

		$serviceList = implode(PHP_EOL, $rows);

		$content = <<<TEXT
# Supported Media Services

$counter

Provider example URLs are covered by the release fixture matrix in `tests/Fixture/provider_urls.php`.

| Service | Status | Category | Capabilities | Notes |
|---------|--------|----------|--------------|-------|
$serviceList

TEXT;

		return $content;
	}

	/**
	 * @param array<string, mixed> $array
	 *
	 * @return string
	 */
	protected function name(array $array): string {
		if (!empty($array['website']) && preg_match('#^http#', $array['website'])) {
			return '[' . $array['name'] . '](' . $array['website'] . ')';
		}

		return $array['name'];
	}

	/**
	 * @param array<string, mixed> $array
	 * @return string
	 */
	protected function capabilities(array $array): string {
		$capabilities = [];
		if (!empty($array['iframe-player'])) {
			$capabilities[] = 'iframe';
		}
		if (!empty($array['oembed'])) {
			$capabilities[] = 'oEmbed';
		}

		if (!empty($array['image-src'])) {
			$capabilities[] = 'thumbnail';
		}
		if (!empty($array['supports-timestamp'])) {
			$capabilities[] = 'timestamp';
		}
		if (!empty($array['fetch-match'])) {
			$capabilities[] = 'fetch';
		}

		return implode(', ', $capabilities);
	}

	/**
	 * @param array<string, mixed> $array
	 * @return string
	 */
	protected function notes(array $array): string {
		if (empty($array['notes']) || !is_string($array['notes'])) {
			return '';
		}

		return str_replace('|', '\\|', $array['notes']);
	}

}
