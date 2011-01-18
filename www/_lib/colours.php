<?php

class colours {

	// A little class which defines colours used for various purposes, and
	// provides a method to access their hex values.

	// This array tells us how to colour NICs. The subnets and colours are
	// site-specific, so you will most likely want to change them.

	static $nic_cols = array(
		"unconfigured" => "#a8a8a8",// Unconfigured, but plumbed interfaces
		"vlan" => "#a8a8a8",		// VLANned interfaces
		"vswitch" => "#7a89bb",		// Virtual Switches
		"alom" => "#e9a655",		// ALOM cables
		"192.168.1" => "#c27fac",
		"10.0.2" => "#62A189",
		"10.10.8" => "#967e4a",		// brown
		"10.10.7" => "#61ab52",		// green
		"10.10.4" => "#f1ef5c",		// yellow
		"10.10.6" => "#d5554c");	// red

	// These are just named colours we use all over.

	static $cols = array(
		"green" => "#89BF84",
		"red" => "#ca6a5b",
		"amber" => "#e9c243",
		"blue" => "#2a1ad6",
		"pink" => "#a41cb0",
		"grey" => "#bbb", 
		"black" => "#000", 
		"orange" => "#df8439"
		);
	
	// $fs_cols is a list of colours used in the filesystem audit page

	static $fs_cols = array(
		"ufs" => "#bfcde0",
		"zfs" => "#c7a49c",
		"nfs" => "#dfdc9b",
		"hsfs" => "#80d4a0",
		"lofs" => "#e6e9c9",
		"vboxfs" => "#b86bae",
		"smb" => "#7b7a94",		// keep "smb" and "smbsf" the same
		"smbfs" => "#7b7a94",
		"vdisk" => "#6FBB68",
		"iscsi" => "#a0b141"
		);

	// $ws_cols is a list of colours used to identify different webserver
	// types

	static $ws_cols = array(
		"apache" => "#bfcde0",
		"iPlanet" => "#c7a49c",
		"nginx" => "#dfdc9b"
		);
	
	// $db_cols is a list of colours used to identify different databases

	static $db_cols = array(
		"mysql" => "#BFCDE0",
		"oracle" => "#c7a49c",
		"postgres" => "#dfdc9b"
		);

	// $stor_cols is a list of colours used to identify different storage
	// devices
	
	static $stor_cols = array(
		"disk" => "#bfcde0",	// disks
		"cd" => "#ccc",	// optical drives
		"fc" => "#7b7a94", 		// fibre arrays
		"tp" => "#c7a49c");		// tape drives

	// $plat_cols is a list of colours used to identify different hardware
	// types
	
	static $plat_cols = array(
		"x86" => "#7b7a94",
		"sparc" => "#c7a49c"
	);

	public function get_col($colour)
	{
		if (in_array($colour, array_keys(colours::$cols)))
			$colour = colours::$cols[$colour];

		return $colour;
	}

}

class inline_col {

	// You can call the following functions with a shorthand name from the
	// variable list above, or with a hex value. The get_col() function
	// decides which one to use

	static function box($colour)
	{
		return "padding: 0px; border: 2px solid " . colours::get_col($colour);
	}

	static function solid($colour)
	{
		return "padding: 1px; background: ". colours::get_col($colour);
	}

}

?>
