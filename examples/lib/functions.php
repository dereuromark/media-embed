<?php

function getVideos($file) {
	$videos = [];

	if (($handle = fopen($file, 'r')) !== false) {
		$count = 0;
		while (($data = fgetcsv($handle, 0)) !== false) {
			$count++;
			// Empty rows and header (host, url) should be skipped
			if ($count === 1 || empty($data[0]) || empty($data[1])) {
				continue;
			}
			$count++;
			$videos[$data[0]] = [$data[1], json_decode($data[2], true), json_decode($data[3], true)];
		}
	}
	return $videos;
}
