<?php

//============================================================================
//
// key_server.php
// ---------------
//
// Key data for the single server view page
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

if (defined("SINGLE_VIEW")) {
	;
}
else {
	$generic_key = array(

		"col_1" => array(
			array("physical server", "server", false),
			array("logical domain", "ldmp", false),
			array("VirtualBox", "vb", false)
		),
	
		"others" => array(
			array("local zone", "zone", false)
		)
	);
}


?>
