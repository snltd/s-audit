<?php

// These classes get audit data from flat files. They extend the classes in
// reader_classes.php

//

// Since we're reading files, we need the Filesystem:: class

require_once(LIB . "/filesystem_classes.php");

//----------------------------------------------------------------------------
// ZONE MAP

class ZoneMap extends ZoneMapBase {

	// Extend the ZoneMapBase class so audit data can be read in from flat
	// files

	public function __construct($audit_dir, $can_be_empty = false)
	{
		// If you don't mind there being no files in an audit directory,
		// call with the second arg as true. 

		$this->offset = (isset($_GET["o"])) ? $_GET["o"] : 0;

		if (!is_dir($audit_dir))
			page::error("missing directory [${audit_dir}]");

		$server_dirs = filesystem::get_files($audit_dir, "d");
		$this->count = sizeof($server_dirs);

		// Throw away server_dirs we aren't interested in, but not if we're
		// on the single server view page

		if (!defined("HOST_COLS"))
			$server_dirs = array_slice($server_dirs, $this->offset, PER_PAGE);

		if (sizeof($server_dirs) == 0) {

			if ($can_be_empty)
				return;
			else
				page::error("no server information [$audit_dir]");
		}

		// Loop through all the directories we have, building up the
		// publicly visible arrays

		foreach($server_dirs as $d) {
			$sname = basename($d);
			$this->globals[] = $sname;

			// Look at the virtualization type in the global zone's platform
			// file. There always be a single entry, so the current() is
			// safe

			$hwf = "${d}/audit.${sname}.platform";

			if (file_exists($hwf)) {
				$fdat = file($hwf);

				$virt = current(preg_grep("/^virtualization/", $fdat));

				// Look for LDOMs which aren't primaries, and VirtualBoxes

				if (preg_match("/(?<!primary) LDOM/i", $virt))
					$this->ldoms[] = $sname;
				elseif (preg_match("/VirtualBox/i", $virt))
					$this->vbox[] = $sname;

			}
			
			foreach(filesystem::get_files($d, "f") as $f) {

				// Do some regexp work to get the filename base (match[1])
				// and the zone name (match[2])

				preg_match("/(^.*\/audit\.([^\.]*))\..*$/", $f, $match);

				$fbase = $match[1];
				$zname = $match[2];
				$tmp_arr[$zname] = $fbase;

				if ($sname != $zname && ! in_array($zname, $this->locals))
					$this->locals[] = $server_arr[] = $zname;

			}

			if (isset($server_arr)) {
				$this->servers[$sname] = $server_arr;
				unset($server_arr);
			}

		}

		$this->map = $tmp_arr;
	}

}

//----------------------------------------------------------------------------
// GetServers class

class GetServers extends GetServersBase {

	// Extend the GetServersBase class to get data from flat files

	public function __construct($map, $pattern = false, $s_list = false)
	{
		// $map is the map created by ZoneFileMap
		// $pattern is the audit class
		// $s_list can be an array of servers to get. If false, then look to
		// see if no_zones is set in the $_GET[] array. If so, get all
		// global zones, if not, get all known zones

		if (!$s_list) {

			if (isset($_GET["no_zones"])) {
				$s_list = $map->list_globals();
				define("NO_ZONES", true);
			}
			else
				$s_list = $map->list_all();
		}

		foreach($s_list as $s) {
			$this->servers[$s] = $this->get_zone($map->get_base($s),
			$pattern);
		}

	}
	
	public function get_zone($file, $pattern)
	{
		// Returns an array of zones. i.e. a whole server
		// Pattern is a string, or an array of strings, to be used in
		// creating a list of files which are parsed with parse_file()

		// If we know the file and we know the pattern, we can just get it.
		// If we have an array of patterns, loop through them.

		if (is_array($pattern)) {
			$ret = array();

			foreach($pattern as $pat) {
				$ret = array_merge($ret, $this->parse_file("${file}.$pat"));
			}

		}
		else 
			$ret = GetServers::parse_file("${file}.$pattern");

		return $ret;

	}

	public function parse_file($file)
	{
		// Returns a multidimensional array of data in the given file. i.e.
		// for a single zone Of the form:
		// Array
		// (
		//     [hostname] => Array
		//         (
		//             [0] => stephen
		//         )
		//
		//     [platform] => Array
		//         (
		//             [0] => Sun Fire V210
		//         )
		// I know all those single element arrays look clumsy, and sometimes
		// there's a lot of seemingly unnecessary references to [0]
		// elsewhere, but it's the best way of hanlding things when you
		// don't know if there will be one of them, or many. For instance
		// disks, NICs, missing patches, websites etc.
		
		// If the file is missing, or zero size, return the name of the file
		// so other functions can display it in error messages

		if (file_exists($file) && (filesize($file) > 0)) {

			$data = file($file);

			foreach($data as $line) {
				$line = trim($line);
				$key = preg_replace("/=.*$/", "", $line);
				$ret[$key][] = preg_replace("/^[^=]*=/", "", $line);
			}

		}
		else 
			$ret = $file;

		return $ret;
	}

}

//----------------------------------------------------------------------------
// IP listing 

class GetIPFromAudit {

	// Used by the GetIPList class. Pulls all IP addresses out of audit
	// files. Does NICs and ALOMs. Populates an array addrs[] which pairs
	// ip_addr => "hostname (nic)"

	private $addrs = array();
	
	public function __construct($map)
	{
		// We need to look at everything to be sure we catch exclusive IP
		// instances. Look for LOMs and NICs

		foreach ($map->list_all() as $zone) {
			$df = $map->get_base($zone) . ".platform";
		
			if (!file_exists($df))
				continue;

			$nic = $this->get_value($df, "NIC");
			$nic = preg_grep("/^\w+[:\d]+\|\d/", $nic);
			
			// The same IP address can be overwritten because it may exist
			// in both a global and local zone.  But I don't think it
			// matters.

			foreach($nic as $n) {
				$a = explode("|", $n);
				
				// Put in the names of non-aliased interfaces

				$if = (!preg_match("/:/", $a[0])) 
					? " $a[0]"
					: "";

				$this->addrs[$a[1]] = "${zone}$if";
			}

			$alom = $this->get_value($df, "ALOM IP");

			if (isset($alom[0]))
				$this->addrs[$alom[0]] = "$zone LOM"; 

		}

	}
	
	private function get_value($file, $key)
	{
		// Get the value paired with key in the named file. Returns values
		// as an array

        $ret_arr = array();

        $file_pointer = fopen("$file", "r");

        for ($i = 0; !feof($file_pointer); $i++) {
            $line = fgets($file_pointer, 1024);

            if (preg_match("/^${key}=/", $line))
                $ret_arr[] = trim(preg_replace("/^${key}=/", "", $line));
        }

        return $ret_arr;
    }

	public function get_ips()
	{
		// Method to pass back the addresses

		return $this->addrs;
	}

}
