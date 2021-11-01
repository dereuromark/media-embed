<?php

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

	/**
	 * @var bool
	 */
	protected $dryRun = false;

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
	public function generate() {
		$content = $this->build();
		$path = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'supported.md';

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
	protected function build() {
		$services = [];
		$hosts = (new MediaEmbed())->getHosts();
		ksort($hosts);

		foreach ($hosts as $host) {
			$services[] = ' - ' . $this->name($host);
		}

		$counter = count($services) . ' services';

		$serviceList = implode(PHP_EOL, $services);

		$content = <<<TEXT
# Supported Media Services

$counter

$serviceList

TEXT;

		return $content;
	}

	/**
	 * @param array $array
	 *
	 * @return string
	 */
	protected function name($array) {
		if (!empty($array['website']) && preg_match('#^http#', $array['website'])) {
			return '[' . $array['name'] . '](' . $array['website'] . ')';
		}

		return $array['name'];
	}

}
