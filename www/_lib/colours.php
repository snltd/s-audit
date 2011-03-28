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

//----------------------------------------------------------------------------
// colours

class colours {

	// A little class which defines colours used for various purposes, and
	// provides a method to access their hex values.

	protected $nic_cols;
		// This array stores colours for various networks. It's set by the
		// in the nic_colours.php file

	// These are just named colours we use all over.

	protected $cols = array(
		"green" => "#89bf84",
		"red" => "#ca6a5b",
		"amber" => "#E9D02F",
		"blue" => "#2a1ad6",
		"pink" => "#E197C5",
		"grey" => "#bbb", 
		"black" => "#000", 
		"orange" => "#df8439"
	);
	
	// $fs_cols is a list of colours used in the filesystem audit page.
	// Keep "smb" and "smbsf" the same

	protected $fs_cols = array(
		"ufs" => "#230997",
		"zfs" => "#B60A4F",
		"lofs" => "#737373",
		"hsfs" => "#275733",
		"nfs" => "#000",
		"vboxfs" => "#9A5F0B",
		"smb" => "#571C56",
		"smbfs" => "#571C56",
		"vdisk" => "#167318",
		"iscsi" => "#DF1711",
	);

	// $ws_cols is a list of colours used to identify different webserver
	// types

	protected $ws_cols = array(
		"apache" => "#230997",
		"iPlanet" => "#B60A4F"
	);
	
	// $db_cols is a list of colours used to identify different databases

	protected $db_cols = array(
		"mysql" => "#230997"
	);

	// $stor_cols is a list of colours used to identify different storage
	// devices
	
	protected $stor_cols = array(
		"disk" => "#230997",	// disk drives
		"cd" => "#ccc",			// optical drives
		"fc" => "#7b7a94", 		// fibre arrays
		"tp" => "#c7a49c"		// tape drives
	);

	// $plat_cols is a list of colours used to identify different hardware
	// types
	
	protected $plat_cols = array(
		"x86" => "#7b7a94",
		"sparc" => "#c7a49c"
	);

	protected $card_cols = array(
		"pci" => "#7b7a94",
		"sbus" => "#c7a49c"
	);

	public function __construct()
	{
		// Get the nic_colours[] array. We have to use a _SERVER path
		// because we might be calling this from dynamic_css.php, which
		// doesn't include the site config file

		require_once(ROOT . "/_conf/nic_colours.php");

		if (isset($nic_cols)) {
			$this->nic_cols = $nic_cols;
			unset($nic_cols);
		}
	}

	public function get_col($col, $class)
	{
		// A function used to get the values above.

		if ($class) {
			$cl = $this->$class;

			$col = (isset($cl[$col]))
				? $cl[$col]
				: false;
		}
		else
			$col = $this->cols[$col];

		return $col;
	}

	public function get_col_list($cl)
	{
		return $this->$cl;
	}

	// You can call the following functions with a shorthand name from the
	// variable list above, or with a hex value. The get_col() function
	// decides which one to use

	public function icol($type, $col, $class = false, $html = false)
	{
		if (!$chex = $this->get_col($col, $class))
			return false;

		$ret = ($html)
			? "style=\"" 
			: "";
			
		$ret .= ($type == "box")
			?  "border: 2px solid $col"
			: "background: $col";

		if ($html)
			$ret .= "\"";

		return $ret;
	}

}

?>

