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
		array("addresses not known to be in use", "empty", false),
		array("audited servers", false, "font-weight: bold"),
		array("addresses taken from audit files", "onlylive", false),
	)

);

// Only add the following if they're relevant

if (file_exists(IP_LIST_FILE)) {
	$grid_key["general"][] = array("live addresses in DNS", "resolved",
	false);
	$grid_key["general"][] = array("pingable addresses not in DNS or audit
	files", "onlyping", false);
	$grid_key["general"][] = array("pingable on last subnet audit",
	"boxgreen", false);
	$grid_key["general"][] = array("not pingable on last
	subnet audit", "boxred", false);
}

if (file_exists(IP_RES_FILE))
	$grid_key["general"][] = array("reserved IP addresses", "reserved",
	false); 

?>
