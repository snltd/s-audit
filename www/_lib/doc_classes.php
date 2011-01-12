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

	private $scr;		// The name of the code to include (like basename)
	private $scr_cp;	// path to the raw code
	private $scr_cl;	// link to raw code
	private $scr_hp;	// path to HTMLized code
	private $scr_hl;	// link to HTMLized code

	public function __construct($scr)
	{
		// Set variables so show_script() can find everything

		$this->scr = $scr;

		$this->scr_cp = CB_DIR . "/$scr";
		$this->scr_cl = CB_URL . "/$scr";
		$this->scr_hp = CB_DIR . "/${scr}.html";
		$this->scr_hl = CB_URL . "/${scr}.html";
	}

	public function show_script()
	{
		// Show the Vim syntax coloured script if we can. If not, fall back
		// to using the script

		$ret = "\n\n<div class=\"codeBlock\">\n"
		. "  <div class=\"codeBlockHead\">Source of $this->scr</div>";

		if (file_exists($this->scr_cp))
			$ret .= "  <div class=\"codeBlockLink\">(<a href=\""
			. "$this->scr_cl\">view as plain text/download</a>)</div>";

		$ret .= "\n<pre class=\"codeBlock\">";

		if (file_exists($this->scr_hp))
			$ret .= file_get_contents($this->scr_hp);
		elseif (file_exists($this->scr_cp))
			$ret .= file_get_contents($this->scr_cl);
		else
			$ret .= "Someone forgot the code!<br/>$this->scr_cp";

		return $ret . "\n  </pre>\n  </div>";
	}


}

?>
