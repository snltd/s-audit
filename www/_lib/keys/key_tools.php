<?php

//============================================================================
//
// key_tools.php
// -------------
//
// Key for tool audits
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	// Just one "general" key type that stretches right across the table

	"general" => array(
		array("most recent installed version", "sw_latest", false),
		array("older installed version", "sw_old", false),
		array("version unobtainable", "solidorange", false)
	)

);

?>
