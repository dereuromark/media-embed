<?php

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
	public function id();

	/**
	 * Returns the host as slugged string.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function slug();

	/**
	 * Returns the name of this media host type.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function name();

	/**
	 * Returns the final HTML code for display.
	 *
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedCode();

	/**
	 * Returns the embed src. Useful for iframes where you only need the src attribute
	 *
	 * @api
	 *
	 * @return string
	 */
	public function getEmbedSrc();

}
