<?php
include dirname(dirname(__FILE__)) . '/vendor/autoload.php';
include dirname(__FILE__) . '/lib/functions.php';
include dirname(__FILE__) . '/lib/MediaEmbedBehavior.php';

$video = 'http://www.youtube.com/watch?v=yiSjHJnc9CY';
$string = 'Cool video: [video]' . $video . '[/video] Like it - now!';
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
<p>You can use Markdown or BBCode snippets to transform your text into a embed media snippet.</p>

<table><tr><td class="types">
<h2>BBCode</h2>
<code><pre><?php echo $string;?></pre></code>

That is the user input from a textarea, for example.

<h2>Upon save it will be processed</h2>
<?php
	$Behavior = new \MediaEmbedBehavior();
	$input = $Behavior->simulateSave($string);
?>
<code><pre><?php echo $input; ?></pre></code>

<p>You may also validate it and act accordingly (prevent save or remove the video and alert the user).</p>

<h2>Upon display we transform it again</h2>
The final HTML output:
<?php
	$output = $Behavior->prepareForOutput($input);
?>
<code><pre><?php echo $output; ?></pre></code>

</td></tr></table>

<p>That's it! :)</p>

<p>
If there are outdated (not working) example URLs or missing types, let me know or provide a PR in <a href="https://github.com/dereuromark/MediaEmbed" target="_blank">GitHub</a> to fix this.
</p>
