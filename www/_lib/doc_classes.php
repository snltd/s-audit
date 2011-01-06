<?php

//============================================================================
//
// doc_classes.php
// ---------------
//
// Classes needed for s-audit documentation. The main docPage() class is in
// display_classes.php.
//
// R Fisher 01/11
//
//============================================================================

class codeBlock {

	// This class prints embedded code blocks

	private $scr;
	private $scr_src;
	private $scr_html;
	private $scr_link;

	public function __construct($scr)
	{
		$this->scr = $scr;
		$this->scr_src = DOC_SCR_DIR . "/$scr";
		$this->scr_html = $this->scr_src . ".html";
		$this->scr_link = "/docs/_files/$scr";
	}

	public function show_script()
	{
		// Show the Vim syntax coloured script if we can. If not, fall back
		// to using the script

		$ret = "\n\n<div class=\"codeBlock\">\n"
		. "  <div class=\"codeBlockHead\">Source of $this->scr</div>"
		. "  <div class=\"codeBlockLink\">(<a href=\"$this->scr_link\">"
		. "view as plain text/download</a>)</div>"
		. "\n<pre class=\"codeBlock\">";

		if (file_exists($this->scr_html))
			$ret .= file_get_contents($this->scr_html);
		elseif (file_exists($this->scr_src))
			$ret .= file_get_contents($this->scr_src);
		else
			$ret .= "Someone forgot the code!";

		return $ret . "\n  </pre>\n  </div>";
	}


}

?>
