<?php

//============================================================================
//
// key_ip_listing.php
// -------------------
//
// Key for IP listing page audits
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	// Just one "general" key type that stretches right across the table

	"general" => array(
		array("live addresses in DNS", "resolved", false),
		array("addresses taken from audit files", "onlylive", false),
		array("pingable addresses not in DNS or audit files", "onlyping",
		false),
		array("reserved IP addresses", "reserved", false),
		array("pingable on last subnet audit", "boxgreen", false),
		array("not pingable on last subnet audit", "boxred", false)
	)

);

?>
