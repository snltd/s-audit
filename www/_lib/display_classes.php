<?php

//============================================================================
//
// display_classes.php
// -------------------
//
// Classes which sort, process, and display audit data.
//
//
//============================================================================

define("MY_VER", "3.0");
    // Interface software version

//- generic host grid base class ---------------------------------------------

class HostGrid {
	
	// This class holds everything needed to make up a "generic" audit
	// display grid. It deals only with data presentation and massaging, and
	// requires its data to be read in by a reader class. All
	// show_property() functions belong to it, but they may be overriden by
	// classes which extend it.

	// The contents of this class used to be scattered all over the place,
	// but now they're in one file so things like the full server audit can
	// get access to all the fancy printing methods, and so that if checks
	// move from one audit to another, which they sometimes do, the
	// interface doesn't have to be changed.
	
	//------------------------------------------------------------------------
	// VARIABLES

	protected $servers;
		// The big data structure we get from the reader class

	protected $fields;
		// A numbered list, starting at 0, of the fields we will print,
		// pulled out of a global zone audit file

	protected $audit_dir = LIVE_DIR;
		// The top level audit directory. This holds a directory for every
		// known server

	protected $hidden_fields = array("zone status");
		// This field is never shown in its own column. It's handled
		// elsewhere

	protected $adj_fields = array();
		// hints for where to put fields. If you want one field to follow
		// another, put the two of them in the $adj_fields[] array, in the
		// order you wish them to appear.
		
	protected $key_filename = false;
		// Can be used to override the default key filename

	protected $grid_key = false;
	protected $grid_notes = false;
		// Data for keys and notes, at the foot of the page

	protected $show_zones = true;

	//------------------------------------------------------------------------
	// METHODS

	public function __construct($map, $servers)
	{
		// A simple constructor which just populates a few arrays.

		$this->servers = $servers;
		$this->fields = $this->get_fields();
		$this->fields = $this->sort_fields("audit completed");
		$this->map = $map;

		$this->show_zones = (isset($_GET["z"]) && ($_GET["z"] == "hide"))
			? false
			: true;

		$this->get_key();
	}

	protected function get_key()
	{
		// Each audit page has a key held in a file. Work out what that file
		// should be called. If we have it, include it, and populate the
		// $grid_key and $grid_notes variables with its contents. There is
		// also a generic key file which is always included

		include_once(KEY_DIR . "/key_generic.php");

		$kfn = ($this->key_filename)
			? $this->key_filename
			: "key_" . basename($_SERVER["SCRIPT_FILENAME"]);

		$keyfile = KEY_DIR . "/" . $kfn;

		if (file_exists($keyfile))
			include_once($keyfile);
		else
			$grid_key = array();

		$this->grid_key = array_merge($generic_key, $grid_key);

		if (isset($grid_notes))
			$this->grid_notes = $grid_notes;

		unset($generic_key, $grid_key, $grid_notes);

	}

	protected function sort_fields($last = false)
	{
		// This function isn't that clever, and OTOH I can think of two ways
		// to break it. USE WITH CARE! It sorts the fields according to the
		// hints in the $adj_fields[] array

		$ret_arr = array();
		$tmp =& $this->fields;
		$keys = array_keys($this->adj_fields);

		foreach($tmp as $c) {
			// Put this element in the output array

			if ($c == $last)
				$lastflag = true;
			else
				$ret_arr[] = $c;

			// If the element we've just placed is half of a pair, see if we
			// have the other half of the pair, and if so, put it next in
			// the array, then remove it from the source array

			for ($i = 0; $i < sizeof($keys); $i++) {

				if (in_array($c, $keys) && in_array($this->adj_fields[$c],
				$tmp)) {
					$ret_arr[] = $try = $this->adj_fields[$c];
					$old_key = array_search($this->adj_fields[$c], $tmp);
					unset($tmp[$old_key]);
					$c = $try;
				}

			}

		}

		if (isset($lastflag))
			$ret_arr[] = $last;

		return $ret_arr;
	}

	private function get_fields()
	{

		// We need to know how many fields to print, and what those fields
		// are. This has to be worked out from the audit files.

		// We simultaneously try two ways of doing it. The first is to find
		// the audit file with the most unique keys. Secondly, we make a
		// list of *all* keys. We then compare these two lists, and if the
		// all keys list is bigger, we use that. It's preferable to use the
		// file with the most keys, as that preserves the order of keys from
		// the file. (For instance, it keeps "Apache" and "shared modules"
		// together

		// returns an array of fields, This is used for headers and to line
		// up data in the table

		$biggest = $all_keys = array();

		foreach($this->servers as $zone) {
			unset($temp_arr);

			if (!is_array($zone))
				continue;

			foreach(array_keys($zone) as $col_name) {
				$temp_arr[] = $col_name;

                if (!in_array($col_name, $all_keys))
                    $all_keys[] = $col_name;

			}

			if (sizeof($temp_arr) > sizeof($biggest))
				$biggest = $temp_arr;

		}

		$use = (sizeof($all_keys) > sizeof($biggest)) 
			? $all_keys 
			: $biggest;

		return (isset($this->hidden_fields))
			?  array_diff($use, $this->hidden_fields)
			: $use;
	}

	protected function fold_line($str, $width = 25)
	{
		// Fold long lines on certain characters. The characters to fold on
		// are the first part of the preg_match() call.
		
		// The fold width can be overriden by defining the $hard_fold
		// variable

		if (isset($this->hard_fold))
			$width = $this->hard_fold;

		$ret_str = "";

		for($i = $j = 0; $i < strlen($str); $i++, $j++) {
			$ret_str .= $str[$i];

			if ($j > $width && preg_match("/[\s|\-_:;,\.]/", $str[$i])) {
				$ret_str .= "\<br/>\n";
				$j = 0;
			}

		}

		return $ret_str;
	}

	//------------------------------------------------------------------------
	// Grid printing functions

 	public function show_grid($width = "95%")
	{
		// Builds up the html grid which presents the audit data. The
		// creation is broken down into methods so different parts can be
		// overriden by specialist classes

		$ret = "";

		return $ret .  $this->grid_head($width) .  $this->grid_body() .
		$this->grid_key() . $this->grid_foot();
	}

	public function grid_head($width)
	{
		// Open the table which holds the main grid

		return "\n<table width=\"$width\" cellpadding=\"1\" "
		. "cellspacing=\"1\" align=\"center\">" . $this->grid_header();
	}

	public function grid_header()
	{
		// Print the horizontal table column headers

		$ret_str = "\n<tr>";

		foreach($this->fields as $field)
			$ret_str .= "<th>$field</th>";

		return $ret_str . "</tr>";
	}

	public function grid_body()
	{
		// Display all the servers we know about

		$ret_str = "";

		foreach($this->map->list_globals() as $server) {
			$ret_str .= $this->show_server($server);
		}

		return $ret_str;
	}

	protected function grid_key()
	{
		// Put in the key. The information for it is held in a file specific
		// to the page using the class right now. It populates the same
		// fields as the data above. 
		
		// There's also a generic key that goes on every page

		$ret = "\n<tr><td class=\"keyhead\" colspan=\"" .
		sizeof($this->fields) . "\">key</td></tr>";

		// Loop through the grid_key data, filling in columns as we go. Each
		// cell can have arbitrarily many key values

		$ret .= "\n<tr>";

		foreach($this->fields as $field) {

			$ret .= (in_array($field, array_keys($this->grid_key))) 
				? $this->grid_key_col($this->grid_key[$field])
				: new Cell();
		}

		$ret .= "</tr>";
		
		/*
		if (is_array($this->grid_notes)) {
			$ret .= "\n<tr><td class=\"keyhead\" colspan=\"" .
			sizeof($this->fields) . "\">notes</td></tr>\n<tr>";

			foreach($this->fields as $field) {

				if (in_array($field, array_keys($this->grid_notes))) {
					$cell = $this->grid_notes[$field];
				}
				else
					$cell = false;


			$ret .= new Cell($cell);
			}

		$ret .= "</tr>";

		}
		*/

		return $ret;
	}
	
	protected function grid_key_col($data, $span = 1)
	{
		// prints columns in grid keys

		$cell = multiCell::open_table();

		foreach($data as $el) {
			$cell .= "\n<tr>" . new Cell($el[0], $el[1], $el[2]) . "</tr>";
		}
		
		return new Cell($cell .= "</table>", false, false, false, $span);
	}

	public function show_server($server)
	{
		// Display the HTML for a single server and all its zones, if
		// necessary

		$ret = $this->show_zone($this->servers[$server]);

		$zl = (defined("NO_ZONES"))
			? false
			: $this->map->list_server_zones($server);

		if (is_array($zl)) {

			foreach($this->map->list_server_zones($server) as $zone) {
				$ret .= $this->show_zone($this->servers[$zone]);
			}

		}

		return $ret;
	}

	public function grid_foot()
	{
		return "\n</table>";
	}

	private function show_zone($data)
	{
		// Returns a row of HTML which describes a single zone.

		// If $data isn't an array, that's an indicator that parse_files hit
		// a zero sized file. If it is, then we have useable data

		if (is_array($data)) {

			// We always have the zone name as [hostname];

			$zname = $data["hostname"][0];
			
			// Are we a local zone, global zone, logical domain primary or
			// other logical domain? With the addition of the
			// "virtualization" field, this has got easier. We may have to
			// parse the platform file to get it though.

			if (isset($data["virtualization"][0]))
				$zv = $data["virtualization"][0];
			else {
				$hw = GetServers::parse_file($this->map->get_base($zname) .
				".platform");

				$zv = isset($hw["virtualization"][0])
					? $hw["virtualization"][0]
					: false;
			}

			if (preg_match("/^VirtualBox/", $zv))
				$row_class = "vb";
			elseif (preg_match("/^primary LDOM/", $zv))
				$row_class = "ldmp";
			elseif (preg_match("/^guest LDOM/", $zv))
				$row_class = "ldm";
			elseif (preg_match("/^none/", $zv))
				$row_class = "server";
			elseif ($this->map->is_global($zv))
				$row_class = "server";
			else
				$row_class = "zone";

			$ret_str = "\n<tr class=\"$row_class\">";

			// Zones which aren't in the running state should only have
			// three elements in their array. Handle those as a special case
		
			if ($this->non_running_zone($data))
				$ret_str .= $this->non_running_zone_print($data);
			else {

				// We already know what fields we want to display, so loop
				// through them and see if the data[] array contains a value
				// for each of them. If it does, look to see if there's a
				// special method for handling that data

				foreach($this->fields as $field) {

					if (in_array($field, array_keys($data))) {

						$method = preg_replace("/\W/", "_", "show_$field");

						if (method_exists($this, $method))
							$ret_str .= $this->$method($data[$field],
							$data);
						else
							$ret_str .= $this->show_generic($data[$field],
							$field);
					}

					// There's no data for this field. Do we want to try to
					// guess some? We do if a method called guess_$field
					// exists.  Guess methods are called with the whole
					// data[] array and $z

					else {
						$guess_method = preg_replace("/ /", "_",
						"guess_$field");

						if (method_exists($this, $guess_method))
							$ret_str .= $this->$guess_method($data);
						else
							$ret_str .= "<td></td>";
					}

				}

			}

			$ret_str .= "</tr>";
		}
		else {

			// Errors. We may get a zero size file, or something the
			// parse_file() function couldn't parse. If someone has used the
			// manage servers page to push a local zone into a state where
			// the parent zone doesn't exist, flag that as an error too.
			// Finally, we may have a missing file.
			//
			// $data is the name of the file. The string _ERR_ will be
			// replaced by the filename in [square brackets] for display.

			if (file_exists($data)) {

				if (filesize($data) == 0)
					$err_str = "zero size file _ERR_";
				else
					$err_str = "wrong format file _ERR_";
			}
			elseif(preg_match("/^\./", $data))
				$err_str = "Missing information for global zone. Local zones
				below.";
			else
				$err_str = "expected file not found _ERR_";

			$ret_str = "\n<tr>" . new Cell("ERROR: " . str_replace("_ERR_",
			"[${data}]", $err_str), "error", false, false,
			sizeof($this->fields)) . "</tr>";
		}

		return $ret_str;
	}

	protected function non_running_zone($data)
	{
		// A check to see if a zone is running or not

		return (sizeof($data) == 3 && isset($data["hostname"])
		&& isset($data["zone status"]) && isset($data["audit completed"]))
			? true
			: false;
	}

	protected function non_running_zone_print($data)
	{
		// This function informs the user that we got a zone in a state
		// other than running. 

		return $this->show_hostname($data["hostname"], $data) 
		. new Cell("no information :: zone is in &quot;"
		. $data["zone status"][0] .  "&quot; state", "solidamber", false,
		false, (sizeof($this->fields) - 2))
		. $this->show_audit_completed($data["audit completed"]);
	}


	//------------------------------------------------------------------------
	// show_property() functions

	protected function show_generic($data)
	{
		// This function prints properties which don't have a dedicated
		// function. It can handle none, one or many rows of data.

		if (is_string($data))
			$ret_str = new Cell($data);
		else {
			if (sizeof($data) == 1)
				$ret_str = new Cell($data[0]);
			elseif(sizeof($data) > 1)
				$ret_str = new multiCell($data);
			else
				$ret_str = new Cell();
		}

		return $ret_str;
	}

	protected function show_parsed_list($arr, $extra = 0)
	{
		// This function works with show_ functions which manipulate the
		// contents of the array they are fed, rather than just colouring
		// them through show_generic().

		// Extra can be
		//  0 : use normal text and a border
		//  1 : use small class
		//  2 : no border
		//  3 : no border, use small class

		// Input is an array of Cell()s

		$rows = sizeof($arr);

		switch($extra) {
			
			case 1:
				$ret = multiCellSmall::open_table();
				$class = "multicellsmall";
				break;

			case 2:
				$ret = multiCell::open_table();
				$class = "multicellnb";
				break;

			case 3:
				$ret = multiCellSmallnb::open_table();
				$class = "multicellsmallnb";
				break;

			default:
				$ret = multiCell::open_table();
				$class = "multicell";
		}

		if ($rows == 0)
			return new Cell();
		elseif($rows == 1) {

			// Is this a single or multiple <td> element? If it's just a
			// single cell, return it. If it's cells concatanated together,
			// we need to use an embedded table.

			// If the cell already has a class, don't override it. If it
			// isn't a cell, make it one.

			$cells = substr_count($arr[0], "<td");

			if ($cells == 0)
				return new Cell($arr[0], $class);
			elseif ($cells == 1) {

				return (preg_match("/class=/", $arr[0]))
					? $arr[0]
					:  preg_replace("/<td/", "<td class=\"$class\"",
						$arr[0]);
			}

		}

		foreach($arr as $el) {
			$ret .= "\n<tr class=\"${class}\">" . $el . "</tr>";
		}

		return new Cell($ret . "</table>");
	}

	protected function show_hostname($data, $extra)
	{
		// Ask the map if this is a global zone or not

		$class = ($this->map->is_global($data[0]))
			? "serverhn"
			: "zonehn";

		return new Cell($data[0], $class, false);

	}

	protected function show_audit_completed($data)
	{
		// A function to pretty print the audit_completed field

		$t_arr = explode(" ", $data[0]);

		// We don't get an audit_completed for "placeholder" servers

		if (sizeof($t_arr) == 2) {

			$d_arr = explode("/", trim($t_arr[1]));

			$time_str = preg_replace("/:\d{2}$/", "", $t_arr[0]);

			$date = mktime(0, 0, 0, $d_arr[1], $d_arr[0], $d_arr[2]);
			$now = mktime();

			if (($now - $date) < 86400) {
				$date_str = false;
				$class = false;
			}
			elseif(($now - $date) < 172800) {
				$date_str = "<div>yesterday</div>";
				$class = "solidamber";
			}
			else {
				$date_str = "$t_arr[1]";
				$class = "solidred";
			}
		}
		else
			$time_str = $date_str = $col = false;

		return new Cell("$time_str $date_str", $class);
	}

	//-- platform ------------------------------------------------------------

	protected function show_memory($data)
	{
		// Show the memory, in suitable units

		$c_arr = array();
		
		foreach($data as $datum) {

			$a = explode(" ", $datum);

			// Highlight machines with no swap

			if ($a[0] == "no") {
				$class = "solidamber";
				$txt = $datum;
			}
			else {
				$sb = units::from_b(units::to_b($a[0]));
				$txt = "$sb $a[1]";
				$class = false;
			}


			$c_arr[] = new cell($txt, $class);

		}

		return $this->show_parsed_list($c_arr);
	}
	
	protected function show_serial_number($data)
	{
		// Print the serial number, on red if it's "TIMED OUT"

		$sn = $data[0];
		
		$class = ($sn == "TIMED OUT") 
			? "solidred"
			: false;

		return new Cell($sn, $class);
	}

	protected function show_hardware($data) 
	{
		// Print the hardware platform. Some things don't report exactly
		// what is printed on the front.

		// Put 32-bit OSes on an amber field.
		// Outline x86
	
		$hwnames = array(
			"Sun Fire T200" => "Sun T2000"
			);

		preg_match("/^(.*) \((.*)\)/", $data[0], $a);

		$hw = (in_array($a[1], array_keys($hwnames)))
			? $hwnames[$a[1]]
			: $a[1];

		$class = (preg_match("/^32-bit/", $a[2]))
			? "solidamber"
			: false;

		$frame = ($a[1] == "i86pc")
			? false
			: inlineCol::box(colours::$plat_cols["sparc"]);

		return new Cell("${hw}<div>($a[2])</div>", $class, $frame);
	}

	protected function show_virtualization($data)
	{
		// Pretty up virtualization info

		$vz = $data[0];
		
		$col = $str = $class = false;

		// Highlight global zones in a blue box

		if (preg_match("/global zone/", $vz))
			$class = "boxblue";

		// Don't print anything if there's no virtualization

		if ($vz == "none" || $vz == "none (global zone)")
			$str = "physical";
		elseif ($vz == "none (global zone)")
			$str = "global zone";
		elseif (preg_match("/^zone \(/", $vz)) {
			
			// For local zones, we highlight whole-root with a red box, and
			// put non-native zones on an amber field

			$za = split("/", preg_replace("/zone \((.*)\)/", "\\1", $vz));

			// za has elements [0] => whole/spare root, [1]=> brand

			// Whole root zones are highlighted in red. They can be of a
			// "non-native" brand. Sparse zones cannot.

			if ($za[0] == "whole root") {
				$class = "boxred";
				$str = "whole root zone";

				// Have to colour this with inline style - we've probably
				// already used the class doing a box
				
				if ($za[1] != "native") {
					$col = inlineCol::solid("amber");
					$str .= " (<strong>$za[1]</strong> brand)";
				}
				
			}
			else
				$str = "sparse zone";

		}
		else
			$str = $vz;

		// Strip off the "global zone" string, if it's still there, which it
		// will be in VBoxes and LDOMs. We've done the blue highlight, so we
		// don't need this.

		$str = preg_replace("/ \(global zone\)/", "", $str);
		
		return new Cell($str, $class, $col);
	}

	protected function show_OBP($data, $extra)
	{
		// Colour latest green, others red, but only if $this->latest_obps
		// is set

		if (!isset($this->latest_obps))
			return $this->show_generic($data);

		$hw = $extra["hardware"][0];

		if (in_array($hw, array_keys($this->latest_obps))) {
			$lobp = $this->latest_obps[$hw];

			$class = ($data[0] == $lobp)
				? "sw_latest"
				: "sw_old";
		}
		else
			$class = false;

		return new Cell($data[0], $class);
	}

	protected function show_ALOM_F_W($data, $extra)
	{
		// Colour latest green, others red

		if (!isset($this->latest_aloms))
			return new Cell($data[0]);

		$hw = $extra["hardware"][0];

		if (in_array($hw, array_keys($this->latest_aloms))) {
			$lalom = $this->latest_aloms[$hw];

			$class = ($data[0] == $lalom)
				? "sw_latest"
				: "sw_old";
		}
		else
			$class = false;

		return new Cell($data[0], $class);
	}

	protected function show_ALOM_IP($data, $guess = false)
	{
		// Print the ALOM IP address, on an appropriately coloured
		// background.

		// You can override ALOM colours by having an "alom" element in the
		// colours::$nic_cols array 

		// If "guess" is not false, we use a box to denote a "guessed" IP
		// address.

		if (is_array($data))  {

			$colfn = (is_array($guess))
				? "solid"
				: "box";

			$sn = (isset(colours::$nic_cols["alom"]))
				? "alom"
				: PlatformGrid::get_subnet($ip[0]);

			$col = colours::$nic_cols[$sn];

			$c = new Cell($data[0], false, inlineCol::$colfn($col));
		}
		else
			$c = new Cell();

		return $c;
	}

	protected function show_CPU($data) {
		
		// Break up the CPU string

		$arr = explode(" ", $data[0]);

		// multi-core chips have 6 elements, single core have 3

		if (sizeof($arr) == 6) {
			$cores = "$arr[2] cores @<br/>";
			$speed = $arr[5];
		}
		else {
			$cores = "";
			$speed = $arr[2];
		}

		$physical = $arr[0];

		if ($physical == 1)
			$physical = "";
		else
			$physical = "$physical x ";

		$speed = preg_replace("/M.*$/", "", $speed);

		$speed = ($speed >= 1000) ? round($speed / 1000, 1) . "GHz" :
		"${speed}MHz";

		return new Cell("$physical $cores $speed");
	}

	protected function show_storage($data)
	{
		// Colour storage using the dynamic style sheet

		$c_arr = array();

		foreach($data as $datum) {

			$ic = false;
			$type = preg_replace("/: .*$/", "", $datum);

			switch($type) {

				// Disk drives. Put the size into Mb/Gb/Tb etc if we've been
				// given a number

				case "disk":

					$class = "smalldisk";

					if (!preg_match("/unknown/", $datum)) {
						$parts = explode(" ", $datum, 5);
						$inb = units::to_b($parts[3]);
						$datum = "${type}: $parts[1] x ".
						units::from_b($inb) . " " . $parts[4];
					}

					break;

				case  "CD/DVD":

					// CD/DVD has a coloured border indicating its state

					$class = "smallcd";

					if (preg_match("/\(loaded\)/", $datum))
						$ic = inlineCol::box("amber");
					elseif (preg_match("/\(mounted\)/", $datum))
						$ic = inlineCol::box("green");
					break;
				
				case "tape":
					$class = "smalltp";
					break;

				case "FC array":
					$class = "smallfc";
					break;

				default:
					$class = false;

			}

			$c_arr[] = new Cell(preg_replace("/^(.*):/",
			"<strong>\\1</strong>:", $datum), $class, $ic);
		}

		return $this->show_parsed_list($c_arr, 1);
	}

	protected function show_pci_card($data)
	{
		$c_arr = array();

		foreach (preg_replace("/^(\S+) /", "<strong>\\1</strong> ", $data)
		as $datum) {
			$c_arr[] = new Cell($datum);
		}
		
		return $this->show_parsed_list($c_arr, 1);
	}

	protected function show_sbus_card($data) {
		return $this->show_pci_card($data);
	}

	protected function show_mac($data)
	{
		$c_arr = array();

		foreach($data as $datum) {
			$a = explode(" ", $datum);
			$c_arr[] = new Cell("<strong>$a[0]</strong>", false, false,
			"40%") . new Cell($a[1], "mac_addr");
		}

		return $this->show_parsed_list($c_arr, 1);
	}

	public function show_NIC($nic_arr)
	{
		// $data is an array of NIC lines in parseable format. Each element
		// is a NIC.

		// The NIC info is presented in machine parseable form as a "|"
		// delemited string which, when exploded, gives the following
		// elements
		//
		//   [0] - device name 
		//   [1] - IP address / uncabled / unconfigured in global /
		//         vswitch on.. / exclusive IP on... / vlan
		//   [2] - hostname / zonename
		//   [3] - speed-duplex / speed:duplex
		//   [4] - IPMP group / DHCP
		//   [5] - +vsw / VLAN
		//
		// These line-up with the Sn variables in the auditor script

		// We create a table two cells for each NIC. The LHS has the device
		// name, and is solid-coloured according to the $subnet array. It
		// may also contain DHCP, IPMP, and link speed information. The RHS
		// may be frame-coloured, and contains the IP address, host/zone
		// name, IPMP group, and other information

		//  LHS        RHS
		// $na[0]     $na[1]
		// $na[3]     $na[2]
		// $na[4]     $na[5]
		//            $na[4]

		// The IPMP group goes RHS, but IPMP is also flagged LHS

		if (!is_array($nic_arr))
			return new Cell("no information");

		// Open the table

		$c_arr = array();

		foreach($nic_arr as $nic) {
			$na = explode("|", $nic);
			unset($speed);

			// Basic info is NIC name on left, IP address on right

			$lhs = ($na[1] == "uncabled" || preg_match("/:/", $na[0]))
				? $na[0]
				: "<strong>" . $na[0] . "</strong>";

			$rhs = $na[1];

			// Look for extra info to add to each side. RHS has host/zone
			// name
			
			if ($na[2]) $rhs .= " ($na[2])";

			// LHS can have speed
			
			if ($na[3]) {

				// in LDOMs speed is reported as "unknown"

				if ($na[3] == "unknown")
					$speed = "unknown speed";
				else {
			
					// Split the speed/duplex into two parts

					$sa = preg_split("/:|-/", $na[3]);
	
					// Now $sa[0] is the speed, $sa[1] is the duplex. I
					// don't want the "b" on the speed.
	
					$sa[0] = str_replace("b", "", $sa[0]);
	
					// Make the speed "1G" if it's 1000M. Also look out for
					// long strings from kstat.
	
					if ($sa[0] == "1000M" || $sa[0] == "1000000000")
						$sa[0] = "1G";
					elseif ($sa[0] == "100000000")
						$sa[0] = "100M";
					elseif ($sa[0] == "10000000")
						$sa[0] = "10M";
	
					// Make the duplex part "full" if it's only "f", and
					// "half" if it's only "h"
		
					if (sizeof($sa) > 1) {

						if ($sa[1] == "f")
							$sa[1] = "full";
						elseif ($sa[1] == "h")
							$sa[1] = "half";

						$speed = "${sa[0]}/$sa[1]";
					}

					if (isset($sf[1]))
						$speed .= " $sf[1]";
				}
				
				if (isset($speed))
					$lhs .= " <div>$speed</div>";
			}

			// na[4] can be DHCP or IPMP info. IPMP info goes on both sides

			if ($na[4]) {

				if ($na[4] == "DHCP")
					$lhs .= " <div>DHCP</div>";
				else {
					$lhs .= " <div>IPMP</div>";
					$rhs .= "<div>IPMP=${na[4]}</div>";
				}

			}

			// LHS can also have +vswitch or VLAN info

			if ($na[5]) $lhs .= "<div>" .strtoupper($na[5]) . "</div>";

			// That's all the info that goes in the cells. Now we need to
			// work out how to colour them

			$subnet = false;

			// If there's an IP address, try to get it's subnet.

			if (preg_match("/^\d/", $na[1]))
				$subnet = PlatformGrid::get_subnet($na[1]); 

			// If na[1] is "unconfigured", we need to find out what virtual
			// interfaces said NIC has by looping through all the NICs we
			// were given until we hit a virtual interface belonging to
			// $na[0]. VLANned interfaces show up as "unconfigured", but do
			// have a speed.
			
			elseif (preg_match("/unconfigured/", $na[1])) {

				foreach ($nic_arr as $tnic) {
					$tna = explode("|", $tnic);

					if (preg_match("/$na[0]:\d+$/",$tna[0])) {
						$subnet = PlatformGrid::get_subnet($tna[1]);
						break;
					}

				}

				// If the above failed, we're either a VLAN or a vswitch

				if (!$subnet) {

					if ($na[5] == "+vsw")
						$subnet = "vswitch";
					elseif ($na[3])
						$subnet = "vlan";

				}

			}
			elseif (preg_match("/vswitch/", $na[1]))
				$subnet = "vswitch";
			elseif (preg_match("/vlanonly/", $na[1]))
				$subnet = "vlanonly";
			elseif (preg_match("/exclusive/", $na[1])) {

				// Colour exclusive IP instances by getting the name of the
				// zone which holds the IP instance, and getting its primary
				// NIC's subnet. 

				$zarr = GetServers::get_zone($this->map->get_base($na[2]),
				"platform");

				$snn = explode("|", $zarr["NIC"][0]);
				$subnet = PlatformGrid::get_subnet($snn[1]);
			}

			// We know the subnet, so we can get the colour for the cells

			$col = (isset(colours::$nic_cols[$subnet])) 
				?  colours::$nic_cols[$subnet]
				: false;

			// Solid background colour on the physically cabled, non-VLAN
			// NICs, and outline the address box in the appropriate subnet
			// colour

			$lcol = (!preg_match("/:/", $na[0]) && $col && $na[5] != "vlan")
				? inlineCol::solid($col)
				: false;

			$rcol = ($col) 
				? inlineCol::box($col)
				: false;

			// Now we can make the cell

			$c_arr[] =  new Cell($lhs, false, $lcol, "30%") . new Cell($rhs,
			false, $rcol) ;
		}

		return $this->show_parsed_list($c_arr, 3);
	}

	//-- o/s -----------------------------------------------------------------

	protected function show_version($data, $extra)
	{
		// In a local zone, if the version is not the same as the parent
		// zone, box it in amber

		$zn = $extra["hostname"][0];
		$class = false;

		if (!$this->map->is_global($zn)) {

			if ($this->map->get_parent_prop($zn, "version", "os")  !=
			preg_replace("/ zone$/", "", $data[0]))
				$class = "boxamber";
		}

		return new Cell($data[0], $class);
	}

	protected function show_release($data, $extra)
	{
		// Show the operating system version and revision. For normal
		// Solaris we get this in a "5.10 10/09" style, which doesn't mean a
		// lot to some people, so here we convert it into more sensible
		// marketing type strings. We also flag up zones with different
		// releases to their parents

		// An array pairing update numbers with the months they were
		// released. You have to update this by hand as new versions of
		// Solaris come out

		$updates = array(
			"5.8" => array(
				"6/00" => "update 1",
				"10/00" => "update 2",
				"1/01" => "update 3",
				"4/01" => "update 4",
				"7/01" => "update 5",
				"10/01" => "update 6",
				"2/02" => "update 7",
				"12/02" => "HW1",
				"5/03" => "HW2",
				"7/03" => "HW3",
				"2/04" => "HW4"
				),

			"5.9" => array(
				"9/02" => "update 1",
				"12/02" => "update 2",
				"4/03" => "update 3",
				"8/03" => "update 4",
				"12/03" => "update 5",
				"4/04" => "update 6",
				"9/04" => "update 7",
				"9/05" => "update 8",
				"9/05 HW Update" => "u9/HW update"
				),

			"5.10" => array(
				"03/05" => "GA",
				"01/06" => "update 1",
				"06/06" => "update 2",
				"11/06" => "update 3",
				"8/07" => "update 4",
				"5/08" => "update 5",
				"10/08" => "update 6",
				"5/09" => "update 7",
				"10/09" => "update 8",
				"9/10" => "update 9"
				)
			);

		$zn = $extra["hostname"][0];
		$class = false;
		$os_hr = $data[0];

		// If we've got something in the array above, translate it. We need
		// the Solaris revision first.
	
		preg_match("/^.*SunOS ([\d.]+).*$/", $this->map->get_zone_prop($zn,
		"version", "os"), $vi);

		if (isset($vi[1])) {
			$sv = $vi[1];

			if (in_array($sv, array_keys($updates))) {
				
				if (in_array($os_hr, array_keys($updates[$sv])))
					$os_hr .= "<div>(" . $updates[$sv][$os_hr] . ")</div>";
			}

		}

		// If we're a zone, check to see if we have the same O/S as the
		// parent

		if (!$this->map->is_global($zn)) {

			if ($this->map->get_parent_prop($zn, "release", "os")  !=
			preg_replace("/ zone$/", "", $data[0]))
				$class = "boxamber";
		}

		return new Cell($os_hr, $class);
	}

	private function uptime_in_m($up)
	{
		// We may get uptimes reported as min(s) or h:mm, or as "n days".
		// However it comes, convert it to minutes and return.
		// $1 is the uptime string, unprocessed

		if (preg_match("/day/", $up)) {
			$up = (preg_replace("/ day.*$/", "", $up) * 1440);
		}
		elseif (preg_match("/min/", $up))
			$up = preg_replace("/ min.*$/", "", $up);
		elseif (preg_match("/:/", $up)) {
			$hm = split(":", $up);
			$up = (60 * $hm[0]) + $hm[1];
		}

		return round($up);
	}

	protected function show_uptime($data, $extra = false)
	{

		$up = $this->uptime_in_m($data[0]);
		$class = false;

		// If this is a global zone, and extra is set, get the parent's
		// uptime also. ($extra won't be set when this is called from the
		// compre grid)

		if ($extra) {
			$zn = $extra["hostname"][0];

			if (!$this->map->is_global($zn))
				$pu = $this->uptime_in_m($this->map->get_parent_prop($zn,
				"uptime", "os"));

			// Flag the box amber if uptime is less than a day. Put an amber
			// border round the cell if this is a zone and it's been
			// rebooted more recently than the global. Reboot gets priority

			if (isset($pu) && $up < $pu)
				$class = "boxamber";
			elseif ($up < 1440)
				$class = "solidamber";
		}

		// Make the numbers a bit more human-readable

		if ($up > 1440)
			$up = round($up / 1440, 1) . " days";
		elseif ($up > 60) {
			$m = $up % 60;
			$h = ($up - $m) / 60;
			$up = "${h}h ${m}m";
		}
		elseif ($up > 1)
			$up = "$up mins";
		else
			$up = "$up min";

		return new Cell($up, $class);
	}

	protected function show_kernel($data, $extra)
	{
		// We used to only print the kernel in global zones, because local
		// zones would always have the same kernel as the the global. But
		// with the SUNWsolaris10 brand, that's no longer the case. We also
		// now colour the kernel version squares when we do O/S audits

		$zn = $extra["hostname"][0];
		$kr = $data[0];

		if (!isset($this->latest_kerns))
			return $this->show_generic($kr);

		$osver = $this->mk_ver_arch_str($zn, $extra["distribution"][0],
		$extra["version"][0]);

		// Don't colour "virtual" kernels at all

		if ($kr == "Virtual")
			$class = false;
		elseif (in_array($osver, array_keys($this->latest_kerns)))

			$class = ($kr == $this->latest_kerns[$osver])
				? "sw_latest"
				: "sw_old";
		else
			$class = "solidamber";

		// Now look to see if the kernel is the same as the parent

		$col = ($this->map->is_global($zn) || $data[0] ==
		$this->map->get_parent_prop($zn, "kernel", "os"))
			? false
			: inlineCol::box("amber");

		return new Cell($data[0], $class, $col);
	}
	
	protected function show_local_zone($data) 
	{
		// Show local zone information in a table
		// green highlighting on the zone name means "running"
		// yellow highlighting on the zone name means "installed"
		// red highlighting on the zone name means "other" and the status is
		// displayed

		$ret = multiCellSmall::open_table();

		foreach($data as $row) {
			$col = false;
			$rarr = preg_split("/[ :]/", preg_replace("/[\(\[\]\)]/", "",
			$row));

			// Get the colour to background the zone name

			if ($rarr[2] == "running")
				$class = "solidgreen";
			elseif ($rarr[2] == "installed")
				$class = "solidamber";
			else {
				$class = "solidred";
				$rarr[0] = "$rarr[0] ($rarr[2])";
			}

			// do we print the zone type?

			if ($rarr[1] == "native")
				$z_type = false;
			else {
				$z_type = "<div>[$rarr[1] brand]</div>";
				$col = inlineCol::box("amber");
			}

			$ret .= "\n<tr class=\"multicellsmall\">" . new Cell("<strong>"
			.  $rarr[0] . "</strong> ($rarr[3])$z_type", $class, $col)  .
			"</tr>";
		}

		return new Cell($ret . "</table>");
	}

	protected function show_packages($data)
	{
		// Print the number of packages installed in the zone, and highlight
		// in amber if any of them are only partially installed

		$class = preg_match("/partial/", $data[0])
			? "solidamber"
			: false;

		return new Cell($data[0], $class);
	}

	protected function show_patches($data, $extra)
	{
		// If this is a local zone, get the number of patches in the global
		// zone and compare. 
		
		$class = false;

		if (!$this->map->is_global($extra["hostname"][0])) {
			$parent = $this->map->get_parent_zone($extra["hostname"][0]);
			
			if ($this->map->has_data($parent)) {
				$pz = $this->servers[$parent];

				if ($data[0] < $pz["patches"][0])
					$class = "solidamber";

			}
		}

		return new Cell($data[0], $class);
	}

	protected function show_ldom($data) 
	{
		// Show Logical Domain information in a table
		// green highlighting on the domain name means "active"
		// yellow highlighting on the domain name means "bound"
		// red highlighting on the domain name means "other" and the status is
		// displayed

		// cs-dev-02-lws01 (active) [port 5000]

		$ret = multiCellSmall::open_table();

		foreach($data as $row) {
			$port_part = preg_replace("/^.* \[/", "[", $row);
			$rarr = explode(" ", preg_replace("/[\(\[\]\)]/", "", $row));

			// Get the colour to background the zone name

			if ($rarr[1] == "active")
				$class = "solidgreen";
			elseif($rarr[1] == "bound")
				$class = "solidamber";
			else {
				$class = "solidred";
				$rarr[0] = "$rarr[0] ($rarr[1])";
			}

			$ret .= "\n<tr class=\"multicellsmall\">" . new Cell("<strong>"
			. $rarr[0] . "</strong> <div>$port_part</div>", $class) . "</tr>";
		}

		return new Cell($ret . "</table>");
	}
	//-- tools and applications ----------------------------------------------

	protected function show_sun_cc($data)
	{
		// Parse a list of Sun CC versions, make them more human-readable,
		// and colour them. Display is done through the show_parsed_list()
		// function

		// Make Sun CC versions more understandable

		$sun_cc_vers = array(
			"5.0" => "5.0",
			"5.8" => "11",
			"5.9" => "12",
			"5.10" => "12u1",
			"5.11" => "12.2"
			);

		$c_arr = array();

		foreach ($data as $datum) {

			$sccarr = $new_data = array();
			preg_match("/(^.*)@=(.*$)/", $datum, $sccarr);

			$sccver = preg_replace("/ .*$/", "", $sccarr[1]);

			if (isset($this->latest["Sun CC"])) {

				$bg_class =  ($sccver == $this->latest["Sun CC"])
					? "sw_latest"
					: "sw_old";

			}
			else
				$bg_class = false;

			$new_data = (in_array($sccver, array_keys($sun_cc_vers)))
				? preg_replace("/^${sccver}/", "<b>$sun_cc_vers[$sccver]</b>",
					$sccarr[1])
				: $sccarr[1];

			$c_arr[] = new Cell($new_data, $bg_class, false, false, false,
			$sccarr[2]);
		}

		return $this->show_parsed_list($c_arr);
	}

	protected function show_apache_so($data, $extra)
	{
		// Print a list of Apache shared modules. The module lists contain
		// the version number of the Apache to which they belong. If there's
		// only one Apache on this box, strip that extraneous information
		// out. SSL modules are highlighted in yellow, just to make it
		// easier to see an SSL enabled Apache.

		$strip_ver = (sizeof($extra["Apache"]) == 1)
			? true
			: false;
	
		$c_arr = array();

		foreach($data as $mod) {

			// If the parent apache is of an unknown version, the auditor
			// just puts () after the module. Change that to (unknown)

			$mod = preg_replace("/\(\)/", "(unknown)", $mod);
				
			if ($strip_ver)
				$mod = preg_replace("/ .*$/", "", $mod);

			$col = (preg_match("/(mod_ssl.so|ssl_module)/", $mod))
				? inlineCol::solid("yellow")
				: false;

			$c_arr[] = new Cell(preg_replace("/\.so/", "", $mod), false,
			$col);
		}

		return $this->show_parsed_list($c_arr, 1);
	}

	protected function show_mod_php($data, $extra)
	{
		// Parse PHP modules. Display is done through show_parsed_list()

		$c_arr = array();

		foreach($data as $datum)
		{
			$datum = str_replace("()","(unknown)", $datum);

			$str = (sizeof($extra["Apache"]) == 1)
				? preg_replace("/\(apache.*$/", "(apache)", $datum)
				: preg_replace("/module\) \(/", "", $datum);

			$c_arr[] = $this->show_generic($str, "mod_php");
		}

		return $this->show_parsed_list($c_arr);
	}

	protected function show_sshd($data)
	{
		// Display SSH info in a nicer way than the auditor normally
		// delivers. Works for OpenSSH and Sun SSH. Haven't run into any
		// others yet.

		$ret = false;

		foreach($data as $datum) {

			$sshver = preg_replace("/^.*_/", "", $datum);

			if (preg_match("/OpenSSH/", $datum))
				$call = $sshvend = "OpenSSH";
			elseif (preg_match("/Sun_SSH/", $datum)) {
				$sshvend = "Sun";
				$call = "Sun_SSH";
			}
			else
				$sshvend = preg_replace("/_[\d].*$/", "", $datum);

			$newdata[] = "$sshvend $sshver";

			$ret .= $this->show_generic($newdata, "sshd", $call);

		}

		return $ret;
	}

	protected function show_x_server($data)
	{
		// Print X server info. XSun can't report a version, so don't colour
		// those in.

		$call = preg_replace("/[- ].*$/", "", $data[0]);

		if ($call == "Xsun")
			$call = "NOBG";

		return $this->show_generic($data, "X server", $call);
	}

	//-- filesystem ----------------------------------------------------------

	protected function show_zpool($data)
	{
		// List Zpools and their sizes. Ones which can be upgraded are on an
		// orange field

		$c_arr = false;

		foreach($data as $row) {
			$rarr = preg_split("/[ :]/", $row);

			// We deal with the version part separately

			$varr = explode("/", preg_replace("/[\[\]]/", "", $rarr[2]));

			if ($varr[0] != $varr[1]) {
				$vex = "v$varr[0] (v$varr[1] available)";
				$class = "solidorange";
			}
			else {
				$vex =" v$varr[0]";
				$class = false;
			}

			$c_arr[] = new Cell("<strong>$rarr[0]</strong> $rarr[1]"
			. "<div>$vex</div>", $class);
		}

		return $this->show_parsed_list($c_arr);
	}

	protected function show_capacity($data)
	{
		// Colour the box amber if it's more than 85% full

		$a = preg_split("/[\s%\[\]()]+/", $data[0]);
		
		// This produces an array of the form
		// [0] => 35.6Gb		- capacity
		// [1] => 23.8Gb		- used
		// [2] => used
		// [3] => 66.00	 		- % used

		$cap_b = units::to_b($a[0]);
		$use_b = units::to_b($a[1]);

		$txt = "<div><strong>" . units::from_b($cap_b) .
		"</strong></div>\n<div>" . units::from_b($use_b) . " (" .
		round($a[3]) . "%) used</div>";

		$class = ($a[3] > 85)
			? "solidamber"
			: false;

		return new Cell($txt, $class);
	}

	protected function show_root_fs($data)
	{
		// Colour code the root FS coloumn, the same as the fs column. You
		// need a td.box$fstyp in the stylesheet for each filesystem

		$fstyp = (preg_replace("/ .*$/", "", $data[0]));

		return new Cell($data[0], "box$fstyp");
	}

	protected function show_fs($data)
	{
		// Put the mountpoint in bold, followed by the FS type in brackets.
		// On a second line put extra info. Device, NFS path, ZFS dataset
		// etc.
		// Orange fields are used for upgradeable ZFS filesystems, red for NFS
		// filesystems not in the vfstab.
		// All on a field given by the "known" array at the top of the class
		// input is of the form
		//  /  (ufs:opt:/dev/md/dsk/d20)
		//  /zonedata/cs-db-01z-mysql41/data (zfs:opt:space/cs-db-01z-mysql41 \
		//     /data:3/4:comp)
		// /home/robertf (nfs:opt:cs-fs-01:/export/home/robertf)

		$c_arr = array();

		foreach($data as $row) {

			// Break up the string. rarr[0] is the mountpoint, earr[] is the
			// "extra" data. fstype:opt:more

			$exstyle = false;
			$rarr = explode(" ", $row);
			$earr = explode(":", preg_replace("/[\(\)]/", "", $rarr[1]));
			$fstyp = $earr[0];
			$out = "<strong>" . $this->fold_line(htmlentities($rarr[0]), 50)
			. "</strong> ($earr[0])";
			$row2 = $earr[2];

			// We report the versions of ZFS filesystems, and inline colour
			// the cell with an orange field if it can be upgraded

			if ($fstyp == "zfs") {
				// earr[1] is the fs options
				// earr[2] is the dataset
				// earr[3] is version/available_version
				// earr[4] is "comp" if the fs is compressed

				// Look at the version part

				if (isset($earr[3])) {
					$varr = explode("/", $earr[3]);
					$row2 .= ", v$varr[0]";

					if ($varr[0] != $varr[1]) {
						$exstyle = inlineCol::solid("orange");
						$row2 .= " <strong>upgradeable to $varr[1]</strong>";
					}

				}

				if (isset($earr[4]) && $earr[4] == "comp")
					$row2 .= ", compressed";

			}
			elseif ($fstyp == "nfs") {
				
				// NFS need the export path tagging on, and will put on a
				// red field if the FS isn't in the vfstab.

				$row2 .= ":" . $earr[3];

				if (!isset($earr[4]) || $earr[4] != "in_vfstab") {
					$row2 .= " (not in vfstab)";
					$exstyle = inlineCol::solid("red");
				}

			}

			// Certain filesystems are of no interest to us

			elseif ($fstyp == "lofs" &&
			preg_match(":^/platform/|^/dev/|^/dev$|^/platform$|^/\.S:",
			$rarr[0]))
				continue;

			// Don't display the device if it's the same as the mountpoint,
			// as it is with zone roots

			elseif ($earr[2] == $rarr[0])
				unset($row2);

			if ($earr[2] == "ro") {
				$out .= " [read only]";
				$exstyle = inlineCol::solid("grey");
			}

			if (isset($row2))
				$out .= "\n<div class=\"indent\">$row2</div>";

			$c_arr[] = new Cell($out, "smallbox$fstyp", $exstyle);
		}

		return $this->show_parsed_list($c_arr);
	}

	protected function show_export($data, $extra)
	{
		// Nicely present exported filesystems. At the moment they can be
		// NFS, SMB, or iSCSI (yes, I know that's not strictly an exported
		// filesystem...) Colouring is done from the dynamic stylesheet.
		// Input is of the form:
		//
		// nfs:/js/export:anon=0,sec=sys,ro
		// iscsi:space/target
		// smb:/export/software:software

		$c_arr = false;

		foreach($data as $datum) {
			$earr = preg_split("/:/", $datum, 3);
			$fstyp = $earr[0];
			$mntinfo = "";
			$col = false;

			$str = "<strong>$earr[1]</strong> ($fstyp)";

			if ($fstyp == "nfs") {
				// For NFS, we strip off the domain name, if it's defined in
				// STRIP_DOMAIN, and fold. 

				if (STRIP_DOMAIN)
					$earr[2] = $this->fold_line(str_replace("." .
					STRIP_DOMAIN, "", $earr[2]), 40);

				// Now we look to see if anything else has mounted this
				// filesystem. show_fs() will have taken a note if it's seen
				// it anywhere. If it doesn't look like that has happened,
				// skip this step

				if (isset($this->mntd_nfs) && sizeof($this->mntd_nfs) > 0) {
					$key = $extra["hostname"][0] . ":" . $earr[1];
	
					$mnts = (in_array($key, array_keys($this->mntd_nfs)))
						? $this->mntd_nfs[$key]
						: 0;

					if ($mnts == 0) {
						$mntinfo = " (0 known mounts)";
						$col = inlineCol::solid("amber");
					}
					elseif ($mnts == 1)
						$mntinfo = " (1 known mount)";
					else
						$mntinfo = " ($mnts known mounts)";
				}

				$str .= "$mntinfo<div class=\"indent\">$earr[2]</div>";
			}
			elseif ($fstyp == "smb")
				$str .= "<div class=\"indent\">&quot;$earr[2]&quot;</div>";

			$c_arr[] = new Cell($str, "smallbox$fstyp", $col);
		}

		return $this->show_parsed_list($c_arr);
	}

	//-- hosted services -----------------------------------------------------

	protected function show_website($data, $extra)
	{
		// Show websites. Colour coded on the server which provides them.
		// Each element of $data is of the form
		//  server site config_path doc_root
		// Site names are in bold:
		//   Unresolved sites are boxed in red
		//   Resolved sites are boxed in green

		// Try to pull together sites which have the same config file and
		// doc root. i.e. Server aliases.

		// Create a data structure like this:
		//
		//   sites => config_file => dr 
		//                        => sn[]
		//	                      => server_type

		// sort the array. Naturally this will sort on server type first,
		// hostname second, which is good

		sort($data);
		$c_arr = $sites = array();

		foreach($data as $datum) {
			$a = explode(" ", $datum);

			$ws = $a[0];	// web server type (e.g. apache)
			$uri = $a[1];	// URI (e.g. www.snltd.co.uk)
			$cf = $a[2];	// path to config file 
			$dr = (isset($a[3]))
				? $a[3]
				: "UNDEFINED";
			
							// path to document root

			if (!isset($sites[$cf]))

				$sites[$cf] = array(
					"dr" => $dr,
					"ws" => $ws,
					"uri" => array($uri)
				);

			else
				$sites[$cf]["uri"][] = $uri;

		}

		foreach($sites as $cf => $s) {

			// Bit of shorthand. Makes things easier to follow.

			$dr = $s["dr"];
			$ws = $s["ws"];
			$uri = $s["uri"];
			$row1 = "";
			$i = 0;

			// We can have multiple URIs. They're server aliases

			foreach($uri as $u) {
				$row1 .= "\n<div>";

				// Make the URI link coloured if it looks like we have a
				// parsed IP list

				if (isset($this->ip_list) && sizeof($this->ip_list) > 0) {

					$lc = (in_array($u, array_keys($this->ip_list)))
						? "strongg"
						: "strongr";

				}
				else
					$lc = "strong";

				$row1 .= "<a class=\"$lc\" href=\"http://${u}\">${u}</a>";

				if ($i++ == 0)
					$row1 .= " ($ws)";

				$row1 .= "</div>";
			}

			// On the next line down, we print the document root. This is
			// outlined in red if it's an NFS mount, and amber if there are
			// NFS mounts somewhere under it. We only do this if there's a
			// populated NFS directory array

			$row2 = $row3 = "<div class=\"indent\"";
			$hn = $extra["hostname"][0];
			
			if (isset($this->nfs_dirs) && sizeof($this->nfs_dirs) > 0 &&
				in_array($hn, array_keys($this->nfs_dirs))) {

				foreach($this->nfs_dirs[$hn] as $nd) {
					unset($col);

					// First look for directory roots inside NFS mounts,
					// then for directory roots with NFS mounts somewhere
					// underneath them

					if ($dr == "UNDEFINED" || preg_match("|^$nd.*|", $dr))
						$col = "red";
					elseif (preg_match("|^$dr.*|", $nd))
						$col = "amber";

					if (isset($col)) {
						$row2 .= " style=\"" . inlineCol::solid($col) . "\"";
						break;
					}

				}

			}

			$row2 .= ">doc_root: $dr</div>";

			// Finally we do the config file. We highlight this if its name
			// doesn't end .conf. This might not be suitable for all sites.

			// For iPlanet this field is the instance

			if ($ws == "iPlanet")
				$row3 .= ">instance: $cf</div>";
			else {

				if (!preg_match("|\.conf$|", $cf))
					$row3 .= " style=\"" . inlineCol::solid("amber") .
					"\"";

				$row3 .= ">config: $cf</div>";
			}

			$c_arr[] = new Cell($row1 . $row2 . $row3, "small$ws");
		}

		return $this->show_parsed_list($c_arr, 1);
	}

	protected function show_database($data)
	{
		// Present databases. Input is of the form
		//
		//  db_server:db_name:size:extra
		//
		// For MySQL, extra is the time of last db update, if > 30 days. We
		// colour each cell according to the type of DB server, and put it
		// on a yellow field if it's not been updated in the last 30 days.

		$c_arr = array();

		foreach($data as $datum) {
			$arr = explode(":", $datum);
			$str = "<strong>$arr[1]</strong> ($arr[0]) $arr[2]b";
			$col = false;

			if ($arr[3]) {
				$str .= "<div class=\"indent\">last updated: 
				$arr[3]</div>";
				$col = inlineCol::solid("amber");
			}
				
			$c_arr[] = new Cell($str, "small$arr[0]", $col);
		}

		return $this->show_parsed_list($c_arr, 1);
	}

	//-- security ------------------------------------------------------------

	protected function show_user($data)
	{
		// Work on user data. If any user name comes up twice, but with a
		// different UID the second time, flag it red

		// Discard username/uid pairs in the omit_users array

		$c_arr = array();

		$arr = (isset($this->omit->omit_users))
			? array_diff($data, $this->omit->omit_users)
			: array();

		foreach($arr as $e) {
			preg_match("/^(\S+) \((\d+)\)$/", $e, $a);
			$un = $a[1];
			$ui = $a[2];
			$lclass = $rclass = false;

			// Is the username already known? If so, does it have the same
			// UID it did before? If it's not, add it to the known users
			// list

			if (in_array($un, array_keys($this->known_users))) {

				if (!in_array($ui, $this->known_users[$un])) {
					$lclass = "solidred";
					$this->known_users[$un][] = $ui;
				}

			}
			else
				$this->known_users[$un][] = $ui;

			if (in_array($ui, array_keys($this->known_uids))) {

				if (!in_array($un, $this->known_uids[$ui])) {
					$rclass = "boxred";
					$this->known_uids[$ui][] = $un;
				}

			}
			else
				$this->known_uids[$ui][] = $un;
		
			$c_arr[] = new Cell($un, $lclass) . new Cell($ui, $rclass);

		}

		return $this->show_parsed_list($c_arr);
	}

	protected function show_authorized_key($data)
	{
		// Display authorized key data. Not much processing to do here. Root
		// keys are highlighted in amber, everything else just goes in a
		// list with the username in bold.

		$c_arr = array();

		foreach($data as $row) {
			$a = explode(" ", $row);
			$user = preg_replace("/[\(\)]/", "", $a[1]);
			
			$class = ($user == "root")
				? "solidamber"
				: false;

			$c_arr[] = new Cell("<strong>$user</strong>:</div>$a[0]",
			$class);
		}

		return $this->show_parsed_list($c_arr, true);
	}

	protected function show_ssh_root($data)
	{
		// Highlight the box in red if root can SSH in

		$class = ($data[0] == "yes")
			? "solidred"
			: false;

		return new Cell($data[0], $class);
	}
	
	protected function show_user_attr($data)
	{
		// List user_attr info. Bit of bolding and folding, that's all.

		// We're not interested in the ones in omit_attrs

		if (isset($this->omit))
			$data = array_diff($data, $this->omit->omit_attrs);

		$c_arr = array();

		foreach($data as $attr)
			$c_arr[] =  new Cell(preg_replace("/^([^:]*)/",
			"<strong>\\1</strong>",
			$this->fold_line(htmlentities($attr),
			30)), "lalign");

		return $this->show_parsed_list($c_arr, 1);
	}

	protected function show_port($data)
	{
		// List open ports. Non-"expected" ports are on an amber field.
		// Inetd ports are boxed in red.

		$call = array();

		foreach($data as $datum) {
			$arr = explode(":", $datum);
			
			$class = (isset($this->omit) && in_array($arr[0],
			$this->omit->usual_ports))
				? false
				: "solidamber";

			$col = ($arr[2] == "inetd")
				? inlineCol::box("red")
				: false;

			$extra = ($arr[1] != "")
				? "<strong>$arr[1]</strong>"
				: false;

			$extra .= ($arr[2] != "")
				? " ($arr[2])"
				: false;

			$c_arr[] = new Cell($arr[0], $class, $col) . new Cell($extra,
			$class, $col);
		}

		return $this->show_parsed_list($c_arr, 1);
	}

	protected function show_cron_job($data)
	{
		// List cron jobs. Pretty much a copy of show_user_attr()

		if (isset($this->omit))
			$data = array_diff($data, $this->omit->omit_crons);

		$c_arr = array();

		foreach($data as $attr)
			$c_arr[] =  new Cell(preg_replace("/^([^:]*):/",
			"<strong>\\1</strong> ", $this->fold_line(htmlentities($attr),
			30)), "lalign");

		return $this->show_parsed_list($c_arr, 1);
	}

	protected function show_empty_password($data)
	{
		// Highlight all these in amber, except root, which is RED

		$c_arr = array();

		foreach($data as $datum) {
			
			if ($datum == "root")
				$class = "solidred";
			else
				$class = "solidamber";

			$c_arr[] = new Cell($datum, $class);
		}

		return $this->show_parsed_list($c_arr);
	}

	protected function show_dtlogin($data)
	{
		// Print info on desktop login daemons. We don't do any version
		// colouring, as most of them can't report their version. We
		// highlight running daemons in solid amber, because not many people
		// want dtlogin type things running

		$c_arr = array();

		foreach($data as $datum) {
			$ta = preg_split("/@=/", $datum);

			$path = (isset($ta[1]))
				? $ta[1]
				: false;

			$class =(preg_match("/not running/", $datum))
				? "boxred"
				: "solidamber";

			$c_arr[] = new Cell($ta[0], $class, false, false, false, $path);


		}

		return $this->show_parsed_list($c_arr);
	}
	//------------------------------------------------------------------------
	// other display functions

	public function server_count()
	{
		// Prints the "auditing x physical servers message at the top of the
		// page

		$gz = sizeof($this->map->list_globals());
		$ld = sizeof($this->map->list_ldoms());
		$lz = sizeof($this->map->list_locals());
		$vb = sizeof($this->map->list_vbox());
		$others = ($this->map->count - PER_PAGE);
		$parts = 0;

		// Logical domains, virtualboxes and physical servers all have
		// global zones, so work out how many are physical machines there
		// are

		$phys = $gz - $ld - $vb;

		$ret_str = "displaying ";
		
		// We may not have any physical servers - it could all be VBoxes.

		if ($phys > 0) {
			$ret_str .= "<strong>$phys</strong> physical server";

			if ($phys != 1) $ret_str .= "s";

			$parts++;
		}

		// Vboxes

		if ($vb > 0) {

			if ($phys > 0) {
				$ret_str .= ($ld == 0 && $lz == 0) ? " and" : ",";
			}

			$ret_str .= " <strong>$vb</strong> VirtualBox";

			if ($vb != 1) $ret_str .= "es";

			$parts++;
		}

		// Now LDOMs

		if ($ld > 0) {
			$ret_str .= ($lz == 0) ? " and" : ",";

			$ret_str .= " <strong>$ld</strong> logical domain";

			if ($ld != 1) $ret_str .= "s";

			$parts++;
		}

		if ($lz > 0) {

			if ($parts > 1)
				$ret_str .= ",";

			$ret_str .= " and <strong>$lz</strong> non-global zone";

			if ($lz != 1) $ret_str .= "s";
		}

		$ret_str .= ".";

		if ($others > 0)
			$ret_str .= "<p class=\"center\"><strong>$others</strong> other"
			. " machines known to system. (Total of " . $this->map->count
			. " physical and virtual machines, not counting local zones.)";

		return $ret_str;
	}

	public function zone_toggle()
	{
		// Print a link which lets the user show or hide zones, whichever is
		// appropriate. Also gives you the "next page" and "previous page"
		// links, if they are required.

		// Will we need previous/next links? Only if there are more known
		// servers than the current PER_PAGE limit

		$next_str = $prev_str = "";
		$os = $this->map->offset;
		$no = $os + PER_PAGE;
		$po = $os - PER_PAGE;

		if ($this->map->count > PER_PAGE) {

			if ($os != 0) {

				if ($po < 0)
					$po = 0;

				$prev_str = "<a href=\"$_SERVER[PHP_SELF]?o=${po}\">&lt;"
				. "previous</a> :: ";
			}

			if ($no < $this->map->count)
				$next_str = " :: <a href=\"$_SERVER[PHP_SELF]?o=${no}\">next"
				. "&gt;</a>";
		}

		// Are zones currently shown or hidden? Offer the alternative.

		if (defined("NO_ZONES")) {
			$qs = "?" . preg_replace("/(&*)no_zones/", "\\1",
			$_SERVER["QUERY_STRING"]);
			$txt = "show";
		}
		else {
			$qs = (empty($_SERVER["QUERY_STRING"]))
				? "?no_zones"
				: "?" . $_SERVER["QUERY_STRING"] . "&amp;no_zones";

			$txt = "hide";
		}

		return "${prev_str}<a href=\"$_SERVER[PHP_SELF]"
		. "${qs}\">$txt local zones</a>${next_str}";
	}

	//------------------------------------------------------------------------
	// Data processing functions

	protected function guess_ALOM_IP($data)
	{
		// If we don't have an ALOM IP address from the auditor, we assume
		// this is something like a T2000 that doesn't have scadm, but does
		// have a configured ALOM. So, we tag ALOM_SFX on to the hostname,
		// and see if it's in DNS. If it is, we use that as the IP address.
		// We tell show_ALOM_IP() not to use solid colour, so the user knows
		// the address has been "guessed".

		$call = false;

		if (defined("ALOM_SFX")) {

			if ($this->map->is_global($data["hostname"][0])) {
				$ip = gethostbyname($data["hostname"][0] . ALOM_SFX);

				if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/",
					$ip))
					$call = array($ip);
			}
			else
				$call = false;
		}
		
		return $this->show_ALOM_IP($call, true);
	}

	public function get_nic_col($key)
	{
		// Return the hex value for a network colour. To help generate the
		// key at the bottom of the page

		return colours::$nic_cols[$key];
	}

	private function get_subnet($addr)
	{
		// Pull a subnet out of an IP address (assuming class C). Doesn't
		// validate, so wil accept 678.981.582.999!

		if (preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3})\.\d{1,3}.*$/", $addr,
		$a))
			return $a[1];
	}

	protected function get_paired_list($prop1, $prop2)
	{
		// Make an array of the latest version of prop2 for each unique
		// prop1

		$lo = array();

		foreach ($this->servers as $server) {
		
			if (!isset($server[$prop1][0]) || !isset($server[$prop2][0]))
				continue;

			$p1 = $server[$prop1][0];
			$p2 = $server[$prop2][0];
	
			if (in_array($p1, array_keys($lo)))
				$lo[$p1][] = $p2;
			else
				$lo[$p1] = array($p2);;
		}

		foreach($lo as $key=>$val) {
			array_unique($val);
			natsort($val);
			end($val);
			$lo[$key] = current($val);
		}

		return $lo;
	}

}

//==============================================================================
// PLATFORM GRID

class PlatformGrid extends HostGrid
{
	protected $latest_obps = array();
		// An array of "hardware" => "obp"

	protected $latest_aloms = array();
		// An array of "hardware" => "alom"

	protected $key_filename = "key_platform.php";
		// Helps us include they key file automatically

	public function __construct($map, $servers)
	{
		// The constructor is the standard HostGrid one, but it also
		// generates the $latest[] OBP and ALOM arrays
	
		parent::__construct($map, $servers);
		$this->latest_obps = $this->get_paired_list("platform", "OBP");
		$this->latest_aloms = $this->get_paired_list("platform", "ALOM f/w");
	}

}

//==============================================================================
// O/S GRID

class OSGrid extends PlatformGrid
{
	protected $latest_kerns;
		// An array of OS ver => kernel version. OS ver is made up of
		// distribution + SunOS version + architecture
		// e.g.
		// solaris5.10s => 142900-01
		// opensolaris5.11x => 129
		// where "s" denotes SPARC and "x" x86/amd64

	protected $key_filename = false;
    public function __construct($map, $servers)
	{
		// The constructor is the standard HostGrid one, but it also
		// generates the $latest[] array

		parent::__construct($map, $servers);
		$this->latest_kerns = $this->get_latest_kerns();
	}

	protected function mk_ver_arch_str($zn, $dist, $sver)
	{
		// Make a version/arch string for the given zone by concatenating
		// the distribution, version, and architecture 

		$dist = preg_replace("/ zone/", "", $dist);
		$osver = preg_replace("/\W/", "", $dist . $sver);

		$arch = ($this->map->is_global($zn))
			? $this->map->get_zone_prop($zn, "hardware", "platform")
			: $this->map->get_parent_prop($zn, "hardware", "platform");

		$arch = (preg_match("/SPARC/", $arch))
			? "s"
			: "x";

		return $osver . $arch;
	}

	private function get_latest_kerns() 
	{
		// Get an array of the latest kernel versions for each version of
		// solaris on each architecture

		$lk = array();

		foreach ($this->servers as $server) {

			if (!isset($server["distribution"][0]) ||
			!isset($server["version"][0]))
				continue;

			$kp = $server["kernel"][0];
			$osver = $this->mk_ver_arch_str($server["hostname"][0],
			$server["distribution"][0], $server["version"][0]);

			if (in_array($osver, array_keys($lk))) {

				if ($kp > $lk[$osver])
					$lk[$osver] = $kp;
			}
			else
				$lk[$osver] = $kp;

		}

		return $lk;
	}
	
}

//==============================================================================
// FS AUDIT

class FSGrid extends HostGrid
{
	protected $mntd_nfs = array();

		// This array counts the number of times each NFS mount is used. It
		// is populated by show_fs(), and read by show_exports(). It's here
		// because we only want it to be populated when we do a proper FS
		// audit, not when we're comparing or showing a single server.

	public function __construct($map, $servers)
	{
		parent::__construct($map, $servers);
		$this->mntd_nfs = $this->get_nfs_mounts();
	}
	
	private function cb_nfs_mounts($row)
	{

		return (preg_match("/\(nfs:/", $row))
			? true
			: false;
	}

	protected function get_nfs_mounts()
	{
		// populate an array pairing NFS mounted filesystems, in the form
		// server:/full/path, with the amount of times we see them mounted.
		// Used by the exports column on the fs audit page

		// Get the filesystems for each zone

		$ta = array();

		foreach($this->map->list_all() as $srvr) {

			if (!isset($this->servers[$srvr]["fs"]))
				continue;

			// filter out any non-NFS filesystem

			$nfs_arr = array_filter($this->servers[$srvr]["fs"],
			array($this, "cb_nfs_mounts"));

			$ta = array_merge(
				preg_replace("/^.* \(nfs:[^:]*:([^:]*):([^:\)]*).*$/",
				"\\1:\\2", $nfs_arr), $ta);
		}

		return array_count_values($ta);
	}
}

//==============================================================================
// SECURITY AUDIT

class SecurityGrid extends HostGrid{

	protected $user_list = array();
		// Working array of users we encounter

	protected $known_users = array();
		// A list of known username -> array(uids), used to catch collisions

	protected $known_uids = array();
		// A list of known UID -> array(username), used to catch collisions

	protected $omit = array();

	public function __construct($map, $servers)
	{
		parent::__construct($map, $servers);
		$this->omit = new omitData();

		if (isset($this->omit->omit_users)) {

			foreach($this->omit->omit_users as $e) {
				preg_match("/^(\w+) \((\d+)\)$/", $e, $a);

				$this->known_users[$a[1]] = array($a[2]);
				$this->known_uids[$a[2]] = array($a[1]);
			}

		}
	}

}

//==============================================================================
// SOFTWARE GRID

class SoftwareGrid extends HostGrid
{
	// This class generates the grids for the Application and Tools audits.
	// They're effectively the same, and used to be part of the same
	// "software" audit, hence the naming. It's a simple extension of the
	// standard HostGrid.

	protected $latest = array();	
		// an array of the most up-to-date versions of each piece of
		// software that's audited

	protected $ignore_version = array("hostname", "apache_so", "audit
	completed");
		// Don't try to find the latest versions of these fields

	protected $adj_fields = array(
		"Apache" => "apache so",
		"apache so" => "mod_php",
		"mod_php" => "Sun Web Server"
		);

	protected $key_filename = "key_application.php";

	public function __construct($map, $servers)
	{
		// The constructor is the standard HostGrid one, but it also
		// generates the $latest[] array

		parent::__construct($map, $servers);
		$this->latest = $this->get_latest();
	}

	public function show_generic($data, $field, $subname = false)
	{
		// This is a replacement show_generic() only used on the application
		// and tool audits.  It can handle single and multiple rows of data.
		// It differs from the standard version in that it colours the cells
		// according to version numbers. If subname is set to "NOBG", don't
		// do background colouring.

		$call = $col = $ret_str = false;

		if (is_string($data))
			$call = $data;
		else {

			if (sizeof($data) == 1)
				$call = $data[0];
			elseif(sizeof($data) > 1) {

				$ret_str = multiCell::open_table();

				foreach($data as $datum) {
					$ret_str .= "\n<tr class=\"multicell\">" .
					$this->show_generic($datum, $field) . "</tr>";
				}

				$ret_str = new Cell($ret_str . "\n</table>");
			}
			else
				$call = false;

		}

		if ($call) {
			$ta = preg_split("/@=/", $call);

			$path = (isset($ta[1]))
				? $ta[1] 
				: false;

			$call = $ta[0];

			// Colour the cell depending on certain words that it will
			// contain. Latest version is green, old version pale red, no
			// version dark red. Version numbers can also be boxed in red if
			// the software is "not running". This generally applies to app
			// audits

			if (preg_match("/not running/", $call))
				$col = inlineCol::box("red");
			elseif (preg_match("/unknown/", $call))
				$col = inlineCol::solid("red");

			// and, if we can,  change the background colour depending on
			// the version number. Can't do this in a single server audit

			$bg_class = false;

			if (method_exists($this, "strip_out_version")) {

				$sw_ver = $this->strip_out_version($call);
				$recent = $this->how_recent($field, $sw_ver, $subname);

				if ($sw_ver && $recent && $subname != "NOBG") {
				
					if ($recent == 2)
						$bg_class = "sw_latest";
					elseif($recent == 1)
						$bg_class = "sw_old";
				}

			}

			$ret_str = new Cell($call, $bg_class, $col, false, false, $path);
		}

		return $ret_str;
	}

	//-- sorting -------------------------------------------------------------

	private function get_latest()
	{
		// Get the latest version of each piece of software, ignoring
		// software in the ignore_version array. Produces an array like
		// this:
		//  [svnserve] => 1.6.5
		// [exim] => 4.69
		// [sshd] => Array
		//	(
		//		Sun_SSH] => 1.5
		//		[OpenSSH] => 4.9p1
		// 	)
		// 
		// [X server] => 
		// [Apache] => 2.2.14
		// 
		// Things like sshd[] have their own get_latest function

		$ret_arr = $all_sw = array();

		// Assuming we've got a valid array, mash together all the servers
		// and software into one big array

		foreach($this->servers as $hostname=>$sw) {

			if (is_array($sw))
				$all_sw = array_merge_recursive($sw, $all_sw);
		}

		// In the loop below, $sw is the name of the software, and $vers is
		// an unsorted array of all the version numbers we have. Strip
		// extraneous info off the version, sort what's left, then pick off
		// the final one, and store it

		foreach($all_sw as $sw=>$vers) {	

			// Skip anything we don't need to sort. We're potentially doing
			// a lot of work in this loop, and we want to minimize it as
			// much as we can.

			if (in_array($sw, $this->ignore_version))
				continue;

			// Minimize the array by removing duplicate keys, then use a
			// callback to strip out any "unknown" version strings

			$ver_arr = array_filter(array_unique($vers), array($this,
			"cb_no_unknowns"));

			// Chop off all the software paths. preg_replace can be used on
			// every element in an array simultaneously, then unique the
			// array again

			$ver_arr = array_unique(preg_replace("/(@=| ).*$/", "",
			$ver_arr));

			// Some software has its own special version of get_latest which
			// can handle multiple "latest" versions. We can branch out to
			// that function here, if one exists

			$method = strtolower(str_replace(" ", "_", "get_latest_$sw"));

			if (method_exists($this, $method))
				$ret_arr[$sw] = $this->$method($ver_arr);
			else {

				// Natural sorting realizes that 5.2.11 is later than 5.2.9.
				// Can't find anything else that does.

				natsort($ver_arr);
	
				// Whizz to the end of the array and pick off the last
				// element

				end($ver_arr);
				$ret_arr[$sw] = $this->strip_out_version(current($ver_arr));
			}

		}

		return $ret_arr;
	}

	private function get_latest_sshd($ver_arr)
	{
		// SSH (currently) comes in two flavours. OpenSSH and SunSSH. We
		// want to be able to get the latest versions for both of these.
		// They both take the form Name_x.y.z

		return $this->get_latest_multi($ver_arr, '(^.*SSH)_(.*)$');
	}

	private function get_latest_multi($ver_arr, $regex)
	{
		foreach($ver_arr as $ver) {
			preg_match("/$regex/", $ver, $a);
			$arr[$a[1]][] = $a[2];
		}

		foreach($arr as $type=>$vers) {
			natsort($vers);
			end($vers);
			$ret_arr[$type] = current($vers);
		}

		return $ret_arr;
	}
	
	private function get_latest_x_server($ver_arr)
	{
		return $this->get_latest_multi($ver_arr, '(^X\w*)-*(.*)$');
	}

	private function strip_out_version($str)
	{
		// Try to return a version number from a string.
		// To do this, take off anything prior to the first digit and after
		// the next space.

		if (is_array($str))
			$str = $str[0];

		return preg_replace("/^[^\d]*(\d[^ ]*).*$/", "\\1", $str);
	}

	private function cb_no_unknowns($str)
	{
		// Callback function used by get_latest()

		return (preg_match("/unknown/", $str))
			? false
			: true;
	}

	private function how_recent($name, $version, $subname = false) {

		// find out whether $name is the most recent $version that we have.
		// $subname is for things like SSHD, which store the latest version
		// as an array rather than a string.
		// Returns:
		//      2 : latest version
		//      1 : old version
		//  false : unknown

		// Strip off path and extra info just like we did in get_latest()

		$version = preg_replace("/(@=| ).*$/", "", $version);

		$ret = false;

		if (array_key_exists($name, $this->latest)) {
			$lsh = $this->latest[$name];

			if (is_array($lsh)) {

				$latest = (isset($lsh[$subname]))
					? $lsh[$subname]
					: false;
			}
			else
				$latest = $lsh; 

			$ret = ($version == $latest)
				? 2
				: 1;
		}

		return $ret;
	}

	protected function grid_key()
	{
		// Put in the key. This is a special method for tools and apps,
		// because we can never really be sure what fields we have, and
		// because the same key applies to every column.
		
		$nf = sizeof($this->fields);

		$ret = "\n<tr><td class=\"keyhead\" colspan=\"${nf}\">key</td>"
		. "</tr>\n<tr>";

		$ret .= $this->grid_key_col($this->grid_key["hostname"])
		. $this->grid_key_col($this->grid_key["general"], ($nf - 2))
		. $this->grid_key_col($this->grid_key["audit completed"]);

		return $ret . "</tr>";
	}

}

//==============================================================================
// HOSTED SERVICES GRID

class HostedGrid extends HostGrid
{
	// The resolved array keeps track of server names we've already
	// resolved, so we don't waste time doing duplicates

	protected $ip_list = array();
	protected $nfs_dirs = array();

	public function __construct($map, $servers)
	{
		// This does a bit more than the usual grid class. We try to get a
		// list of our resolved external IP addresses, and we try to get a
		// list of all NFS mounted directories

		parent::__construct($map, $servers);
		$this->ip_list = $this->get_ip_list();

		$fss = new GetServersFS($map, false);
		$this->nfs_dirs = $this->get_nfs_dirs($fss);
	}

	protected function get_nfs_dirs($fss)
	{
		// Get a list of all NFS mounted directories. We use this to work
		// out whether or not document roots are NFS mounted. Each nfs mount
		// is an element in an array. Arg is the servers map created in the
		// constructor

		$ra = array();

		foreach($fss->servers as $server=>$data) {

			if (!isset($data["fs"]))
				continue;
				
			foreach($data["fs"] as $fs) {
				preg_match("/^(\S*) \((\w*):.*$/", $fs, $a);

				if ($a[2] == "nfs")
					$ra[$server][] = $a[1];

			}

		}

		return $ra;
	}

	protected function get_ip_list()
	{
		// The s-audit_resolver.sh should have left us a file which maps
		// site names to their external IP addresses. It's not easy to have
		// dig produce that file exactly how we'd like it, so we manipulate
		// a little here.
		
		// Let's see if the map file is there. If not, exit now.

		$map_file = URI_MAP_FILE;

		if (!file_exists($map_file))
			return array();

		$ra = array();
		$t_arr = file($map_file);

		// The dig batch lookup only returns IP addresses for A records.
		// For CNAMEs it returns the name of the alias. I want to display IP
		// addresses only, so we'll change anything  in $ip_map[] that looks
		// like a CNAME into its IP address.

		// We know that the sort command in the becta_audit_resolver.sh
		// script puts IP addresses are at top of the audit file and CNAMES
		// at the bottom. Thus we can keep a running array of resolved_name
		// => IP_address, and use it to look up CNAMES

		foreach($t_arr as $ip_line) {

			// Discard junk

			if (!preg_match("/=/", $ip_line))
				continue;

			$t = explode("=", $ip_line);
			$name = trim($t[0]);
			$addr = trim($t[1]);
			unset($ip);

			// t[0] is the DNS name, t[1] should be the IP address, but
			// may be CNAME info. Any letters in t[1] and it's a CNAME

			if (preg_match("/[a-z]/", $addr)) {

				if (in_array($addr, array_keys($ra)))
					$ip = $ra[$addr];

				if (!isset($ip))
					$ip = "$addr (CNAME)";

				$ra[$name] = $ip;

			}
			else 
				$ra[$name] = $addr;

		}

		return $ra;
	}
}

//==============================================================================
// SINGLE SERVER VIEW GRIDS

class serverView extends HostGrid {

	// This class handles presentation of all audit files for a single
	// server or zone. It doesn't do a great deal of work itself, but
	// depends on the singleClasses to handle each audit type
	
	private $alldata;
		// The parsed audit data, in a big-ass array

	public function __construct($server, $alldata, $mmap)
	{
		$this->alldata = $alldata;
		$this->server = $server;
		$this->map = $mmap;
	}

	public function show_grid()
	{
		// The grid in this case is a list of tables, one for each audit
		// type

		$ret = false;

		foreach($this->alldata as $type=>$data) {
			$class = "single$type";

			$ret .= (class_exists($class))
				? new $class($type, $data, $this->map)
				: new singleGeneric($type, $data, $this->map);
		}

		return $ret;
	}
}

class singleGeneric extends HostGrid {

	// This class isn't currently used, as I've created an extension class
	// for each audit type. It's left as it is though, because if a new
	// audit class is created, this class will automatically, if
	// imperfectly, display it

	protected $cols = 3;
		// The default number of columns of properties. Each "column" is
		// really two <table> columns, because there's name and value

	protected $type;
		// The type of audit. "platform", "fs" or whatever

	protected $data;
	protected $map;
		
	protected $one_cols = array();
		// A list of server properties you want to span the whole table. For
		// long data like cron jobs or filesystems

	protected $html;
		// We build up the HTML of audit data here, and pass it back with
		// to_string.

	public function __construct($type, $data, $map)
	{
		// The printed name of the audit class comes from capitalizing the
		// first letter of the class name. This can be overriden by setting
		// $type in the inheriting class

		if (!isset($this->type))
			$this->type = ucfirst($type);

		// Set some class variables

		$this->data = $data;
		$this->map = $map;

		// And start populating the $html variable with the title of this
		// audit class

		$this->html = "\n\n<table align=\"center\" width=\"700\">"
		. "<tr><td class=\"sclh\">$this->type audit </td></tr>\n</table>\n";

		// Now call show_class() to get the table 

		$this->html .= $this->show_class();
	}

	public function __toString()
	{
		return $this->html;
	}

	protected function show_class()
	{

		$ret = "\n\n<table align=\"center\" width=\"700\">";

		if (sizeof($this->data) == 2)
			return $ret . "<tr><td class=\"comprow\" style=\"text-align:
			center\">No data</td></tr>\n</table>\n";

		// Each element of the data[] array is a property, like "hostname"
		// or "exim". Step through them all. If there's a specific
		// show_property() function, use it. If not, use show_generic()

		$c = 0;	// column counter

		foreach($this->data as $field=>$val) {

			// Certain fields are skipped

			if ($field == "hostname" || $field == "audit completed")
				continue;

			// If we're on the first column, start a new table row

			if ($c == 0)
				$ret .= "<tr class=\"zone\">";

			// Some cells span the whole table. They're listed in the
			// $one_cols array. If we hit one of those, we need to close off
			// the existing row, then span the whole row with a single cell

			if (in_array($field, $this->one_cols)) {

				// Do we already have anything on this row? If we do, pad it
				// out with a blank cell, close the row and start a new
				// one.

				if ($c != 0) 
					$ret .= new Cell(false, "blank", false, false,
					(($this->cols - $c) * 2)) . "</tr>\n<tr
					class=\"zone\">";

				$val_cell = preg_replace("/<td/", "<td colspan=\"" .
				(($this->cols * 2) - 1) . "\"", $this->show_cell($field,
				$val));
				
				$c = $this->cols;
			}
			else {
				$val_cell = $this->show_cell($field, $val);
				$c++;
			}

			$ret .= new Cell($field, "comprow") . $val_cell;

			if ($c == $this->cols || in_array($field, $this->one_cols)) {
				$ret .= "</tr>";
				$c = 0;
			}

		}

		if ($c !=0)
			$ret .= "</tr>";

		return $ret . $this->completed_footer($this->cols * 2);
	}

	protected function completed_footer($cols)
	{
		// Print an "audit completed" bar across the whole table
	
		return "\n<tr><td class=\"scompl\" colspan=$cols>audit completed: "
		. $this->data["audit completed"][0] .  "</td></tr></table>";
	}

	protected function show_cell($field, $val)
	{
		// Split out because it was overriden in the app/tool class. Isn't
		// any more, but still split out

		$method = preg_replace("/\W/", "_", "show_$field");

		return (method_exists($this, $method))
			? $this->$method($val, $this->data)
			: $this->show_generic($val);
	}

}

class singlePlatform extends singleGeneric {

	// Platform doesn't need anything clever doing, but we spread the NIC
	// information right across the table

	protected $one_cols = array("NIC");
}

class singleOS extends singleGeneric {

	// Change the name and put zones  and LDOMs in a single column

	protected $type = "O/S";
	protected $one_cols = array("local zone");

}

class singleFS extends singleGeneric {
	protected $type = "Filesystem";
	protected $one_cols = array("fs", "export");
}

class singleApp extends singleGeneric {
	protected $cols = 4;
	protected $type = "Application";

	protected function show_generic($data)
	{
		return softwareGrid::show_generic($data, false);
	}

}

class singleTool extends singleApp {
	protected $type = "Tool";
}

class singleHosted extends singleGeneric {
	protected $type = "Hosted Services";
	protected $one_cols = array("website", "database");
}

class singleSecurity extends singleGeneric {
	protected $one_cols = array("port", "cron job", "user_attr");
	protected $hard_fold = 130;
}

class singlePlist extends singleGeneric
{
	// Patches and packages are handled in a special way. They're typically
	// very long lists

	protected $type = "Patch and Package";

	protected function show_class()
	{

		$ret = "";
		$blocks = sizeof($this->data) / 2;
		$i = 1;
		$hn = $this->data["hostname"][0];
		$pkg_arr = array();

		$os = ($this->map->is_global($hn))
			? $this->map->get_zone_prop($hn, "version", "os")
			: $this->map->get_parent_prop($hn, "version", "os");

		$os = preg_replace("/ .*$/", "", $os);

		foreach($this->data as $field=>$val) {

			if ($field == "hostname" || $field == "audit completed")
				continue;


			// Work out what the hover map is likely to be called

			$fn = ($this->map->is_global($hn))
				? "get_zone_prop"
				: "get_parent_prop";

			// The hover map only currently works for "proper" Solaris,
			// versions 10 and earlier, so ignore everything else. (This
			// will change.)

			$dist = $this->map->$fn($hn, "distribution", "os");

			if ($dist == "Solaris") {

				// Work out the path to the patch or package definition file

				$hw = (preg_match("/SPARC/", $this->map->$fn($hn,
				"hardware", "platform")))
					? "sparc"
					: "i386";

				$ver = preg_replace("/^.*SunOS ([0-9.]*).*$/", "\\1",
				$this->map->$fn($hn, "version", "os"));
			}

			// How many columns? And do we have a "hover" map?

			//-- package lists -----------------------------------------------

			if ($field == "package") {
				$pdef = 5 ;	// 5 columns for packages
				$hover = PKG_DEF_DIR .  "/pkg_def-${dist}-${ver}-${hw}.php";
			}
			//-- patch lists -------------------------------------------------
			else {
				$pdef = 12;	// 12 columns for patches
				$hover = PCH_DEF_DIR .  "/pch_def-${ver}-${hw}.php";
			}

			// Include the hover map, if we have it

			if (file_exists($hover))
				include($hover);

			$cols = (sizeof($val) > $pdef)
				? $pdef
				: sizeof($val);

			$ret .= "\n\n<table align=\"center\" width=\"700\">"
			. "\n  <tr><th colspan=\"$cols\">$field</th></tr>";

			$c = 0;

			foreach($val as $p) {
			
				if ($c == 0) 
					$ret .= "\n  <tr class=\"zone\">";

				// Highlight partially installed packages with a red border

				if (preg_match("/ \(/", $p)) {
					$bcol = inlineCol::box("red");
					$p = preg_replace("/ .*$/", "", $p);
				}
				else
					$bcol = false;

				// Highlight non-SUNW packages or patches that don't start
				// with a 1

				$fcol = ($field == "patch" && !preg_match("/^1/", $p) ||
				($field == "package" && !preg_match("/^SUNW/", $p)))
					? "smalldisk"
					: "smallrow";

				// Anything in the hover map? We have to trim the revision
				// off for patches

				$pm = ($field == "package")
					? $p
					: substr($p, 0, 6);

				$hover = (in_array($pm, array_keys($hover_arr)))
					? $hover_arr[$pm]
					: false;

				$ret .= new Cell($p, $fcol, $bcol, false, false, $hover);
				$c++;

				if ($c == $cols) {
					$ret .= "</tr>";
					$c = 0;
				}

			}

			if ($c > 0)
				$ret .= "</tr>";

			$ret .= ($i++ == $blocks)
				? $this->completed_footer($pdef)
				: "\n</table>";
		}

		return $ret;
	}

}

//- HTML and template stuff --------------------------------------------------

class Page {
	
	// Classes used to generate HTML pages. Each audit display page should
	// begin by creating an instance of this class. Documentation pages use
	// the docPage class, which extends this one

	protected $title;
		// The page title. Used in the <title> tag 
	
	protected $type;
		// "platform audit" or whatever

	protected $styles = array("basic.css");
		// Stylesheets to apply

	private $metas = array(
		"Content-Type" => "text/html; charset=utf-8",
		"pragma" => "no-cache");
		// HTTP meta tags. Key/value pairs

	public function __construct($title)
	{
		$this->type = $title;
		$this->title = SITE_NAME . " :: $title";
		$this->styles[] = "dynamic_css.php?" .
		basename($_SERVER["PHP_SELF"]);
		echo $this-> open_page();
	}

	protected function open_page()
	{
		// Generate all the HTML for a valid page, up to the start of the
		// content. The closing HTML is done by the main page file calling
		// the close_page() method. I have put the <head> tags in to make it
		// clear what functions make what part of the page.

		return 
			$this->start_page()			// open html
			. "\n<head>"
				. $this->add_styles()	// add stylesheets
				. $this->add_metas()	// add <meta> tags
				. "\n  <title>$this->title</title>"
			. "\n</head>\n"
			. "\n<body>"
			. $this->add_header(); 		// horizontal title/navigation
	}

	private function start_page()
	{
		// Print the XHTML DOCTYPE and whatnot

		return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional'
		. '//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
		. "\n\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
	}

	private function add_styles()
	{
		// insert HTML for each stylesheet defined in the $styles variable

		$ret = "";

		foreach($this->styles as $style) {
  			$ret .= "\n  <link rel=\"StyleSheet\" href=\"/_css/${style}\" "
			. "type=\"text/css\" media=\"all\" />";
  		}

		return $ret;
  	}

	private function add_metas()
	{
		$ret = "\n";

		foreach($this->metas as $key=>$val) {
  			$ret .= "\n  <meta http-equiv=\"$key\" content=\"$val\" />";
  		}

		return $ret;
  	}

	public function close_page()
	{
		echo "</div>" . Page::add_footer() . "\n</body>\n</html>";
	}

	protected function add_footer()
	{
		$ret =  "\n\n<div id=\"footer\">This is &quot;" .  SITE_NAME 
		. "&quot; | s-audit web interface " . "version " . MY_VER
		. " | (c) 2010 <a href=\"http://snltd.co.uk\""
		. ">SNLTD</a>";

		if (SHOW_SERVER_INFO)
			$ret .= " | Running under PHP " . phpversion() . " on " .
			php_uname("n");

		return $ret . "</div>";
		
	}

	public function error($msg = "undefined error")
	{
		echo "<p class=\"error\">ERROR: $msg</p>" .  Page::close_page();
		exit();
	}

}

class ipPage extends Page {

	// Generates the IP listing page

	protected $styles = array("basic.css", "audit.css", "ip_listing.css");
		// Stylesheets to apply

	protected function add_header()
	{
		$nav = new NavigationStaticHoriz;

		$fn = basename($_SERVER["PHP_SELF"]);

		return "\n
		<div id=\"header\">
			<div id=\"headerl\">
				<div id=\"logo\">s-audit</div>
				<div id=\"sublogo\">IP address listing</div>
			</div>
			<div id=\"headerr\">
				<div>documentation ::
				<a href=\"" . DOC_URL . "/index.php\">main</a> /
				<a href=\"" . DOC_URL
				. "/interface/${fn}\">this page</a>
				</div>
			</div>
		</div>"
		. $nav->display_navbar() . "\n<div id=\"content\">";
	}

}

class audPage extends Page {

	// Generates audit grid pages

	protected $styles = array("basic.css", "audit.css");
		// Stylesheets to apply

	protected $s_count = false;
		// the "displaying..." text in the header

	protected $z_tog = false;
		// the "show zones" string in the header

	public function __construct($title, $s_count, $z_tog) {
		$this->s_count = $s_count;
		$this->z_tog = $z_tog;
		parent::__construct($title);
	}

	protected function add_header()
	{
		$nav = new NavigationStaticHoriz;

		$fn = basename($_SERVER["PHP_SELF"]);

		$class_link = ($fn == "index.php")
			? "class_platform.php"
			: "class_$fn";

		return "\n
		<div id=\"header\">
			<div id=\"headerl\">
				<div id=\"logo\">s-audit</div>
				<div id=\"sublogo\">$this->type :: $this->s_count</div>
			</div>
			<div id=\"headerr\">
				<div>documentation ::
				<a href=\"" . DOC_URL . "/index.php\">main</a> /
				<a href=\"" . DOC_URL
				. "/interface/${class_link}\">this page</a> /
				<a href=\"" . DOC_URL
				. "/client/${class_link}\">this class</a>
				</div>
				<div>$this->z_tog</div>
			</div>
		</div>"
		. $nav->display_navbar() . "\n<div id=\"content\">";
	}

}

class ssPage extends audPage {
	
	// Special class for single server view

	public function __construct($title, $s_count) {
		$this->s_count = $s_count;
		parent::__construct($title, $s_count, false);
	}

	protected function add_header()
	{
		$nav = new NavigationStaticHoriz;

		return "\n
		<div id=\"header\">
			<div id=\"headerl\">
				<div id=\"logo\">s-audit</div>
				<div id=\"sublogo\">$this->type</div>
			</div>
			<div id=\"headerr\">
				<div>documentation ::
				<a href=\"/docs/index.php\">main</a> /
				<a href=\"/docs/interface/" . basename($_SERVER["PHP_SELF"])
				. "\">this page</a> /
				</div>
			</div>
		</div>"
		. $nav->display_navbar() . "\n<div id=\"content\">";
	}

}


class docPage extends Page {

	protected $styles = array("basic.css", "audit.css", "doc.css",
	"script.css", "ip_listing.css");
		// Stylesheets to apply

	public function __construct($title)
	{
		require_once(ROOT . "/_lib/filesystem_classes.php");
		require_once(ROOT . "/_lib/doc_classes.php");
		$this->title = "s-audit documentation :: $title";
		$this->styles[] = "dynamic_css.php?" .
		basename($_SERVER["PHP_SELF"]);
		echo $this-> open_page();
	}

	protected function add_header()
	{
		return "<div id=\"header\">"
					. "<div id=\"logo\">s-audit</div>"
					. "<div id=\"sublogo\">$this->title</div>"
		."</div>"
		. "\n<div id=\"docwrapper\">"
		. "\n<div id=\"doccontent\">";
	}

	private function dyn_menu()
	{
		// Put the dynamic menu on the right of the page. For documentation
		// pages. First close the "content" div, and open another. That'll
		// be closed by the close_page() function

		$vm = new NavigationDynamicVert();

		return "\n</div>\n<div id=\"vmenu\">" . $vm->print_list() .
		"</div>";
	}

	protected function add_footer()
	{
		// Print the footer for documentation pages. We keep the
		// documentation version in a file now
		
		$verfile = ROOT . "/docs/.version";
		
		$verstr = (file_exists($verfile))
			? " version " .file_get_contents($verfile)
			: "";

		return "\n\n<div class=\"spacer\">&nbsp;</div>" .
		"\n\n<div id=\"footer\">s-audit documentation $verstr| (c) 2010 <a
		href=\"http://snltd.co.uk\">SNLTD</a></div>";
	}

	public function close_page()
	{
		echo  docPage::dyn_menu() . "</div>" . docPage::add_footer() 
		. "\n\n</body>\n</html>";
	}

}

class NavigationDynamicVert {

	// This class dynamically generates the vertical menu used in the
	// documentation pages

    private $my_f;
    private $my_d;

	private $all_f;
	private $all_d;

    public function __construct()
    {
        $this->my_f = $_SERVER["SCRIPT_FILENAME"];
		$this->my_d = dirname($this->my_f);
		$this->all_d = Filesystem::get_files(ROOT . "/docs", "d");
		$this->all_f = Filesystem::get_files($this->my_d, "f");
    }

	public function print_list()
	{
		$ret = "\n<ul class=\"vmd\">";

		foreach($this->all_d as $d) {
			$da = Filesystem::all_fnames($d);

			// Ignore "hidden" directories. That is, ones whose names start
			// with an underscore

			if (preg_match("/^_/", $da["link"]))
				continue;

			$ret .= ($this->my_d == $d)
				? "\n  <li>$da[link]</li>"
				: "\n  <li><a href=\"$da[url]\">$da[link]</a></li>";

			if ($d == $this->my_d)
				$ret .= $this->this_dir_list();
		}

		return $ret . "\n</ul>";
	}
	
	private function this_dir_list()
	{
		$ret = "\n<li><ul class=\"vmf\">";

		// Move the index.php page to the front of the array

		$ind = dirname($this->my_f) . "/index.php";
		$arr = array_reverse($this->all_f);

		if ($k = array_search($ind, $arr)) {
			unset($arr[$k]);
			$arr[] = $ind;
			$arr = array_reverse($arr);
		}
	
		foreach($arr as $f) {
			$da = Filesystem::all_fnames($f);
			eval(Filesystem::getline($da["file"], "menu_entry"));

			$ret .= ($f == $this->my_f)
				? "\n  <li>$menu_entry</li>"
				: "\n  <li><a href=\"$da[url]\">$menu_entry</a></li>";
		}

		return $ret . "\n</ul></li>";
	}

}

class NavigationStaticHoriz {

	// We used to dynamically create the navigation bar, but now it's
	// hardcoded. This gives us greater flexibility, and it's hardly a lot
	// of work to add a link. Just put it in the array below. filename =>
	// description

	private $o;
	private $links = array(
		"index.php" => "platform",
		"os.php" => "O/S",
		"fs.php" => "filesystem",
		"application.php" => "applications",
		"tools.php" => "tools",
		"hosted.php" => "hosted services",
		"security.php" => "security",
		"server.php" => "single server view",
		"compare.php" => "compare two servers",
		"ip_listing.php" => "IP address listing"
	);

	public function __construct()
	{
		$this->o = isset($_GET["o"]) ? $_GET["o"] : 0;

		if (file_exists(ROOT . "/monitor/index.php"))
			$this->pages["row2"]["/monitor/index.php"] = "Monitor";

	}

	public function display_navbar()
	{
		// Make an unordered list of everything in the $links array. CSS
		// will do the rest

		$ret = "\n<ul class=\"navlist\" id=\"navlist\">";
		$here = basename($_SERVER["SCRIPT_FILENAME"]);

		foreach($this->links as $pg => $txt) {

			$ret .=($pg == $here)
				? "\n<li class=\"here\">$txt</li>"
				: "<li><a href=\"${pg}?o=$this->o\">$txt</a></li>";

		}
		
		return $ret . "</ul>";
	}

}

class html {

	static function dialog_submit($name, $value)
	{

		// code to produce a generic submit button

		return "\n<input type=\"submit\" name=\"$name\" value=\"$value\" />";
	}

	static function dialog_form($page, $method = "post")
	{

    	// open a form

		return "\n<form action=\"$page\" method=\"$method\">\n";
	}

	static function dialog_cycle($name, $data, $default = false)
	{

		// return the HTML for a generic cycle gadget whose OPTIONs are
		// stored in the $data array. If $data[] is associative, the key is
		// the OPTION and the val is the text. $force forces associative
		// mode. Very useful for when the vals are numeric

		if (empty($data))
			return false;

		$str = "\n<select name=\"$name\">";

		// Have we been given a default value?

		if ($default) {
			$default = trim($default);

			// the given value goes to the start of the data[] array.

			$data = array_merge(array("$default"), (array)$data);
			$data = array_unique($data);
		}

		// now print out the array we've just built up
		
		while(list($real, $show) = each($data)) {
			$str .= "\n  <option value=\"$show\">$show</option>";
    	}

		return $str .= "\n</select>\n";

	}
}

class Units {

	static $m = array(
			0 => "",
			1 => "k",
			2 => "m",
			3 => "g",
			4 => "t",
			5 => "p");

	static function to_b($in)
	{
		// Convert a Kb, Mb etc into b

		preg_match("/^([.\d]*)([kmgtp])(.*$)/i", $in, $bits);

		$rm = array_flip(Units::$m);
		$suff = strtolower($bits[2]);

		return (in_array($suff, array_keys($rm))) 
			? $bits[1] * pow(1024, $rm[$suff])
			: false;
	}

	static function from_b($size = 0, $sfx = "b")
	{
		// print a number of bytes as K, M, G, T + optional suffix

		//$e = floor((strlen($size) - 1) / 3);

		if ($size < 1024)
			$sf = 0;
		elseif ($size < 1048576)
			$sf = 1;
		elseif ($size < 1073741824)
			$sf = 2;
		elseif ($size < 1099511627776)
			$sf = 3;
		elseif ($size < 1125899906842624)
			$sf = 4;
		else
			$sf = 5;

		return (in_array($sf, array_keys(Units::$m)))
			? round($size / pow(1024, $sf), 1) . strtoupper(Units::$m[$sf]) .
			$sfx
			: false;
	}

	static function h_m_s($time)
	{
		// print a number of seconds as days, hours minutes seconds

		if ($time < 0)
			$ret_str = "undefined";
		elseif ($time < 60)
			$ret_str = "${time}s";
		elseif ($time < 3600) {
			$sec = $time % 60;
			$min = ($time - $sec) / 60;
			$ret_str = "${min}m ${sec}s";
		}
		elseif ($time < 86401) {
			$sec = $time % 60; // seconds
			$t = ($time - $sec) / 60; // whole minutes
			$min = $t % 60; // minutes
			$hour = ($t - $min) / 60;
			$ret_str = "${hour}h ${min}m ${sec}s";
		}
		else {
			$sec = $time % 60; // seconds
			$t = ($time - $sec) / 60; // whole minutes
			$min = $t % 60; // minutes
			$h = ($t - $min) / 60; // total hours
			$hour = $h % 24;  // whole hours
			$day = ($t - $min - ($hour * 60)) / 60 / 24;
			$ret_str = "${day}d ${hour}h ${min}m ${sec}s";
	 	}

		return $ret_str;
	}

}

class Cell {
	
	// A shorthand way to create (quite nasty) HTML <table> cells. Allows
	// you to colour them in a number of ways. Args are:

	// 1 - the content -- what gets printed in the cell
	// 2 - a class -- a style from the CSS
	// 3 - inline <style>
	// 4 - the width of the cell
	// 5 - how many columns the cell should span (colspan)
	// 6 - mouseover text

	public $html;
	private $class;
	private $style;
	private $width;
	private $span;
	private $mouseover;

	// display HTML table cells

	public function __construct($content = false, $class = false, $style =
	false, $width = false, $span = false, $mouseover = false) 
	{
		$this->content = $content;
		$this->class = $class;
		$this->style = $style;
		$this->width = $width;
		$this->span = $span;
		$this->mouseover = $mouseover;

		// call with $style to add inline style elements

		$str = $this->open_el();

		if ($this->class)
			$str .= " class=\"$this->class\"";

		if ($this->style)
			$str .= $this->add_style();

		if ($this->width)
			$str .= " width=\"$this->width\"";
	
		if ($this->span)
			$str .= " colspan=\"$this->span\"";

		if ($this->mouseover)
			$str .= " title=\"$this->mouseover\"";

		$str .= ">";

		if ($content != "-")
			$str .= $this->add_content($content);

		$this->html = $str . "</td>";
	}
	
	public function open_el()
	{
		return "\n<td align=\"center\" valign=\"top\"";
	}

	protected function add_content($content)
	{
		return $content;
	}

	protected function add_style()
	{
		return " style=\"$this->style\"";
	}

	public function __toString()
	{
		return $this->html;
	}

}

class multiCellSmall extends MultiCell {

	// This class is used to print lists in a single table cell

	public function open_table()
	{
		return "\n  <table class=\"multicellsmall\" width=\"100%\" "
		. "cellpadding=\"0\" cellspacing=\"0\">";
	}

}

class multiCellSmallnb extends MultiCell {

	// This class is used to print lists in a single table cell

	public function open_table()
	{
		return "\n  <table class=\"multicellsmallnb\" width=\"100%\" "
		. "cellpadding=\"0\" cellspacing=\"0\">";
	}

}

class multiCell extends Cell {

	// This class is used to print lists in a single table cell

	public function open_table()
	{
		return "\n  <table class=\"multicell\" width=\"100%\" "
		. "cellpadding=\"0\" cellspacing=\"0\">";
	}

	protected function add_content($content)
	{
		if (is_array($content)) {
			$i = 1;

			$rows = sizeof($content);

			$ret = $this->open_table();
	
			foreach($content as $row) {
				$class = ($i++ < $rows) ? "rowbar" : false;
				$ret .= $this->add_row($row, $class);
			}
			
			 $ret .= "</table>";
		}
		else
			$ret = false;

		return $ret;
	}

	protected function add_row($row, $class)
	{
		return"\n<tr>" . new Cell($row, $class) . "</tr>";
	}

}

class tableCell extends multiCell {

	// This class is used to print multi-column lists in a single table cell

	protected function add_row($row)
	{
		// Some data is better printed in two fields, in a little embedded
		// table

		$t = explode(" ", $row);

		return "\n<tr>" . new Cell($t[0]) . new Cell($t[1]) . "</tr>";
	}
		
}

?>
