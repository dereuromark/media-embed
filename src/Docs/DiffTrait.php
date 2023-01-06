<?php

namespace MediaEmbed\Docs;

use SebastianBergmann\Diff\Differ;

/**
 * @internal Only for internal docs generation.
 */
trait DiffTrait {

	/**
	 * @param string $before
	 * @param string $after
	 *
	 * @return string|null
	 */
	protected function getDiff(string $before, string $after): ?string {
		$beforeArray = $this->toSimpleArray($before);
		$afterArray = $this->toSimpleArray($after);

		$differ = new Differ(null);
		$array = $differ->diffToArray($beforeArray, $afterArray);

		$diff = $this->generateDiff($array);
		if (!$diff) {
			return null;
		}

		return $diff;
	}

	/**
	 * @param string $content
	 *
	 * @return array<string>
	 */
	protected function toSimpleArray(string $content): array {
		return explode(PHP_EOL, $content);
	}

	/**
	 * @param array<mixed> $array
	 *
	 * @return string
	 */
	protected function generateDiff(array $array): string {
		$out = [];

		$begin = null;
		$end = 0;
		foreach ($array as $key => $row) {
			if ($row[1] === 0) {
				continue;
			}

			if ($begin === null) {
				$begin = (int)$key;
			}
			$end = $key;
		}
		if ($begin === null) {
			return '';
		}

		$firstLineOfOutput = $begin > 0 ? $begin - 1 : 0;
		$lastLineOfOutput = count($array) - 1 > $end ? $end + 1 : $end;

		for ($i = $firstLineOfOutput; $i <= $lastLineOfOutput; $i++) {
			$row = $array[$i];

			$output = trim($row[0], "\n\r\0\x0B");

			if ($row[1] === 1) {
				$char = '+';
			} elseif ($row[1] === 2) {
				$char = '-';
			} else {
				continue;
			}
			$out[] = $char . $output;
		}

		return implode(PHP_EOL, $out);
	}

}
