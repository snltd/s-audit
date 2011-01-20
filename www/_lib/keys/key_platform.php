<?php

//============================================================================
//
// platform_key.php
// ----------------
//
// Key for platform audits. Used on the audit page and in the documentation
//
// format of data structure:
//
// $grid_key[] -> field_name[] -> td_class, td_inline_col, text
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

$grid_key = array(

	"hardware" => array(
		array("SPARC hardware", false,
			inlineCol::box(colours::$plat_cols["sparc"])),
		array("32-bit O/S", "solidamber", false)
	),

	"virtualization" => array(
		array("global zone", "boxblue", false),
		array("non-native zone", "solidamber", false),
		array("whole-root zone", "boxred", false)
	),

	"memory" => array(
		array("no swap space", "solidamber", false)
	),
		
	"ALOM IP" => array(
		array("address reported by server", "solidorange", false),
		array("address &quot;guessed&quot; by querying DNS", "boxorange",
		false)
	),
	
	"storage" => array(
		array("disk drive", "smalldisk", false),
		array("optical drive", "smalltp", false),
		array("tape drive", "smallcd", false),
		array("fibre array", "smallfc", false)
	),

);

// Generate the NIC key automatically

foreach(colours::$nic_cols as $net=>$col) {

	if ($net == "alom" || $net == "vlan")
		continue;
	elseif ($net == "unconfigured")
		$net = "unconfigured or VLANned interface";
	elseif ($net == "vswitch")
		$net = "virtual switch";
	elseif (preg_match("/^\d{1,3}.\d{1,3}.\d{1,3}$/", $net))
		$net = "${net}.0 network";

	$grid_key["NIC"][] = array($net, false, inlineCol::solid($col));
}

/*
$grid_notes = array(
	"ALOM IP" => "The absence of ALOM information does not necessarily mean
	that server has no ALOM configuration. It is not possible to query the
	LOMs on T200 platform machines from Solaris. The firmware version
	currently has to remain unknown, but the interface tries to guess
	missing ALOM IP addresses.  A &quot;guessed&quot; IP address in the ALOM
	IP field is denoted by an orange border, and is acquired by doing a DNS
	lookup on hostname-lom. It may not be correct.",

	"NIC" => "Solid colours in the NIC  and ALOM columns identify the colour
	of the cable that is (or should be) plugged into the corresponding port.
	A grey background means the interface was not able to work out what
	colour the cable should be. (It's probably brown.)"

);

*/
?>
