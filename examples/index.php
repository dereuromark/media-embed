<?php

declare(strict_types=1);

use MediaEmbed\MediaEmbed;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/lib/functions.php';

$videos = getVideos(__DIR__ . '/data/videos.csv');
$mediaEmbed = new MediaEmbed();
$hosts = $mediaEmbed->getHosts();
ksort($hosts);
$selectedType = is_string($_GET['type'] ?? null) ? $_GET['type'] : null;
?>
<style>
table td {
	vertical-align: top;
}
td.types {
	width: 300px;
}
textarea {
	width: 100%;
	height: 100px;
}
ul.no-examples li {
	font-size: 10px;
}
</style>

<h1>Video Examples</h1>
<p>The examples use iframe embeds.</p>

<table><tr><td class="types">
<h2>Select Type</h2>
<ul>
<?php foreach ($videos as $name => $parts): ?>
	<li><a href="index.php?type=<?php echo h($name); ?>"><?php echo h($name); ?></a></li>
<?php endforeach; ?>
</ul>

<ul class="no-examples">
<?php foreach ($hosts as $host): ?>
	<?php if ($host['name'] === '$2' || array_key_exists($host['name'], $videos)): ?>
		<?php continue; ?>
	<?php endif; ?>
	<li><?php echo h((string)$host['name']); ?></li>
<?php endforeach; ?>
</ul>

Currently supported services: <?php echo count($hosts); ?><br />
Examples available for <?php echo count($videos); ?> services.
</td><td>
<?php if ($selectedType !== null && isset($videos[$selectedType])): ?>
	<?php
		$videoUrl = $videos[$selectedType][0];
		$videoAttributes = $videos[$selectedType][1];
		$videoParams = $videos[$selectedType][2];
		$mediaObject = $mediaEmbed->parseUrl($videoUrl);
		if ($mediaObject === null) {
			throw new RuntimeException('An error occurred with this type.');
		}
	?>

	<h2><?php echo h($selectedType); ?></h2>
	<p>Video URL: <?php echo h($videoUrl); ?></p>
	<?php if ($videoAttributes): ?>
		<p>Video Attributes: <pre><?php echo h(print_r($videoAttributes, true)); ?></pre></p>
	<?php endif; ?>
	<?php if ($videoParams): ?>
		<p>Video Params: <pre><?php echo h(print_r($videoParams, true)); ?></pre></p>
	<?php endif; ?>

	<table><tr><td>
	<h3>Parsing Result</h3>
	Video ID: <?php echo h($mediaObject->id()); ?>

	<h3>Embedded Media</h3>
	<?php
		if ($videoAttributes) {
			$mediaObject->setAttribute($videoAttributes);
		}
		if ($videoParams) {
			$mediaObject->setParam($videoParams);
		}
		$embed = $mediaObject->getEmbedCode();
		echo $embed;
	?>

	<div><h3>Embed code:</h3><textarea><?php echo h($embed); ?></textarea></div>
	</td><td>
	<?php
		$id = $mediaObject->id();
		$slug = $mediaObject->slug();
		$reverseLookupObject = $mediaEmbed->parseId($id, $slug);
		$reverseEmbed = null;
		if ($reverseLookupObject !== null && !str_contains($reverseLookupObject->getEmbedSrc(), '$')) {
			$reverseEmbed = $reverseLookupObject->getEmbedCode();
		}
	?>

	<h3>Reverse lookup by video id and host slug</h3>
	Result: <?php echo $reverseEmbed !== null ? 'OK' : 'Not available for this provider'; ?>

	<?php if ($reverseEmbed !== null): ?>
		<h3>Embedded Media</h3>
		<?php echo $reverseEmbed; ?>
		<div><h3>Embed code:</h3><textarea><?php echo h($reverseEmbed); ?></textarea></div>
	<?php endif; ?>
	</td></tr></table>
<?php endif; ?>
</td></tr></table>

<p>
If there are outdated example URLs or missing types, let us know or provide a PR in <a href="https://github.com/dereuromark/media-embed" target="_blank" rel="noopener">GitHub</a>.
</p>
