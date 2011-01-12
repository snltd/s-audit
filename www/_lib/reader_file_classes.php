<?php

//----------------------------------------------------------------------------
// DATA COLLECTION

class ZoneFileMap {

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

	private $audit_dir;
		// Directory to map out

	public function __construct($audit_dir, $can_be_empty = false)
	{
		// If you don't mind there being no files in an audit directory,
		// call with the second arg as true. 

		$this->audit_dir = $audit_dir;
		$this->offset = (isset($_GET["o"])) ? $_GET["o"] : 0;

		if (!is_dir($this->audit_dir))
			page::error("missing directory [". $this->audit_dir. "]");

		$server_dirs = filesystem::get_files($this->audit_dir, "d");
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

class GetServers {

	// This class gets all the server information out of files, and puts it
	// all in a great big, horrible, data structure ($servers) 

	public $servers;

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

//----------------------------------------------------------------------------
// FILESYSTEM ACCESS

class Filesystem {

	static function get_files($base, $type = false, $pattern = false) {

		// Returns an array of files in the directory given by the first
		// argument used to call it. If the second argument is "d" then it
		// returns a list only of directories; if it is "a" then it returns
		// files and directories. Hidden files/directories are not returned.
		// Needed by menu generator

		if (!is_dir($base))
			return false;

		$dh = opendir($base);

		$file_list = $dir_list = array();

		while ($candidate = readdir($dh)) {

			// If we're using a pattern, discard things which don't match it

			if ($pattern) {

				if (!pret_match("/$pattern/", basename($candidate)))
					continue;

			}
			// drop all the hidden files 
	
			if (!preg_match("/^\./", $candidate)) {
	
				$candidate = "${base}/${candidate}";
	
				if (!file_exists($candidate))
					continue;
		
				if (is_dir($candidate))
					$dir_list[] = $candidate;
				else
					$file_list[] = $candidate;
						
			}
	
		}

		closedir($dh);

		sort($dir_list);
		sort($file_list);

		if ($type == "d")
			$ret_arr = $dir_list;
		elseif($type == "f") 
			$ret_arr = $file_list;
		else
			$ret_arr = array_merge($dir_list, $file_list);

		return $ret_arr;
	}

	static function all_fnames($file) {
		
		// Returns all the information about a link 

		$links["file"] = $file;
		$links["url"] = str_replace(ROOT, "", $file);
		$links["link"] = basename($file);

		return $links;
	}

	static function get_lines($file, $string, $count = -1) {

		// return an array of the first $count lines in a given file
		// containing the given string

		if (!file_exists($file))
			return false;

		$ret_arr = array();
		$file_pointer = fopen("$file", "r");
		$c = 0;

		for ($i = 0; !feof($file_pointer); $i++) {
			$line = fgets($file_pointer, 1024);

			if (preg_match("/$string/", $line)) {
				$ret_arr[] = $line;
				$c++;

				if ($c == $count)
					break;
			}

		}

		fclose($file_pointer);
		return ($c > 0) ? $ret_arr : false;
	}

	static function getline($file, $string) {

		// return the first line in a given file containing the given string 

		if (!file_exists($file))
			return false;

		$file_pointer = fopen("$file", "r");

		for ($i = 0; !feof($file_pointer); $i++) {
			$line = fgets($file_pointer, 1024);

			if (preg_match("/$string/", $line))
				break;

		}

		fclose($file_pointer);
		return (isset($line)) ? $line : false;
	}

	static function get_dirs($d)
	{
		// return an array of all the zones in the site under $dir to which
		// the user is allowed access

		$ret_arr = array();

		// get a list of zones from the filesystem. We could cache this if
		// we were smart

		$zones = Filesystem::get_files($d, "d");

		// If this site has zones defined, look at each zone, see if we have
		// the privs to see it, and if so, add it to the string we're
		// returning


		if ($zones) {

			foreach ($zones as $zone)
				$ret_arr[] = $zone;
		}

		return $ret_arr;
	}

	static function move_files($files, $dest)
	{
		// Move a bunch of files to a directory. Syntax just like mv(1).
		// First argument can be an array

		$ret = true;
		
		if (!is_array($files))
			$files = array($files);

		if (!is_dir($dest)) {
			echo "\n\n<p class=\"error\">$dest is not a directory.</p>";
			$ret = false;
		}
		elseif (!is_writeable($dest)) {
			echo "\n\n<p class=\"error\">$dest is not writeable.</p>";
			$ret = false;
		}
		else {

			foreach($files as $file) {

				if (!rename($file, $dest . "/" . basename($file)))
					$ret = false;

			}

		}

		return $ret;
	}

	static function rm_empty_dir($dir)
	{
		// Remove a directory if it's empty

		$files = filesystem::get_files($dir);

		if ($files == 0)
			rmdir($dir);
	}

}

?>
