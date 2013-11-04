<?php

//============================================================================
//
// reader_classes.php
// ------------------
//
// These classes take raw audit data and ready it for processing by the
// display classes. They are incomplete base classes, having "missing"
// methods which pull in the data from wherever it resides, be it flat
// files, MySQL or whatever gets added.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

// The correct extension class file is included here

require_once(LIB . "/reader_file_classes.php");

//----------------------------------------------------------------------------
// GENERAL DATA COLLECTION

class ZoneMapBase {

	// This is a very important class. It produces and reports from arrays
	// which define the way zones relate to each other. It also has a map of
	// zones

	private $group;
		// group of servers

	private $t_start_map;
		// microtime at which we begin creating the map

	public $all;
		// How many server_dirs there are - the total amount of "machines"
		// before we start slicing up in to "PER_PAGE" chunks

	public $offset;
		// The starting server to display, as a number

	public $globals = array();
		// List of global zones (and Solaris servers which don't run zones)

	public $locals = array();
		// List of local zones

	public $pldoms = array();
		// List of logical domains which are physical servers.  Subset
		// of $globals

	public $ldoms = array();
		// List of logical domains which are not physical servers.  Subset
		// of $globals

	public $domu = array();
		// XEN domUs
	
	public $kvm = array();
		// KVM guests
	
	public $vbox = array();
		// List of virtualboxes. Subset of $globals

	public $xvms = array();
		// list of XEN virtual machines. Subset of $globals

	public $vmws = array();
		// List of VMware virtual machines. Subset of $globals

	public $unknowns = array();
		// Unknown platforms

	public $servers = array();
		// on the face of it, like $this->globals, but associative. Each key
		// is a server name, and multiple values are zone names

	public $map = array();
		// Map of zone name to zone filename

	public $af_vers = array();
		// Versions of audit files

	public function set_extra_paths($dir)
	{
		return array(
			"uri_map" => "${dir}/network/uri_list.txt",
			"ip_list_file" => "${dir}/network/ip_list.txt",
			"ip_res_file" => "${dir}/network/ip_list_reserved.txt",
			"extra_dir" => "${dir}/extras"
		);

	}
	
	public function get_path($key)
	{
		return $this->paths[$key];
	}

	public function list_globals()
	{
		// Returns an array of all global zones

		return $this->globals;
	}
	
	public function list_pldoms()
	{
		// Returns an array of all LDOMs which are also physical servers

		return $this->pldoms;
	}

	public function list_ldoms()
	{
		// Returns an array of all LDOMs which aren't also physical servers

		return $this->ldoms;
	}

	public function list_vbox()
	{
		// Returns an array of all virtualboxes

		return $this->vbox;
	}

	public function list_vmws()
	{
		// Returns an array of hosts running as VMware instances

		return $this->vmws;
	}

	public function list_unknowns()
	{
		// Returns an array of machines with unknown virtualization

		return $this->unknowns;
	}

	public function list_dom0s()
	{
		// Returns an array of hosts running as XEN dom0 domains

		return $this->dom0;
	}

	public function list_domus()
	{
		// Returns an array of hosts running as XEN domU domains

		return $this->domu;
	}

	public function list_kvms()
	{
		// Returns an array of hosts running as KVM guests

		return $this->kvm;
	}


	public function list_locals()
	{
		// Returns an array of all non-global zones

		return $this->locals;
	}

	public function list_all()
	{
		// Returns an array of all known zones

		$all = array_merge($this->globals, $this->locals);
		sort($all);

		return $all;
	}

	public function list_server_zones($server) 
	{
		// Returns an array of zones belonging to the given server. False if
		// there are no zones or if the server doesn't exist

		$ret = (isset($this->servers[$server]))
			? $this->servers[$server]
			: false;

		if (is_array($ret))
			sort($ret);

		return $ret;
	}

	public function get_af_ver($server)
	{
		// Return the version of the audit file for the given server

		$ret = false;

		if (isset($this->map[$server])) {
			$ret = $this->af_vers[$this->map[$server]];
		}
		else {
			// try stripping off "/global"
	
			$newname = preg_replace("/\/global/", "", $server);

			if (isset($this->map[$newname])) {
				$ret = $this->af_vers[$this->map[$newname]];
			}
		}

		return $ret;
	}

	public function is_global($zone)
	{
		// returns true if the given zone is global, false if it's not

		return (in_array($zone, $this->globals))
			? true
			: false;
	}

	// XXX CAN'T BE TRUSTED - ZONES WITH THE SAME NAMES CONFUSE IT

	public function get_parent_zone($zone)
	{
		// Return the global zone which manages the given local

		$ret = false;

		foreach($this->servers as $server=>$zones) {

			if (in_array($zone, $zones)) {
				$ret = $server;
				break;
			}

		}

		return $ret;
	
	}

	public function has_data($zone)
	{
		return in_array($zone, $this->list_all())
			? true
			: false;
	}

}

class GetServersBase {

	// This class gets all the server information and puts it all in a great
	// big, horrible, data structure ($servers) 

	protected $servers = array();	// said data structure

	public function get_array()
	{
		// Return a great big data structure which describes a list of
		// servers

		return $this->servers;
	}

	public function get_parent_prop($map, $zone, $class, $prop)
	{
		$p = $map->get_parent_zone($zone);
		
		if (isset($this->servers[$p][$class][$prop]))
			$r = $this->servers[$p][$class][$prop];

		return (count($r) == 0)
			? $r[0]
			: $r;
	}

}

?>
