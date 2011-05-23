<?php

//============================================================================
//
// key_os.php
// ----------
//
// Key for O/S audits
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	"version" => array(
		array("different version<br/>in local zone", "boxamber", false)
	),

	"release" => array(
		array("different release<br/>in local zone", "boxamber", false)
	),

	"kernel" => array(
		array("latest installed kernel", "ver_l", false),
		array("older kernel", "ver_o", false),
		array("different kernel<br/>in local zone", "boxamber", false)
	),

	"hostid" => array(
		array("different hostid<br/>in local zone", "boxamber", false)
	),

	"local zone" => array(
		array("running zone", "solidgreen", false),
		array("installed zone", "solidamber", false),
		array("zone in other state", "solidred", false),
		array("resource caps", "boxblack", false),
		array("non-native zone", "boxamber", false)
	),

	"LDOM" => array(
		array("active domain", "solidgreen", false),
		array("bound domain", "solidamber", false),
		array("domain in other state", "solidred", false)
	),

	"uptime" => array(
		array("rebooted today", "solidamber", false),
		array("local zone rebooted<br/>after global zone", "boxamber", false)
	),

	"boot env" => array(
		array("active now", "boxgreen", false),
		array("active on reboot", "solidamber", false)
	),

	"SMF services" => array(
		array("services in maintenence mode", "solidred", false)
	),

	"packages" => array(
		array("partially installed<br/>packages", "solidamber", false)
	),

	"publisher" => array(
		array("preferred publisher", "boxgreen", false)
	),

	"patches" => array(
		array("in local zone, possible<br/>missing patches", "solidamber",
		false)
	)

);

?>
