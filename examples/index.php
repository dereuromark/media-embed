<?php
include dirname(dirname(__FILE__)) . '/vendor/autoload.php';
include dirname(__FILE__) . '/lib/functions.php';

$file = dirname(__FILE__) . '/data/videos.csv';
$videos = getVideos($file);
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
<p>The examples use the iframe if possible, and fallback to the embed object if necessary.</p>

<table><tr><td class="types">
<h2>Select Type</h2>
<ul>
<?php
$MediaEmbed = new \MediaEmbed\MediaEmbed();
$hosts = $MediaEmbed->getHosts();
ksort($hosts);

foreach ($videos as $name => $parts) {
	$url = $parts[0];
	$attributes = $parts[1];
	$params = $parts[2];
?>
	<li><a href="index.php?type=<?php echo $name; ?>"><?php echo $name; ?></a></li>
<?php
}
?>
</ul>

<ul class="no-examples">
<?php
foreach ($hosts as $slug => $host) {
	if ($host['name'] === '$2' || array_key_exists($host['name'], $videos)) {
		continue;
	}
?>
	<li><?php echo $host['name']; ?></li>
<?php
}
?>
</ul>

Currently supported services: <?php echo count($hosts); ?><br />
Examples available for <?php echo count($videos); ?> services.
</td><td>
<?php
	if (!empty($_GET['type']) && isset($videos[$_GET['type']])) {
		$videoUrl = $videos[$_GET['type']][0];
		$videoAttributes = $videos[$_GET['type']][1];
		$videoParams = $videos[$_GET['type']][2];

		echo '<h2>"' . $_GET['type'] . '"</h2>';
		echo '<p>Video URL: ' . $videoUrl . '</p>';
		if($videoAttributes) {
			echo '<p>Video Attributes: <pre>' . print_r($videoAttributes, 1) . '</pre></p>';
		}
		if($videoParams) {
			echo '<p>Video Params: <pre>' . print_r($videoParams, 1) . '</pre></p>';
		}

		$Object = $MediaEmbed->parseUrl($videoUrl);
		if (!$Object) {
			throw new Exception('An error occured with this type');
		}

		echo '<table><tr><td>';

		echo '<h3>Parsing Result</h3>';
		echo 'Video ID: ' . $Object->id();

		echo '<h3>Embedded Media</h3>';
		//adding attributes
		if($videoAttributes) {
			$Object->setAttribute($videoAttributes);
		}
		//adding params
		if($videoParams) {
			$Object->setParam($videoParams);
		}
		$embed = $Object->getEmbedCode();
  	// or
		//$embed = (string)$result;
		echo $embed;

		echo '<div><h3>Embed code:</h3><textarea>'. htmlspecialchars($embed) . '</textarea></div>';


		echo '</td><td>';
		$id = $Object->id();
		$slug = $Object->slug();
		//remove some params here
		$Object->setParam('autoplay', 0);
		$ObjectFromReverseLookup = $MediaEmbed->parseId($id, $slug);
		echo '<h3>Reverse lookup by video id and host slug</h3>';
		echo 'Result: ' . ($ObjectFromReverseLookup ? 'OK' : 'ERROR');

		if ($ObjectFromReverseLookup) {
			echo '<h3>Embedded Media</h3>';
			$embed = $Object->getEmbedCode();

			echo $embed;

			echo '<div><h3>Embed code:</h3><textarea>'. htmlspecialchars($embed) . '</textarea></div>';
		}

		echo '</td></tr></table>';
	}
?>
</td></tr></table>

<p>
If there are outdated (not working) example URLs or missing types, let me know or provide a PR in <a href="https://github.com/dereuromark/MediaEmbed" target="_blank">GitHub</a> to fix this.
</p>