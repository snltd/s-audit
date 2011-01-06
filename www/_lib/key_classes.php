<?php

//============================================================================
//
// R Fisher
//
// Please record changes below.
//
// v1.0  initial release
//
//============================================================================

class auditKey
{
	// A class which helps us to print nice looking, consistent keys at the
	// bottom of our audit pages.

	public function open_key($heading = "Key")
	{
		return "\n\n<center>\n<table class=\"key\">"
		. "\n<tr><td class=\"keyhead\" colspan=\"2\">$heading</td></tr>";
	}

	public function key_row($class, $style, $str)
	{
		// Return 

		return "\n<tr>" . new Cell(false, $class, $style, "50") . new
		Cell($str, "key_txt") . "</tr>";
	}

	public function close_key()
	{
		return "<tr>" . new Cell("Cells may have combinations of different
		coloured backgrounds and borders. Pay close attention!", "keynote",
		false, false, 2). "</tr>" 
		."\n</table>\n</center>";
	}

	public function key_extra_info($heading, $text)
	{
		return $this->open_key($heading) . "<tr><td class=\"key_info\"><p>"
		.  preg_replace("/<br>/", "</p><p>", $text) .
		"</p></td></tr>\n</table>\n</center>";
	}

	public function key_global()
	{
		return $this->key_row("server", false, "Global zones/physical
		servers") 
		. $this->key_row("zone", false, "local zones")
		. $this->key_row("vb", false, "VirtualBox (and global zone)")
		. $this->key_row("ldmp", false, "primary LDOM (and global zone)")
		. $this->key_row("ldm", false, "LDOM (and global zone)");
	}

	public function key_time()
	{
		return $this->key_row("solidamber", false, "In <strong>audit
		completed</strong>, shows audit is more than 12 hours old") .
		$this->key_row("solidred", false, "In <strong>audit
		completed</strong>, shows audit is more than 24 hours
		old");
	}
}

?>
