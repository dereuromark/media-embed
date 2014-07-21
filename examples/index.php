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
</style>

<h1>Video Examples</h1>
<p>The examples use the iframe if possible, and fallback to the embed object if necessary.</p>

<table><tr><td class="types">
<h2>Select Type</h2>
<ul>
<?php
$MediaEmbed = new \MediaEmbed\MediaEmbed();

foreach ($videos as $name => $url) {
?>
	<li><a href="index.php?type=<?php echo $name; ?>"><?php echo $name; ?></a></li>
<?php
}
?>
</ul>
</td><td>
<?php
	if (!empty($_GET['type']) && isset($videos[$_GET['type']])) {
		$videoUrl = $videos[$_GET['type']];

		echo '<h2>"' . $_GET['type'] . '"</h2>';
		echo $videoUrl;
		echo '<br /><br />';

		$result = $MediaEmbed->parseUrl($videoUrl);
		if (!$result) {
			throw new Exception('An error occured with this type');
		}
		$embed = $result->getEmbedCode();
  	// or
		//$embed = (string)$result;
		echo $embed;
	}
?>
</td></tr></table>

<p>
If there are outdated (not working) example URLs or missing types, let me know or provide a PR in <a href="https://github.com/dereuromark/MediaEmbed" target="_blank">GitHub</a> to fix this.
</p>