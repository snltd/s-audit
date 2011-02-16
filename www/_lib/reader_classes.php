<?php

// These classes take raw audit data and ready it for processing by the
// display classes. They are incomplete base classes, having "missing"
// methods which pull in the data from wherever it resides, be it flat
// files, MySQL or whatever gets added.

// The correct extension class file is included here

require_once(LIB . "/reader_file_classes.php");

//----------------------------------------------------------------------------
// GENERAL DATA COLLECTION

class ZoneMapBase {

	// This is a very important class. It produces and reports from arrays
	// which define the way zones relate to each other. It also has a map of
	// zones

	public $count;
		// How many "machines" we know about. They could be physical or
		// virtual

	public $offset;
		// The starting server to display, as a number

	public $globals = array();
		// List of global zones (and Solaris servers which don't run zones)

	public $locals = array();
		// List of local zones

	public $ldoms = array();
		// List of logical domains which are not physical servers. Subset of
		// $globals

	public $vbox = array();
		// List of virtualboxes. Subset of $globals

	public $servers = array();
		// on the face of it, like $this->globals, but associative. Each key
		// is a server name, and multiple values are zone names

	public $map = array();
		// Map of zone name to *part of* zone filename. The audit type
		// (platform, security etc.) is missing off the end)

	public $friends = array();
		// Map of paired servers/zones

	public function in_map($server)
	{
		// Returns true if the given server is in our map. False otherwise.

		return (in_array($server, $this->list_all()))
			? true
			: false;
	}

	public function has_data($zone)
	{
		// returns true if there's valid audit data for the given zone

		return ($this->get_fbase($zone))
			? true
			: false;
	}

	public function get_dir($zone)
	{
		// Returns the directory holding the zone audit files

		return ($ret = $this->get_base($zone))
			? dirname($ret)
			: false;
	}

	public function get_fbase($zone)
	{
		// returns the first part of the audit filenames belonging to the
		// given zone

		return ($ret = $this->get_base($zone))
			? basename($ret)
			: false;
	}

	public function get_base($zone)
	{
		// Returns a string pointing to the base of the audit files for the
		// given zone

		return (isset($this->map[$zone]))
			? $this->map[$zone]
			: false;
	}

	public function list_globals()
	{
		// Returns an array of all global zones

		return $this->globals;
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

	public function is_global($zone)
	{
		// returns true if the given zone is global, false if it's not

		return (in_array($zone, $this->globals))
			? true
			: false;
	}

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

	public function get_zone_prop($zone, $prop, $type = false)
	{

		// Returns a the given property, from "type audit, of given zone
		// NOTE - if it's a single element array, this is returned as a
		// string, which better suits the way this function is probably
		// going to be used

		if ($zarr = GetServers::get_zone($this->get_base($zone), $type)) {

			$ret = (sizeof($zarr[$prop]) == 1)
				? $zarr[$prop][0]
				: $zarr;

		}
		else
			$ret = false;

		return $ret;
	}

	public function get_parent_prop($zone, $prop, $type = false)
	{
		// Returns a the given property, from "type audit, of the global
		// zone which owns the zone given in arg[0]. NOTE - if it's a single
		// element array, this is returned as a string, which better suits
		// the way this function is probably going to be used

		$parent = $this->get_parent_zone($zone);;

		return $this->get_zone_prop($parent, $prop, $type);
	}

	public function get_pairs()
	{
		// Returns a list of paired servers

		// There's no way we could work some pairs out, so you can hard-code
		// them here, in the $friends[] array

		//$friends = array("stephen" => "stanley");
		$friends = array();
		
		$all = $this->list_all();

		foreach($all as $zone) {

			// We may already have found a match for this zone

			if (array_key_exists($zone, $friends) || (in_array($zone,
			array_values($friends))))
				continue;

			// Try to work out what the paired zone should be called

			if(strpos("01", $zone))
				$pair =  preg_replace("/01/", "02", $zone);
			elseif(strpos("02", $zone))
				$pair =  preg_replace("/02/", "01", $zone);

			if (isset($pair) && in_array($pair, $all) &&
				($this->has_data($zone) && $this->has_data($pair)))
				$friends[$zone] = $pair;

		}

		ksort($friends);

		return $friends;
	}
}

class GetServersBase {

	// This class gets all the server information and puts it all in a great
	// big, horrible, data structure ($servers) 

	public $servers;

	public function get_all_zone_names()
	{
		// Return an array of all known zones, both global and local

		$ret_arr = array();

		foreach(array_values($this->servers) as $global) {
			
			foreach(array_keys($global) as $zone) {
				$ret_arr[] = $zone;
			}

		}

		return $ret_arr;
	}

	public function get_array()
	{
		// Return a great big data structure which describes a list of
		// servers

		return $this->servers;
	}

}

//----------------------------------------------------------------------------
// PLATFORM AUDIT

class GetServersPlatform extends GetServers
{

	public function __construct($map, $slist = false)
	{
		parent::__construct($map, "platform", $slist);
	}

}

//----------------------------------------------------------------------------
// O/S AUDIT

class GetServersOS extends GetServers
{
	public function __construct($map, $slist = false)
	{
		parent::__construct($map, "os", $slist);
	}

}

//----------------------------------------------------------------------------
// NET AUDIT

class GetServersNet extends GetServers
{
	public function __construct($map, $slist = false)
	{
		parent::__construct($map, "net", $slist);
	}

}

//----------------------------------------------------------------------------
// APPLICATION AUDIT

class GetServersApp extends GetServers
{
	// Parse the audit files for an application display

	public function __construct($map, $slist = false)
	{
		parent::__construct($map, "app", $slist);
	}

}

//----------------------------------------------------------------------------
// TOOL AUDIT

class GetServersTool extends GetServers
{
	// Parse the audit files for a tool display

	public function __construct($map, $slist = false)
	{
		parent::__construct($map, "tool", $slist);
	}

}

//----------------------------------------------------------------------------
// FS AUDIT

class GetServersFS extends GetServers
{

	public function __construct($map, $slist = false)
	{
		parent::__construct($map, "fs", $slist);
	}

}

//----------------------------------------------------------------------------
// HOSTED SERVICES AUDIT

class GetServersHosted extends GetServers
{
	// Parse the audit files so we can make a hosted services grid

	public function __construct($map, $slist = false)
	{
		parent::__construct($map, "hosted", $slist);
	}
}

//----------------------------------------------------------------------------
// SECURIY AUDIT

class GetServersSecurity extends GetServers
{
	public function __construct($map, $slist = false)
	{
		parent::__construct($map, "security", $slist);
	}

}

//----------------------------------------------------------------------------
// SERVER COMPARISON

class GetServersCompare extends GetServers
{
	public function __construct($map, $slist)
	{
		$call = array("platform", "os", "fs", "app", "tool", "plist",
		"hosted");

		parent::__construct($map, $call, $slist);
	}
}

//----------------------------------------------------------------------------
// SINGLE SERVER VIEW

class GetServerSingle extends GetServers {

	public $all_data;

	// Make an array of arrays. Each sub-array is of the normal "platform",
	// "tool", "fs" type.

	public function __construct($map, $server)
	{
	
		$types = array("platform", "os", "fs", "app", "tool", "hosted",
		"security", "plist");

		foreach ($types as $type) {
			$this->all_data[$type] =
			$this->get_zone($map->get_base($server), $type);
		}

	}


}

?>
