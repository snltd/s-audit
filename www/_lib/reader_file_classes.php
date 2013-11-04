<?php

//============================================================================
//
// reader_file_classes.php
// -----------------------
//
// These classes get audit data from flat files. They extend the classes in
// reader_classes.php
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once(LIB . "/filesystem_classes.php");

//----------------------------------------------------------------------------
// ZONE MAP

class ZoneMap extends ZoneMapBase {

	// Extend the ZoneMapBase class so audit data can be read in from flat
	// files. Now we just use a single flat file per server.

	// Class variables are defined in the ZoneMapBase class in
	// reader_classes.php

	private static $map_instance;
		// The map is now a singleton

	private $fs;

	private function __construct()
	{
		// Record the start time

		$this->t_start_map = microtime(true);

		// Create a filesystem object

		$this->fs = new filesystem;

		// Get the offset, if we have one

		$this->offset = (isset($_GET["o"])) ? $_GET["o"] : 0;

		// work out what directory this group's audit data is in. If there
		// isn't a group, that's an error. We need the _POST when we do
		// comparisons

		if (isset($_GET["g"]))
			$group = $_GET["g"];
		elseif (isset($_POST["g"]))
			$group = $_POST["g"];

		if (isset($group))
			$audit_dir = AUDIT_DIR . "/${group}/hosts";
		else
			page::f_error("No audit group.");

		// Check we've got some data

		if (!is_dir($audit_dir))
			page::f_error("missing directory. [${audit_dir}]");

		$server_dirs = $this->fs->get_files($audit_dir, "d");

		if (sizeof($server_dirs) == 0)
			page::f_error("no audit data. [${audit_dir}]");

		// Throw away server_dirs we aren't interested in, but not if we're
		// on the single server view page. Oh, and record how many there
		// originally were

		$this->all = count($server_dirs);

		if (!defined("SINGLE_SERVER"))
			$server_dirs = array_slice($server_dirs, $this->offset,
			PER_PAGE);

		// Create part of the map. Each directory is a server.
		// globals[] is a list of servers
		// map[] is server->file

		foreach($server_dirs as $d) {
			$sn = basename($d); 
			$sf = "${d}/${sn}.machine.saud";
			
			if (file_exists($sf)) {
				$this->globals[] = $sn;
				$this->map[$sn] = $sf;
			}

		}

		$this->paths = $this->set_extra_paths(AUDIT_DIR . "/" . $group);
	}
	
	public static function getInstance()
	{
		if (!self::$map_instance)
			self::$map_instance = new ZoneMap();

		return self::$map_instance;
	}

	public function get_fname($server)
	{
		// Return the audit filename for the given server

		return (in_array($server, array_keys($this->map)))
			? $this->map[$server]
			: false;
	}

}

//----------------------------------------------------------------------------
// GetServers class

class GetServers extends GetServersBase {

	// Extend the GetServersBase class to get data from flat files

	protected $globals;

	public function __construct($map, $s_list = false, $cl = false)
	{
		// $map is the map created by ZoneFileMap
		// $s_list can be an array of servers to get. 
		// $cl is a class or list of classes
		
		// If we have no $s_list, look to see if no_zones is set in the
		// $_GET[] array. If so, get all global zones, if not, get all known
		// zones. This is probably pointless, but a hangover from when we
		// used to have a file for each zone and each audit type

		if (isset($_GET["h"]))
			define("NO_ZONES", true);

		if ($cl && is_string($cl))
			$cl = array($cl);

		$cl[] = "platform";

		$this->globals = $map->list_globals();
		$this->map = $map;

		if (!$s_list)
			$s_list = $this->globals;

		if (is_string($s_list))
			$s_list = array($s_list);

		foreach($s_list as $g) {

			// We might have been asked for a zone and told what server file
			// to use

			if (preg_match("/@/", $g)) {
				$a = explode("@", $g);
				$c_g = $srv_b = $a[1];
				$hn = $a[0];
			}
			elseif(preg_match("/\//", $g)) {

				// This is how they come from the cycle gadget on the
				// compare page

				$a = explode("/", $g);
				$c_g = $srv_b = $a[0];
				$hn = $a[1];
				$compare = true;
			}
			else {
				$srv_b = $g;
				$hn = false;
			}

			if ($f = $map->get_fname($srv_b)) {
				$this->servers = array_merge($this->servers,
				$this->parse_m_file($f, $cl, $hn));

				// If we were only asked for a local zone, also get the
				// global zone's O/S audit data

				if (isset($a)) {
					$gl_nm = (isset($compare))
						? "$a[1]/$a[1]"
						: $a[1];
					
					$this->servers = array_merge($this->servers,
					$this->parse_m_file($f, array("platform", "os"),
					$gl_nm));
				}
			}

		}

		// Finish the map

		foreach($this->servers as $server) {

			if(!isset($server["platform"]["hostname"][0]))
				return;

			$hn = $server["platform"]["hostname"][0];

			// Catch non-running or branded zones - they just show up as "zone"

			$v = (isset($server["platform"]["virtualization"][0]))
				? $server["platform"]["virtualization"][0]
				: "zone";
			
			// type of VM

			$t = preg_replace("/ .*/", "", $v);

			// c_g is the current global zone. If we're not examining a
			// zone, set it to the current hostname.

			if ($t != "zone")
				$c_g = $hn;

			if ($t == "VirtualBox")
				$map->vbox[] = $hn;
			elseif ($t == "primary")
				$map->pldoms[] = $hn;
			elseif ($t == "guest")
				$map->ldoms[] = $hn;
			elseif ($t == "xVM") {

				if (preg_match("/domU/", $v))
					$map->domu[] = $hn;
				else
					$map->dom0[] = $hn;

			}
			elseif($t == "KVM")
				$map->kvm[] = $hn;
			elseif($t == "VMware")
				$map->vmws[] = $hn;
			elseif($t == "zone") {
				$map->locals[] = "${c_g}/$hn";
				$map->servers[$c_g][] = $hn;
				}
			elseif($t == "undetermined")
				$map->unknowns[] = $hn;
			
		}

	}

	private function parse_m_file($file, $cl = false, $zone = false)
	{
		// Only get classes in the second arg and zones in the third (if
		// supplied)

		if ($cl && is_string($cl))
			$cl = array($cl);

		// Parse an all-in-one machine file into an array like this
		//
		// [global_hn] => [os]
		//             => [net]
		// [gobal_hn/zone_1_hn] => [os]
		//                      => [net]
		// etc. etc.

		// File is readable?

		if (!is_readable($file))
			page::warn("Audit file is not readable. [${file}]");

		// Read in the file - don't add newlines to each record

		if (!$fa = file($file, FILE_IGNORE_NEW_LINES))
			page::warn("Could not read file. [${file}]");

		$global = basename(dirname($file));

		//$match = ($this->map->is_global($zone)) ? $global : $zone;

		$match = ($zone) ? $zone : $global;

		$last_row = count($fa) - 1;

		// Sanity check. Split the header into chunks and check they look
		// okay

		preg_match("/^([^ ]+) v-([^ ]*) .*$/", $fa[0], $ca);

		if ((count($ca) != 3) || ($ca[1] != "@@BEGIN_s-audit")) {
			$this->map->af_vers[$file] = "invalid";
			page::warn("Invalid audit file. [${file}]");
			return array();
		}

		// Write the audit file version into the map. The key is the
		// filename, the value is the version number

		$this->map->af_vers[$file] = $ca[2];

		if ($ca[2] > MAX_AF_VER || $ca[2] < MIN_AF_VER) {
			page::warn("Invalid audit file version. [${file}]");
			return array();
		}

		// And do we have a good-looking footer?

		if ($fa[$last_row] != "@@END_s-audit")
			page::warn("Invalid footer. [${file}]");

		// We're good to go. Read through the file in chunks. Remember each
		// audit type/zone is delimited by BEGIN class@hostname. We've
		// already checked the first and last lines, so get rid of them.

		unset($fa[0], $fa[$last_row]);
		$ret = array();
		$skip = 0;

		$this->map->af_vers[$file] = $ca[2];

		foreach($fa as $l) {

			if (preg_match("/^BEGIN ([^@]+)@(.*)$/", $l, $a)) {

				// a[1] is the audit class
				// a[2] is the zone name. For files from >= 3.2, the machine
				//      itself is called 'global'; before that is was called
				//      by its hostname

				// If we hit a BEGIN line, start recording the data in a
				// temporary array

				if  ($a[2] == "global") {
					$this_h = $global;
				}
				else {
					$this_h = $a[2];
				}

				$inside = $a[2];
				$this_c = $a[1];
				$element_name = "${global}/$this_h";

				// Remember the file format changed in 3.2. We have to
				// correct for it here

				if (($cl && ! in_array($this_c, $cl)) ||
				(defined("NO_ZONES") && !in_array($this_h, $this->globals))
				|| ($zone && ($this_h != $match))) {
					$skip = 1;
				}
				else {
					$TMP = array();
					$skip = 0;
				}

			}
			elseif ($skip == 0 && preg_match("/^END (.*)/", $l, $e)) {

				// If we hit the END line corresponding to the last BEGIN,
				// store the data. If not, store an error
				

				$ret[$element_name][$this_c] = ($e[1] == "${this_c}@$inside")
					? $tmp
					: "ERROR";

				if (isset($tmp)) unset($tmp);

			}
			elseif ($skip == 0) {
				// not a beginning or an end, so must be data

				preg_match("/^([^=]+)=(.*)$/", $l, $d);

				// Silently fail if we don't get key=value. This is probably
				// a bad idea...

				if (sizeof($d) == 3)
					$tmp[$d[1]][] = $d[2];
				else
					continue;

			}

		}

		return $ret;
	}

}

?>
