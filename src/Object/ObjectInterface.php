<?php

declare(strict_types=1);

namespace MediaEmbed\Object;

/**
 * ObjectInterface must be implemented by classes that are a specific type of media.
 */
interface ObjectInterface {

	/**
	 * Returns the unique id of a media resource.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function id(): string;

	/**
	 * Returns the host as slugged string.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function slug(): string;

	/**
	 * Returns the name of this media host type.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function name(): string;

	/**
	 * Returns the final HTML code for display.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedCode(): string;

	/**
	 * Returns the raw embed src URL. Useful when the caller controls escaping.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedSrc(): string;

	/**
	 * Returns the HTML-escaped embed src URL for iframe src attributes.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedSrcForHtml(): string;

}
