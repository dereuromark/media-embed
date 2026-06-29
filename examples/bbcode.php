<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/lib/functions.php';
require __DIR__ . '/lib/MediaEmbedBehavior.php';

$video = 'https://www.youtube.com/watch?v=yiSjHJnc9CY';
$string = 'Cool video: [video]' . $video . '[/video] Like it - now!';
$behavior = new MediaEmbedBehavior();
$input = $behavior->simulateSave($string);
$output = $behavior->prepareForOutput($input);
?>
<style>
table td {
	vertical-align: top;
}
td.types {
	width: 300px;
}
</style>

<h1>BBCode Video Examples</h1>
<p>You can use Markdown or BBCode snippets to transform text into an embedded media snippet.</p>

<table><tr><td class="types">
<h2>BBCode</h2>
<code><pre><?php echo h($string); ?></pre></code>

That is the user input from a textarea, for example.

<h2>Upon save it will be processed</h2>
<code><pre><?php echo h($input); ?></pre></code>

<p>You may also validate it and act accordingly.</p>

<h2>Upon display we transform it again</h2>
<code><pre><?php echo h($output); ?></pre></code>

<h2>Rendered output</h2>
<?php echo $output; ?>
</td></tr></table>

<p>
If there are outdated example URLs or missing types, let us know or provide a PR in <a href="https://github.com/dereuromark/media-embed" target="_blank" rel="noopener">GitHub</a>.
</p>
