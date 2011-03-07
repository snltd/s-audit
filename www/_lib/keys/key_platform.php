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
		array("whole-root zone", "boxred", false),
		array("non-native zone", "solidamber", false)
	),

	"memory" => array(
		array("no swap space", "solidamber", false)
	),

	"serial number" => array(
		array("failed", "solidred", false),
	),

	"OBP" => array(
		array("latest installed version", "ver_l", false),
		array("old version", "ver_o", false),
	),
		
	"ALOM IP" => array(
		array("address reported by server", "solidorange", false),
		array("address &quot;guessed&quot; by querying DNS", "boxorange",
		false)
	),

	"card" => array(
		array("PCI card", "pci", false),
		array("SBUS card", "sbus", false)
	),

	"printer" => array(
		array("default printer", "boxgreen", false),
	),
	
	"storage" => array(
		array("disk drive", "disk", false),
		array("optical drive", "cd", false),
		array("tape drive", "tp", false),
		array("fibre array", "fc", false),
		array("loaded CD/DVD", "cd", inlineCol::solid("amber")),
		array("mounted CD/DVD", "cd", inlineCol::solid("green"))
	),

);

$grid_key["ALOM f/w"] = $grid_key["OBP"];

?>
