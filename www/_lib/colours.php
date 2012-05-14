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

	public $nic_cols;
		// This array stores colours for various networks. It's set by the
		// in the nic_colours.php file. It's accessed statically by the
		// network audit page key, so it has to be public

	// These are just named colours we use all over.

	protected $cols = array(
		"green" => "#89bf84",
		"red" => "#ca6a5b",
		"amber" => "#C7C761",
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
		"vxfs" => "#D68251",
		"lofs" => "#737373",
		"hsfs" => "#275733",
		"nfs" => "#000",
		"vboxfs" => "#9A5F0B",
		"smb" => "#571C56",
		"smbfs" => "#571C56",
		"vdisk" => "#167318",
		"xvm" => "#437061",
		"iscsi" => "#DF1711",
	);

	// $vm_cols is a list of colours used in the hostname column. 

	protected $vm_cols = array(
		"lzone" => "#8B9ADF",	// local zone
		"bzone" => "#df8439",	// branded zone
		"domu" => "#703D3E",	// XEN domU
		"dom0" => "#985355",	// XEN dom0
		"vbox" => "#2B2E70",	// VirtualBox
		"vmware" => "#437061",	// VMWare instance
		"ldmp" => "#2A480E",	// primary logical domain
		"ldm" => "#21701A",		// guest logical domain
		"unk" => "#DC8A82"		// unknown virtualization
	);

	// Colours used in the hostname column. First machines, mostly virtual.
	// These match up with the $vm_cols array

	protected $m_cols = array(
		"vbox" => "#2B2E70",	// VirtualBox
		"vmware" => "#437061",	// VMWare instance
		"ldmp" => "#2A480E",	// primary logical domain
		"ldm" => "#21701A",		// guest logical domain
		"dom0" => "#985355",	// XEN dom0
		"domu" => "#703D3E",	// XEN domU
		"unk" => "#DC8A82"		// unknown virtualization
	);

	// now zones
	
	protected $z_cols = array(
		"szone" => "#439069",	// sparse root zone
		"bzone" => "#df8439"	// branded zone
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
		"tp" => "#c7a49c",		// tape drives
		"iscsi" => "#DF1711",	// iSCSI volume
		"rvol" => "#167318"		// h/w RAID volume
	);

	// $plat_cols is a list of colours used to identify different hardware
	// types
	
	protected $plat_cols = array(
		"xen" => "#7b7a94",
		"sparc" => "#c7a49c"
	);

	protected $card_cols = array(
		"pci" => "#7b7a94",
		"pcie" => "#3b7a34",
		"pcix" => "#9b4a94",
		"sbus" => "#c7a49c"
	);

	protected $eeprom_cols = array(
		"parm" => "#7b7a94",
		"deva" => "#c7a49c"
	);

	protected $net_cols = array(
		"phys" => "#B60A4F",
		"virtual" => "#888",
		"vnic" => "#230997",
		"etherstub" => "#9A5F0B",
		"vswitch" => "#571C56",
		"aggr" => "#DC8A82",
		"clprivnet" => "#2A480E",
		"LLT" => "#2B2E70"
	);

	public function __construct()
	{
		// Get the subnet_cols[] array. We have to use a _SERVER path
		// because we might be calling this from dynamic_css.php, which
		// doesn't include the site config file

		if (file_exists(ROOT . "/_conf/subnet_colours.php")) {
			require_once(ROOT . "/_conf/subnet_colours.php");

			if (isset($subnet_cols)) {
				$this->subnet_cols = $subnet_cols;
				unset($subnet_cols);
			}

		}

	}

	public function get_col($col, $class)
	{
		// A function used to get the values above.

		if ($class) {
			$cl = $this->$class;

			$ret = (isset($cl[$col]))
				? $cl[$col]
				: false;
		}
		else
			$ret = $this->cols[$col];

		return $ret;
	}

	public function get_col_list($cl)
	{
		// Return a whole colour array

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
			?  "border: 2px solid $chex"
			: "background: $chex";

		if ($html)
			$ret .= "\"";

		return $ret;
	}

}

?>

