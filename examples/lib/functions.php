<?php

declare(strict_types=1);

/**
 * @return array<string, array{0: string, 1: array<string, mixed>, 2: array<string, mixed>}>
 */
function getVideos(string $file): array {
	$videos = [];
	$handle = fopen($file, 'r');
	if ($handle === false) {
		return $videos;
	}

	$row = 0;
	while (($data = fgetcsv($handle)) !== false) {
		$row++;
		if ($row === 1 || empty($data[0]) || empty($data[1])) {
			continue;
		}

		$videos[$data[0]] = [
			$data[1],
			decodeJsonColumn($data[2] ?? null),
			decodeJsonColumn($data[3] ?? null),
		];
	}
	fclose($handle);

	return $videos;
}

/**
 * @return array<string, mixed>
 */
function decodeJsonColumn(?string $value): array {
	if ($value === null || $value === '') {
		return [];
	}

	$decoded = json_decode($value, true);
	if (!is_array($decoded)) {
		return [];
	}

	return $decoded;
}

function h(string $value): string {
	return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
