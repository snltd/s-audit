<?php

//============================================================================
//
// key_application.php
// -------------------
//
// Key for application audits
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	// Just one "general" key type that stretches right across the table

	"general" => array(
		array("most recent installed version", "ver_l", false),
		array("older installed version", "ver_o", false),
		array("version unobtainable", "solidorange", false),
		array("not running (but expected to be)", "boxred", false)
	)

);

?>
