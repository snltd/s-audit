<?php

//============================================================================
//
// colours.php
// -----------
//
// Classes to help colour cells in audit grids and their corresponding keys
// in documentation. Required by all audit class pages and dynamic_css.php.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

// Get the nic_colours[] array. We have to use a _SERVER path because we
// might be calling this from dynamic_css.php, which doesn't include the
// site config file

require_once(ROOT . "/_conf/nic_colours.php");

//----------------------------------------------------------------------------
// colours

class colours {

	// A little class which defines colours used for various purposes, and
	// provides a method to access their hex values.

	static $nic_cols;
		// This array stores colours for various networks. It's set by the
		// in the nic_colours.php file

	// These are just named colours we use all over.

	static $cols = array(
		"green" => "#89bf84",
		"red" => "#ca6a5b",
		"amber" => "#e9c243",
		"blue" => "#2a1ad6",
		"pink" => "#a41cb0",
		"grey" => "#bbb", 
		"black" => "#000", 
		"orange" => "#df8439"
	);
	
	// $fs_cols is a list of colours used in the filesystem audit page.
	// Keep "smb" and "smbsf" the same

	static $fs_cols = array(
		"ufs" => "#bfcde0",
		"zfs" => "#c7a49c",
		"nfs" => "#dfdc9b",
		"hsfs" => "#80d4a0",
		"lofs" => "#e6e9c9",
		"vboxfs" => "#b86bae",
		"smb" => "#7b7a94",		
		"smbfs" => "#7b7a94",
		"vdisk" => "#6FBB68",
		"iscsi" => "#a0b141"
	);

	// $ws_cols is a list of colours used to identify different webserver
	// types

	static $ws_cols = array(
		"apache" => "#bfcde0",
		"iPlanet" => "#c7a49c"
	);
	
	// $db_cols is a list of colours used to identify different databases

	static $db_cols = array(
		"mysql" => "#bfcde0"
	);

	// $stor_cols is a list of colours used to identify different storage
	// devices
	
	static $stor_cols = array(
		"disk" => "#bfcde0",	// disks
		"cd" => "#ccc",			// optical drives
		"fc" => "#7b7a94", 		// fibre arrays
		"tp" => "#c7a49c"		// tape drives
	);

	// $plat_cols is a list of colours used to identify different hardware
	// types
	
	static $plat_cols = array(
		"x86" => "#7b7a94",
		"sparc" => "#c7a49c"
	);

	public function get_col($colour)
	{
		// A function used to get the values above.

		if (in_array($colour, array_keys(colours::$cols)))
			$colour = colours::$cols[$colour];

		return $colour;
	}

}

//----------------------------------------------------------------------------
// inlineCol

class inlineCol {

	// You can call the following functions with a shorthand name from the
	// variable list above, or with a hex value. The get_col() function
	// decides which one to use

	static function box($colour)
	{
		return "border: 2px solid " . colours::get_col($colour);
	}

	static function solid($colour)
	{
		return "background: ". colours::get_col($colour);
	}

}

?>

