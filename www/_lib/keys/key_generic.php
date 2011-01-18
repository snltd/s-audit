<?php

//============================================================================
//
// key_generic.php
// ---------------
//
// Generic key data included on every audit type
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$generic_key = array(

	"hostname" => array(
		array("physical server", "server", false),
		array("local zone", "zone", false),
		array("VirtualBox", "vb", false),
		array("primary LDOM", "ldmp", false),
		array("guest LDOM", "ldm", false)
	),

	"audit completed" => array(
		array("data &gt; 24h old", "solidamber", false),
		array("data &gt; 1 week old", "solidred", false)
	)

);

?>
