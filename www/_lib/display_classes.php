<?php

//============================================================================
//
// display_classes.php
// -------------------
//
// Classes which sort, process, and display audit data.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once(LIB . "/pci_classes.php");

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

	private $t_start_grid;
	private $t_end_grid;
		// microtimings

	protected $fields;
		// We used to generate the list of fields completely dynamically,
		// but scanning the audit files and building up an array. But as the
		// range of fields grew, the field ordering became too
		// unpredictable. So now, for each audit class we supply an array of
		// expected defaults in $def_fields. We still scan the audits, and
		// add any fields not in $def_fields after that list. We then look
		// through the combined list of fields and knock out any that have
		// no data. A bit long-winded, but it produces the right results.

	protected $dyn_fields;

	protected $servers;
		// The big data structure we get from the reader class

	protected $hidden_fields = array("zone status", "_err_", "zone brand");
		// These fields are never shown in its own column, but handled
		// elsewhere

	protected $key_filename = false;
		// Can be used to override the default key filename

	protected $grid_key = false;
	protected $grid_notes = false;
		// Data for keys and notes, at the foot of the page

	protected $show_zones = true;

	protected $cz;
		// stores info on the current zone

	protected $audex;
		// extra data from static files

	protected $cols;
		// Colours

	private $lc;
		// last row class

	private $row_sfx;
		// helps get cells the right colour. "a" or "b"

	protected $toggle_str = "local zones";
		// "show/hide" string

	protected $af_ver;
		// The version of the audit file currently being processed. Set each
		// time show_server() is called

	//------------------------------------------------------------------------
	// METHODS

	public function __construct($map, $servers, $class)
	{
		// A simple constructor which just populates a few arrays.

		$this->t_start_grid = microtime(true);
		$this->c = $class;
		$this->servers = $servers;
		$this->map = $map;
		$this->cols = new Colours;

		$this->show_zones = (isset($_GET["h"]))
			? false
			: true;

		$this->get_key();

		// Sort out our fields

		$this->get_fields();
		$fields = array_merge(array("hostname"), $this->def_fields);

		if ($this->dyn_fields)
			$fields = array_merge($fields, $this->dyn_fields);

		// We may have static data. This is in ini file format, to make life
		// easier for me. If the file is there, read it and parse it. It
		// will be names class.audex, and in the EXTRA_DIR directory. We
		// want it as an associative array

		$stat_f = $this->map->get_path("extra_dir") . "/${class}.audex";

		if (file_exists($stat_f)) {
			$this->audex = parse_ini_file($stat_f, TRUE);

			// Fields in static files can contain an AFTER definition, which
			// tells us where to put that fields.

			foreach($this->audex as $xf=>$xd) {

				// If we already have this field, do nothing

				if (in_array($xf, $fields))
					continue;

				// We have an AFTER

				elseif (in_array("AFTER", array_keys($xd))) {
					$to_follow = $xd["AFTER"];

					// Do we have the field we're supposed to follow? If so,
					// put xf in the fields array. If not, tag it on the
					// end. There aren't many fields, so I don't feel too
					// bad about the for() loop

					if (in_array($to_follow, $fields)) {

						foreach($fields as $f) {
							$NEWF[] = $f;
							if ($f == $to_follow) $newf[] = $xf;
						}
						$fields = $newf;
					}
					else
						$fields[] = $xf;

				}

				// If there's no AFTER, just tag the field on the end

				else
					$fields[] = $xf;
			}

			$this->audex_keys = array_keys($this->audex);
		}

		// Remove hidden fields

		$fields = array_diff($fields, $this->hidden_fields);

		$this->fields = array_merge($fields, array("audit completed"));
	}

	public function get_parent_prop($zone, $class, $prop)
	{
		// Get a propery from a parent zone
		// $zone is the local zone you're looking at
		// $class is the audit class
		// $prop is the property/field name

		$p = $this->map->get_parent_zone($zone);

		if (isset($this->servers["$p/$p"][$class][$prop])) {
			$r = $this->servers["$p/$p"][$class][$prop];

			$r = (count($r) == 1)
				? $r[0]
				: $r;
		}
		else
			$r = false;

		return $r;
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

		unset($generic_key, $grid_key);

	}

	private function get_fields()
	{

		// We need to know how many fields to print, and what those fields
		// are. We are given a (big) hint by the $fields[] array, but the
		// user may have added new fields, which we need to find. We do this
		// by examining the servers[] array. While we're at it, we can find
		// any unused fields in the $def_fields array

		$all = array();

		foreach($this->servers as $zone=>$data) {

			if (isset($data[$this->type]))
				$all = array_merge($all, array_keys($data[$this->type]));
		}

		$all = array_unique(array_diff($all, array("hostname",
		"audit completed")));

		// We now have an array of all fields used in the audit files,
		// excepting hostname and audit completed.  We can do differences to
		// find out what fields in $def_fields aren't used, and what fields
		// to put into $dyn_fields

		$this->dyn_fields = array_diff($all, $this->def_fields);

		$this->def_fields = array_intersect($this->def_fields, $all);

	}

	protected function fold_line($str, $width = 25, $chars =
	'[\s|\-_:;,\.]')
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

			if ($j > $width && preg_match("/$chars/", $str[$i])) {
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

		$ret = $this->grid_head($width) .  $this->grid_body() .
		$this->grid_key() . $this->grid_foot();

		$this->t_end_grid = microtime(true);

		if (isset($this->map))
			$ret .= $this->display_timings();

		return $ret;
	}

	private function display_timings()
	{
		// Show the time taken to collect and process audit data

		$t_dc = round(($this->t_start_grid - $this->map->t_start_map), 3);
		$t_p = round(($this->t_end_grid - $this->t_start_grid), 3);

		return "\n\n<div class=\"t_info\">Data collection time: ${t_dc}s. "
		. "Data processing time: ${t_p}s.</div>";
	}

	public function grid_head($width)
	{
		// Open the table which holds the main grid

		return "\n<table class=\"audit\" width=\"$width\" cellpadding=\"1\" "
		. "cellspacing=\"1\" align=\"center\">" . $this->grid_header();
	}

	public function grid_header()
	{
		// Print the horizontal table column headers

		$ret_str = "\n<tr>";

		foreach($this->fields as $field) {

			$txt = (in_array($field, $this->dyn_fields))
				? "$field (+)"
				: $field;

			$ret_str .= "<th>$txt</th>";
		}

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

	protected function grid_key_header($cols = 1)
	{
		return "\n\n<tr><td class=\"keygap\" colspan=\"$cols\">&nbsp;</td>"
		. "</tr>\n\n<tr><td class=\"keyhead\" colspan=\"$cols\">"
		. "grid key</td></tr>\n";
	}

	protected function grid_key()
	{
		// Put in the key. The information for it is held in a file specific
		// to the page using the class right now. It populates the same
		// fields as the data above.

		// There's also a generic key that goes on every page

		$cols = count($this->fields);

		$ret = $this->grid_key_header($cols);

		// Loop through the grid_key data, filling in columns as we go. Each
		// cell can have arbitrarily many key values

		$ret .= "\n<tr>";

		foreach($this->fields as $field) {

			// If we've used a static file, say so in the key

			if (isset($this->audex_keys) && in_array($field,
			$this->audex_keys))
				$this->grid_key[$field][] = array("data from static file",
				"solidpink", false);

			$ret .= (in_array($field, array_keys($this->grid_key)))
				? $this->grid_key_col($this->grid_key[$field])
				: new Cell();
		}

		return $ret . "</tr>";
	}

	protected function grid_key_col($data, $span = 1)
	{
		// prints columns in grid keys

		return new listCell($data, false, $span, true);
	}

	public function show_server($server)
	{
		// Display the HTML for a single server and all its zones, if
		// necessary

		// Set the audit file version - we may need it

		$this->af_ver = $this->map->get_af_ver($server);

		// Now every zone name is prefixed by the physical host name

		$fetch = "$server/$server";

		if (isset($this->servers[$fetch][$this->c]))
			$ret = $this->show_zone($this->servers[$fetch][$this->c]);
		else
			return $this->erred_zone_print(array("hostname"=> array($server),
			"_err_"=> array("missing data for $fetch/$this->c")));

		$zl = (defined("NO_ZONES"))
			? false
			: $this->map->list_server_zones($server);

		if (is_array($zl)) {

			foreach($this->map->list_server_zones($server) as $zone) {
				$zname = preg_replace("/\..*/", "", "${server}/$zone");

				if (isset($this->servers[$zname][$this->c])) {
					$ret .= $this->show_zone($this->servers[$zname][$this->c]);
				}
				else {
					page::warn("Can't get data for '$server/$zone'.");
				}

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

		$this->cz =& $data;

		if (is_array($data)) {

			// We always have the zone name as [hostname];

			$z = $data["hostname"][0];

			// use the map to find out if this is a host or a zone. We
			// alternate colours with "a" and "b" to make it easier to read
			// across the grid

			if ($this->map->is_global($z) && $this->lc != "sa")
				$row_class = "sa";
			elseif ($this->map->is_global($z))
				$row_class = "sb";
			elseif ($this->lc != "za")
				$row_class = "za";
			else
				$row_class = "zb";

			$ret_str = "\n<tr class=\"$row_class\">";

			$this->lc = $row_class;

			// Zones which aren't in the running state should only have
			// three elements in their array. Handle those as a special case

			if ($this->erred_zone($data))
				return $this->erred_zone_print($data) . "</tr>";
			elseif ($this->non_running_zone($data))
				$ret_str .= $this->non_running_zone_print($data);
			elseif ($this->lx_zone($data))
				$ret_str .= $this->lx_zone_print($data);
			else {

				// We already know what fields we want to display, so loop
				// through them and see if the data[] array contains a value
				// for each of them. If it does, look to see if there's a
				// special method for handling that data

				$dk = array_keys($data);

				foreach($this->fields as $field) {

					if (in_array($field, $dk)) {

						$method = preg_replace("/\W/", "_", "show_$field");

						if (method_exists($this, $method)) {
							$ret_str .= $this->$method($data[$field]);
						}
						else
							$ret_str .= $this->show_generic($data[$field],
							$field);
					}

					// There may be static data

					elseif (isset($this->audex_keys) && in_array($field,
					$this->audex_keys) && isset($this->audex[$field][$z])) {
						$ret_str .= new Cell ($this->audex[$field][$z],
						"solidpink");
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

				$err_str = (filesize($data) == 0)
					? "zero size file _ERR_"
					: "wrong format file _ERR_";

			}
			elseif(preg_match("/^\./", $data))
				$err_str = "Missing information for global zone. Local zones
				below.";
			else
				$err_str = "expected data not found _ERR_";

			$ret_str = "\n<tr>" . new Cell("ERROR: " . str_replace("_ERR_",
			"[${data}]", $err_str), "error", false, false,
			sizeof($this->fields)) . "</tr>";
		}

		return $ret_str;
	}

	protected function erred_zone($data)
	{
		// A check to see if a zone is running or not

		return isset($data["_err_"])
			? true
			: false;
	}

	protected function erred_zone_print($data)
	{
		// This function informs the user that a zone audit failed part-way
		// through. There probably won't even be an audit completed field

		return "\n<tr>" . $this->show_hostname($data["hostname"], "error")
		. new Cell("Audit errored. Message was &quot;" . $data["_err_"][0] .
		"&quot;", "error", false, false, (sizeof($this->fields) - 1)) .
		"</tr>";
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
		// This function informs the user that we got a zone with no audit
		// data. This probably means it's not in the "running" state, but it
		// could also be an lx branded zone.

		return $this->show_hostname($data["hostname"])
		. new Cell("No information. Zone is in &quot;"
		. $data["zone status"][0] .  "&quot; state.", "znoinf", false,
		false, (sizeof($this->fields) - 2))
		. $this->show_audit_completed($data["audit completed"]);
	}

	protected function lx_zone($data)
	{
		// Check whether we have an LX branded zone, which requires special
		// handling

		return (isset($data["zone brand"]))
			? true
			: false;
	}

	protected function lx_zone_print($data)
	{
		return $this->show_hostname($data["hostname"])
		. new Cell("LX branded zone in &quot;" . $data["zone status"][0] .
		"&quot; state.", "zlx", false, false, (sizeof($this->fields) - 2))
		. $this->show_audit_completed($data["audit completed"]);
	}

	protected function indent_print($keyval, $keycol = false)
	{
		// Print indented data for NICs, filesystems, exports

		$ret = "\n<div class=\"indent\">";

		foreach($keyval as $key=>$val) {
			$ret .= "\n  <div";

			if (isset($keycol[$key]))
				$ret .= " class=\"$keycol[$key]\"";

			$ret .= "><strong>${key}:</strong> $val</div>";

		}

		return $ret . "\n</div>";
	}

	//------------------------------------------------------------------------
	// show_property() functions

	public function show_generic($data, $field = false, $subname = false)
	{
		// This function prints properties which don't have a dedicated
		// function. It can handle none, one or many rows of data.

		if (is_string($data))
			$ret_str = new Cell($data);
		else {
			if (sizeof($data) == 1)
				$ret_str = new Cell($data[0]);
			elseif(sizeof($data) > 1)
				$ret_str = new listCell($data);
			else
				$ret_str = new Cell();
		}

		return $ret_str;
	}

	protected function show_hostname($data, $c = false)
	{
		// Coloured according to virtualization

		$z = $data[0];

		// The row background is set for either global or local zones

		if ($this->map->is_global($z)) {
			$row_class = "server";
			$zname = "${z}/$z";
			$parent = false;
		}
		else {
			$row_class = "zone";
			$parent = $this->map->get_parent_zone($z);
			$zname = "${parent}/$z";
		}

		// The hostname cell is coloured according to the virtualization (or
		// lack of it)

		// Get the virtualization. We always have this because we always
		// have platform audit data

		$v = (isset($this->servers[$zname]["platform"]["virtualization"]))
			? $this->servers[$zname]["platform"]["virtualization"][0]
			: "unknown";

		// The basic visualization type is vb - this is all we need for most
		// things, but we may need to refer back to $v for more info

		$vt = preg_replace("/ \(.*$/", "", $v);

		// Now work out the class. Default to "unknown".

		$c = "unk";

		if ($vt == "none") // physical server
			$c = "phys";
		elseif ($vt == "zone") {

			if ($v == "zone (sparse/native)")
				$c = "szone";
			elseif ($v == "zone (whole root/native)")
				$c = "lz";
			elseif (preg_match("/solaris[189]/", $v))
				$c = "bzone";
			else
				$c = "lz";
		}
		elseif ($vt == "VirtualBox")
			$c = "vbox";
		elseif ($vt == "primary LDOM")
			$c = "ldmp";
		elseif ($vt == "guest LDOM")
			$c = "ldm";
		elseif ($vt == "VMware")
			$c = "vmware";
		elseif ($vt == "xVM dom0")
			$c = "dom0";
		elseif ($vt == "xVM domU")
			$c = "domu";
		elseif ($vt == "KVM guest")
			$c = "kvm";
		elseif ($vt == "unknown") {

			// unknown could be branded, or not-running zones

			if (isset($this->servers[$z]["platform"]["zone brand"]))
				$c = "bzone";
			elseif (isset($this->servers[$z]["platform"]["zone status"]))
				$c = "lz";

		}

		if ($c)
			$class = "error";
		elseif ($this->map->is_global($data[0]))
			$class = "serverhn";
		else
			$class = "zonehn";

		// All hostnames are clickable links to a single-server view

		$zlink = new singleServerLink($z, false, $parent);

		return new Cell($zlink, $c);
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
			$now = time();

			// Flag up audits done in the future or impossibly early - help
			// people catch machines with the wrong time

			if($date < LOWEST_T) {
				$class = "solidorange";
				$date_str = "$t_arr[1]<div class=\"strl\">IMPOSSIBLY OLD</div>";
			}
			elseif($date > $now) {
				$class = "solidorange";
				$date_str = "$t_arr[1]<div class=\"strl\">FUTURE TIME</div>";
			}
			elseif (($now - $date) < 86400) {
				$date_str = false;
				$class = false;
			}
			elseif(($now - $date) < 680400) {
				$date_str = "yesterday";
				$class = "solidamber";
			}
			else {
				$date_str = "$t_arr[1]";
				$class = "solidred";
			}
		}
		else
			$time_str = $date_str = $col = false;

		// Put a line break in if we're not in single server mode

		if (!defined("SINGLE_SERVER") && $date_str)
			$date_str = "<div>${date_str}</div>";

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

				$txt = ($a[1] == "physical")
					? "<strong>$sb</strong> $a[1]"
					: "$sb $a[1]";

				$class = false;
			}

			$c_arr[] = array($txt, $class);
		}

		return new listCell($c_arr);
	}

	protected function show_serial_number($data)
	{
		// Print the serial number, on red if it's "TIMED OUT"

		$sn = $data[0];

		$class = ($sn == "TIMED OUT" || $sn == "UNSPECIFIED")
			? "solidred"
			: false;

		return new Cell($sn, $class);
	}

	protected function show_hardware($data)
	{
		// Print the hardware platform. Some things don't report exactly
		// what is printed on the front, so we look up the name in the
		// hw_names[] array

		// Put 32-bit OSes on an amber field.  Outline SPARC and XVM

		preg_match("/^(.*) \((.*)\)(.*)$/", $data[0], $a);

		$hw = (in_array($a[1], array_keys($this->hw_db)))
			? $this->hw_db[$a[1]]
			: $a[1];

		$class = (preg_match("/^32-bit/", $a[2]))
			? "solidamber"
			: false;

		if ($a[1] == "i86xpv")
			$frame = $this->cols->icol("box", "xen", "plat_cols");
		elseif(preg_match("/SPARC/", $a[2]))
			$frame = $this->cols->icol("box", "sparc", "plat_cols");
		else
			$frame = false;

		// Put line breaks in if we're not in single server mode

		$arch = (defined("SINGLE_SERVER"))
			? " ($a[2])"
			: "<div>($a[2])</div>";

		$cluster = (!empty($a[3]))
		 	? $a[3]
			: false;

		if (!defined("SINGLE_SERVER"))
			$cluster = "<div>$cluster</div>";

		return new Cell("${hw}${arch}${cluster}", $class, $frame);
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
			// put non-native zones on an amber field. We now consider
			// "solaris" brand zones native

			$za = explode("/", preg_replace("/zone \((.*)\)/", "\\1", $vz));

			// za has elements [0] => whole/spare root, [1]=> brand

			// Whole root zones are highlighted in red. They can be of a
			// "non-native" brand. Sparse zones cannot.

			if ($za[0] == "whole root") {
				$class = "boxred";
				$str = "whole root zone";

				// Have to colour this with inline style - we've probably
				// already used the class doing a box

				if ($za[1] != "native" && $za[1] != "solaris") {
					$col = $this->cols->icol("solid", "amber");
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

	protected function show_OBP($data)
	{
		// Colour latest green, others red, but only if $this->latest_obps
		// is set

		if (!isset($this->latest_obps))
			return $this->show_generic($data);

		$hw = $this->cz["hardware"][0];

		if (in_array($hw, array_keys($this->latest_obps))) {
			$lobp = $this->latest_obps[$hw];

			$class = ($data[0] == $lobp)
				? "ver_l"
				: "ver_o";
		}
		else
			$class = false;

		return new Cell($data[0], $class);
	}

	protected function show_ALOM_F_W($data)
	{
		// Colour latest green, others red

		if (!isset($this->latest_aloms))
			return new Cell($data[0]);

		$hw = $this->cz["hardware"][0];

		if (in_array($hw, array_keys($this->latest_aloms))) {
			$lalom = $this->latest_aloms[$hw];

			$class = ($data[0] == $lalom)
				? "ver_l"
				: "ver_o";
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

			$ct = ($guess)
				? "boxnet"
				: "net";

			// If we have an alom colour, use that. If not, try to get a
			// subnet colour

			if ($this->cols->get_col("alom", "nic_cols"))
				$class = $ct . "alom";
			else {
				$sn = PlatformGrid::get_subnet($data[0]);

				$class = ($sn)
					? $ct . preg_replace("/\./", "", $sn)
					: false;

			}

			$c = new Cell($data[0], $class);
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

		$physical = ($physical == 1)
			? ""
			: "$physical x ";

		$speed = preg_replace("/M.*$/", "", $speed);

		$speed = ($speed >= 1000)
			? round($speed / 1000, 1) . "GHz"
			: "${speed}MHz";

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

					// Special case for iSCSI

					$class = (preg_match("/iSCSI/", $datum))
						? "iscsi"
						: "disk";

					if (!preg_match("/unknown/", $datum)) {
						$parts = explode(" ", $datum, 5);
						$inb = units::to_b($parts[3]);
						$datum = "${type}: $parts[1] x ".
						units::from_b($inb) . " " . $parts[4];
					}

					break;

				case  "CD/DVD":

					// CD/DVD has a coloured field indicating its state

					$class = "cd";

					if (preg_match("/\(loaded\)/", $datum))
						$ic = $this->cols->icol("solid", "amber");
					elseif (preg_match("/\(mounted\)/", $datum))
						$ic = $this->cols->icol("solid", "green");
					break;

				case "tape":
					$class = "tp";
					break;

				case "FC array":
					$class = "fc";
					break;

				case "RAID vol":
					$class = "rvol";
					break;

				default:
					$class = false;

			}

			$c_arr[] = array(preg_replace("/^(.*):/",
			"<strong>\\1</strong>:", $datum), $class, $ic);
		}

		return new listCell($c_arr, "smallaudit", false, 1);
	}

	protected function show_printer($data)
	{
		// Put the default printer in a green box

		foreach($data as $datum) {

			if (preg_match("/\(default\)/", $datum)) {
				$class = "boxgreen";
				$txt = preg_replace("/ .*$/", "", $datum);
			}
			else {
				$class = false;
				$txt = $datum;
			}

			$c_arr[] = array($txt, $class);
		}

		return new listCell($c_arr);
	}

	protected function show_card($data)
	{
		// A system can only have PCI or SBUS. It can't have both. PCI
		// format changed in 3.2.

		// Watch out for multiple exit points!

		$data = array_unique($data);
		$unique = array();

		if (preg_match("/SBUS/", $data[0]))
			$processed = $this->parse_cards_sbus($data);
		elseif($this->af_ver < 3.2)
			$processed = $this->parse_cards_pci_legacy($data);
		else {

			// We need a suitable class to process the raw prtdiag
			// information. Work out what that class should be called and,
			// if we have it, tell parse_cards_pci() to use it. If we don't,
			// issue a warning

			$pci_class = "pci_" . strtolower(preg_replace("/\W+/", "",
			preg_replace("/ \(.*$/", "", $this->cz["hardware"][0])));

			if (class_exists($pci_class))
				$processed = $this->parse_cards_pci($data, $pci_class);
			else
				return new Cell("No PCI info.<br/>(Require class
				'$pci_class'.)", "solidamber");
		}

		// If we got no processed data back, something went wrong - not
		// necessarily! Now we have better filtering, there might just be no
		// cards!

		//if (count($processed) == 0 && count($data) > 0)
			//return new Cell("cannot process data", "error");

		$c_arr = array();

		foreach($processed as $card) {

			// each card is an array which looks like this:
			//
			// [bus]      the bus type - sbus/pci/pcie/pci-x
			// [c_type]   the card type. Normally this is something generic
			//            like "network", but with legacy data, can also be
			//            the model name.
			// [c_model]  the model name of the card. Often not available
			// [c_loc]    the slot the card is in Sometimes has the
			//            backplane or side
			// [c_name]   the name prtdiag gives the card. e.g. SUNW,pci-ce
			// [c_hz]     the speed the card is running at, in MHz

			// We may have too much information. For instance, network cards
			// with multiple ports show up once for each port, which is
			// confusing. We can't do array_unique() on an associative
			// array, so we have to do it ourselves

			$str = implode($card);

			if (in_array($str, $unique)) continue;

			$unique[] = $str;

			// Look up the card model name in card_db to see if we have a
			// better description of it. If we do, print that in bold face
			// with the card model in brackets after it. If not, put the card
			// name in bold with no brackets. Failing that, promote the card
			// name

			$bold = $c_model = $light = "";

			$bus = strtolower($card["bus"]);

			$c_model = isset($card["c_model"])
				? $card["c_model"]
				: false;

			// For the card db, it's either SBUS or PCI

			$db_bus = ($bus == "sbus")
				? $bus
				: "pci";

			if (empty($c_model) && isset($card["c_name"])) {
				$bold = $card["c_name"];
				$name_used = true;
			}
			elseif (in_array($c_model, array_keys($this->card_db[$db_bus]))) {
				$bold = $this->card_db[$db_bus][$c_model];
				$light = "($c_model)";
			}
			else
				$bold = $c_model;

			$txt = "<strong>$bold</strong>";

			if (isset($light)) $txt .= " $light";

			$txt .= "<br/>";

			if (!isset($name_used) && isset($card["c_name"]))
				$txt .= $card["c_name"];

			if (isset($card["c_type"]))
				$txt .= " " . $card["c_type"];

			if (isset($card["c_loc"]))
				$txt .= " " . $card["c_loc"];

			if (!empty($card["c_hz"])) {
				$txt .= "@" . $card["c_hz"];

				if (!preg_match("/mhz|lane/i", $card["c_hz"])) $txt .= "MHz";
			}

			$c_arr[] = array($txt, strtolower($card["bus"]));
		}

		return new listCell($c_arr, "smallaudit", false, 1);
	}

	protected function parse_cards_sbus($data)
	{
		// Make an array of SBUS card information that can be formatted in a
		// nice, easy to read way. Input of the following form:
		// "card" "$bus (SBUS slot $slot)"

		$ret_arr = array();

		foreach($data as $card) {

			preg_match("/^(\S+) \((.*)\)$/", $card, $a);

			$ret_arr[] = array(
				"bus" => "sbus",
				"c_model" => $a[1],
				"c_loc" => $a[2]);
		}

		return $ret_arr;
	}

	protected function parse_cards_pci_legacy($data)
	{
		// Try to parse pre 3.2 PCI card information. This isn't perfect,
		// but neither is the data that it's given. It'll do. Returns an
		// associative array of info about the card

		$ret_arr = array();

		foreach($data as $card) {

			preg_match("/^(\S+) \((\S+)(.*)\)(.*)$/", $card, $a);

			// Lines like this
			// (netw+ pci-bridge (pci-pci8086,b154.0/network 61/B /IO01@66MHz)
			// are discarded. For now I don't care, because I don't think I
			// want them

			if (count($a) < 2) continue;

			$arr = array(
				"bus" => "pci",				// pci - pre 3.2 couldn't
											// distinguish between PCI, PCIE
											// and PCI-X
				"c_type" => trim($a[1]),	// e.g. "network"
				"c_model" => trim($a[4]),	// e.g. "
				"c_loc" => trim($a[3]),		// the card's location. Can be
											// side/slot, or just slot
				"c_name" => trim($a[2])		// e.g. SUNW,emlxs
				);

			if (preg_match("/@/", $a[3])) {
				$b = explode("@", $a[3]);
				$arr["c_loc"] = $b[0];
				$arr["c_hz"] = $b[1];
			}

			$ret_arr[] = $arr;
		}

		return $ret_arr;
	}

	protected function parse_cards_pci($data, $class)
	{
		// Parse raw PCI info from prtdiag. prtdiag output varies from
		// machine to machine, so the client no longer tries to process it.
		// We receive raw prtdiag output, and a class exists for the machine
		// in the pci_classes.php file which turns that information into an
		// array. If there's no class, a warning is printed by the
		// show_card() method.

		$ret_arr = array();

		foreach($data as $card) {

			$mach = preg_replace("/ \(.*$/", "", $this->cz["hardware"][0]);
			$mach = strtolower(preg_replace("/\W+/", "", $mach));

			$pci_class = "pci_$mach";

			// We've already checked the pci class exists

			$x = new $pci_class($card);

			if ($info = $x->get_info())
				$ret_arr[] = $info;

		}

		return $ret_arr;
	}

	protected function show_multipath($data)
	{
		// Display multipathing info. Bold the first word, which is the type
		// of multipathing

		$data = preg_replace("/^(\w+)/", "<strong>$1</strong>", $data);

		return $this->show_generic($data);
	}

	protected function show_eeprom($data)
	{
		// display EEPROM settings and devaliases

		$c_arr = array();

		foreach($data as $datum) {

			if (preg_match("/^devalias/", $datum)) {
				$a = explode(" ", $datum);
				$class = "deva";
				$txt = "<strong>$a[1]</strong> $a[2]";
			}
			else {
				$class = "parm";
				$a = explode("=", $datum);
				$txt = "<strong>$a[0]</strong>=$a[1]";
			}


			$c_arr[] = array($txt, $class);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	//-- o/s -----------------------------------------------------------------

	protected function show_version($data)
	{
		// In a local zone, if the version is not the same as the parent
		// zone, box it in amber

		$zn = $this->cz["hostname"][0];
		$class = false;

		if (!empty($this->servers) && !$this->map->is_global($zn)) {

			if ($this->get_parent_prop($zn, "os", "version") !=
			preg_replace("/ zone$/", "", $data[0]))
				$class = "boxamber";
		}

		return new Cell($data[0], $class);
	}

	protected function show_release($data)
	{
		// Show the operating system version and revision. For normal
		// Solaris we get this in a "5.10 10/09" style, which doesn't mean a
		// lot to some people, so here we convert it into more sensible
		// marketing type strings. We also flag up zones with different
		// releases to their parents

		$zn = $this->cz["hostname"][0];
		$class = false;
		$os_hr = $data[0];

		// If we've got something in the array above, translate it. We need
		// the Solaris revision first.

		preg_match("/^.*SunOS ([\d.]+).*$/", $this->cz["version"][0], $vi);

		if (isset($vi[1])) {
			$sv = $vi[1];

			if (in_array($sv, array_keys($this->sol_upds))) {

				if (in_array($os_hr, array_keys($this->sol_upds[$sv])))
					$os_hr .= "<div>(" . $this->sol_upds[$sv][$os_hr] .
					")</div>";
			}

		}

		// If we're a zone, check to see if we have the same O/S as the
		// parent

		if (!empty($this->servers) && !$this->map->is_global($zn)) {
			if ($this->get_parent_prop($zn, "os", "release") !=
			preg_replace("/ zone$/", "", $data[0]))
				$class = "boxamber";
		}

		return new Cell($os_hr, $class);
	}

	protected function show_hostid($data)
	{
		// if a zone has a different hostid to its parent, we hightlight it

		$zn = $this->cz["hostname"][0];
		$id = $data[0];
		$class = false;

		if (!empty($this->servers) && !$this->map->is_global($zn)) {

			if ($this->get_parent_prop($zn, "os", "hostid") != $id)
				$class = "boxamber";

		}

		return new Cell($id, $class);

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
			$hm = explode(":", $up);
			$up = (60 * $hm[0]) + $hm[1];
		}

		return round($up);
	}

	protected function show_uptime($data)
	{

		$up = $this->uptime_in_m($data[0]);
		$class = false;
		$zn = $this->cz["hostname"][0];

		// If this is a local zone, get the parent's uptime also

		if (!empty($this->servers) && !$this->map->is_global($zn))
			$pu = $this->uptime_in_m($this->get_parent_prop($zn, "uptime",
			"os"));

		// Flag the box amber if uptime is less than a day. Put an amber
		// border round the cell if this is a zone and it's been rebooted
		// more recently than the global. Reboot gets priority

		if (isset($pu) && $up < $pu)
			$class = "boxamber";
		elseif ($up < 1440)
			$class = "solidamber";

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

	protected function show_boot_env($data)
	{
		// Display boot environments. "Active now" in a green box, active
		// on reboot in red

		foreach($data as $row) {
			$class = false;

			if (preg_match("/^failsafe:/", $row))
				preg_match("/^(\w+): [\"']([^\"']*)[\"'] \((.*)\)$/", $row, $a);

				// [1] => "failsafe"
				// [2] => BE name
				// [3] => miniroot path

			else
				preg_match("/^(\S+): (.*) \((.*)\) \[(\S*)\]$/", $row, $a);

				// [1] => BE type
				// [2] => BE name
				// [3] => LU="(in)complete"/beadm=mount point
				// [4] => flags

			if (!isset($a[2]))
				echo "<h2>$row</h2>";
			$txt = "<strong>$a[2]</strong> ($a[1])";

			if ($a[1] == "failsafe")
				$id["path"] = $a[3];
			else {

				if (!empty($a[4]))
					$id["flags"] = $a[4];

				if (preg_match("/N/", $a[4])) {
					$class = "boxgreen";
				}
				elseif (preg_match("/R/", $a[4]))
					$class = "boxred";

				if ($a[1] == "LU") {
					$id["state"] = $a[3];

					if ($a[3] == "incomplete")
						$class = "solidamber";
				}
				else
					$id["mpt"] = $a[3];

			}

			$c_arr[] = array($txt . $this->indent_print($id), $class);

		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	protected function show_kernel($data)
	{
		// We used to only print the kernel in global zones, because local
		// zones would always have the same kernel as the the global. But
		// with the SUNWsolaris10 brand, that's no longer the case. We also
		// now colour the kernel version squares when we do O/S audits

		$zn = $this->cz["hostname"][0];
		$kr = $data[0];
		$col = false;

		if (!isset($this->latest_kerns))
			return $this->show_generic($kr);

		$osver = $this->mk_ver_arch_str($zn, $this->cz["distribution"][0],
		$this->cz["version"][0]);

		// Don't colour "virtual" kernels at all
		if ($kr == "Virtual")
			$class = false;
		elseif (in_array($osver, array_keys($this->latest_kerns)))

			$class = ($kr == $this->latest_kerns[$osver])
				? "ver_l"
				: "ver_o";
		else
			$class = "solidamber";

		// Now look to see if the kernel is the same as the parent

		if (!empty($this->servers) && !$this->map->is_global($zn) &&
			($data[0] != $this->get_parent_prop($zn, "os", "kernel")))
			$col = $this->cols->icol("box", "amber");

		return new Cell($data[0], $class, $col);
	}

	protected function show_smf_services($data)
	{
		// show the SMF service counts
		// input of the form
		//   156 installed (97 online, 1 in maintenence)

		$class = false;

		preg_match("/^(\d+) installed \((\d+) online[, ]*(.*)\)/", $data[0],
		$a);

		if (count($a) != 4)
			return new Cell("<strong>ERROR</strong>", "solidred");

		// a[1] is the total number of services
		// a[2] is the number of online services
		// a[3] may by "x in maintenence"

		$txt = "<ul><li><strong>$a[2] online</strong></li>"
		. "<li>$a[1] installed</li>";

		if ($a[3]) {
			$class = "solidred";
			$txt .= "<li>$a[3]</li>";
		}

		return new Cell($txt . "</ul>", $class);
	}

	protected function show_packages($data)
	{
		// Print the number of packages installed in the zone, and highlight
		// in amber if any of them are only partially installed. Break the
		// cluster out on to a separate line, if it's there

		$data = preg_replace("/^(\d+)/", "<strong>$1</strong>", $data[0]);

		$class = preg_match("/partial/", $data)
			? "solidamber"
			: false;

		if (preg_match("/\/SUNW/", $data))
			$data = preg_replace("/\/(\w*)\]/", "]<div>$1</div>", $data);

		return new Cell($data, $class);
	}

	protected function show_publisher($data) {
		return $this->show_repository($data);
	}

	protected function show_repository($data)
	{
		// Publisher info. Put the preferred publisher in a green box, and
		// link to the repository

		foreach($data as $row) {

			// Hack to handle Joyent pkgsrc repos, which just show up as a
			// URI

			if (preg_match("/^http/", $row)) {
				$c_arr[] = array("<strong>pkgsrc repo</strong> ($row)");
			}
			else {
				$a = explode(" ", $row);

				$class = (sizeof($a) == 3)
					? "boxgreen"
					: false;

				$url = preg_replace("/[\(\)]/", "", $a[1]);
				$lt = preg_replace("/^\(http:\/\/|\/\)$/", "", $a[1]);

				$c_arr[] = array("<strong>$a[0]</strong> "
				. "(<a href=\"$url\">$lt</a>)", $class);
			}
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	protected function show_patches($data)
	{
		// If this is a local zone, get the number of patches in the global
		// zone and compare.

		$class = false;

		if (!empty($this->servers) &&
			!$this->map->is_global($this->cz["hostname"][0])) {
			$p= $this->map->get_parent_zone($this->cz["hostname"][0]);

			if (isset($this->servers[$p]["os"]["patches"][0])) {
				$ppn = $this->servers[$p]["os"]["patches"][0];

				if ($data[0] < $ppn)
					$class = "solidamber";
			}

		}

		return new Cell($data[0], $class);
	}

	protected function show_vm($data)
	{
		// Show virtual machine information in a table. This could be local
		// zones, LDOMs, VirtualBoxes or Xen domains

		// Input is of the form:
		// vm_type: vm_name (os_type:state) [extra_1] /zone/path

		// The colour of the border (the cell class) shows you what the VM
		// is, the field (inline style) gives you the state.

		foreach($data as $row) {
			$id = $idc = array();
			unset($res);
			preg_match("/^([^:]+): (.*) \(([^:]+):(.*)\) \[(.*)\](.*)$/U",
			$row, $a);

			// Gives us an array of the form:
			// [1] => VM type
			// [2] => VM name
			// [3] => VM O/S type (brand, O/S), or console port for LDOMs
			// [4] => VM state (running, shut off etc.)
			// [5] => resources as CPU/memory
			// [6] => zone path for local zones, blank for others

			// Catch errors

			if (count($a) != 7) {
				$call[] = array("ERROR: $row", "error");
				continue;
			}

			$txt = "<strong>" . $zlink = new singleServerLink($a[2], false,
			$this->get_parent_prop($a[2], "platform", "hostname")) .
			"</strong> ($a[1])";

			$id["state"] = $a[4];

			if (!empty($a[5]))
				$b = explode("/", $a[5]);

			switch($a[1]) {

				case "local zone":

					// Add on the zone path.

					$id["brand"] = $a[3];
					$id["path"] = $a[6];

					$class = ($a[3] != "native" && $a[3] != "ipkg")
						? "bzone"
						: "lzone";

					// If we have resource caps, deal with them.

					if (!empty($a[5])) {
						$c = explode(",", $a[5]);

						foreach($c as $key) {
							$d = explode("=", $key);
							$id[$d[0]] = $d[1];
						}
					}

					break;

				case "xVM":

					// XEN domain. Virtualization type? Change "linux" to
					// "paravirtualized".

					$id["type"] = ($a[3] == "linux")
						? "paravirtualized"
						: $a[3];

					$class = ($a[2] == "Domain-0")
						? "dom0"
						: "domu";

					$id["CPU"] = "x " . str_replace(" CPU", "", $b[0]);

					// Memory is in "xx kB". Convert it.

					if ($b[1] != "no limit")
						$id["memory"] =
						units::from_b(units::to_b(str_replace(" kB", "kb",
						$b[1])));

					break;

				case "VBox":
					$class = "vbox";
					$id["CPU"] = "x " . str_replace(" CPU", "", $b[0]);
					$id["memory"] = units::from_b(units::to_b($b[1]));
					break;

				case "LDOM":
					$class = "ldm";
					$cons = ($a[3] == "port SP")
						? "SP"
						: $a[3];

					$id["console"] = $cons;
					break;

				default:
					$call[] = array("ERROR: $row", "error");
					continue;
			}

			if (isset($res))
				$id["resource"] = $res;

			// colour according to the state of the VM. We may also add some
			// explanatory text

			switch($a[4]) {

				case "down":			// lx brand zones
				case "incomplete":		// local zones
				case "aborted":			// VBox
					$sc = "solidred";
					break;

				case "running":
					$sc = "solidgreen";
					break;

				case "idle":			// xVM
				case "configured":		// local zone
					$sc = "solidamber";
					break;

				case "shut off":		// xVM
				case "installed":		// local zone
				case "powered off":		// VBox
				case "bound":			// LDOM
					$sc = "solidorange";
					break;

				default:
					$sc = false;
			}

			$idc["state"] = $sc;
			$c_arr[] = array($txt . $this->indent_print($id, $idc), $class);

		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	//-- Networking //-------------------------------------------------------

	protected function show_NTP($data)
	{
		// Highlight preferred NTP servers, and if the machine itself is a
		// server

		$c_arr = array();

		foreach($data as $datum) {

			if ($datum == "acting as server")
				$class = "solidorange";
			elseif (preg_match("/preferred server/", $datum))
				$class = "solidgreen";
			else
				$class = "false";

			$col = (preg_match("/not running/", $datum))
				? $this->cols->icol("box", "red")
				: false;

			$c_arr[] = array(preg_replace("/ \(.*$/", "", $datum), $class,
			$col);
		}

		return new listCell($c_arr);
	}

	protected function show_name_service($data)
	{
		return new listCell(preg_replace("/^(.*:)/", "<strong>$1</strong>",
		$data), "auditl");
	}

	protected function show_name_server($data)
	{
		foreach ($data as $datum) {
			$a = preg_split("/\s/", $datum);

			// a[0] is the name service (DNS, NIS etc)
			// a[1] is the type of server (master, slave)
			// a[2] is explanatory text

			if ($a[0] == "NIS")
				$txt = "$a[2] (<strong>$a[0]</strong>)<br/>$a[1]";
			elseif($a[0] == "DNS")
				$txt = "$a[2] (<strong>$a[0]</strong>)";

			if (preg_match("/master/", $a[1]))
				$class = "solidgreen";
			elseif (preg_match("/slave/", $a[1]))
				$class = "solidamber";
			else {

				// Some things, like DNS stubs, aren't slave or master, so
				// don't colour the cell, and say what they are

				$class = false;
				$txt .= " $a[1]";
			}

			$c_arr[] = array($txt, $class);
		}

		return new listCell($c_arr, "smallaudit");
	}

    protected function show_port($data)
	{
		// List open ports. Non-"expected" ports are on an amber field.
		// Inetd ports are boxed in red.

		$call = array();

		foreach($data as $datum) {
			$a = explode(":", $datum);

			// a[0] is the port number
			// a[1] is the /etc/services entry
			// a[2] is the process

			// We may not be displaying high-numbered ports

			if ((defined("OMIT_PORT_THRESHOLD")) && ($a[0] >
			OMIT_PORT_THRESHOLD))
				continue;

			$txt = "<strong>$a[0]</strong> (";

			// If this port is in the "usual ports" array, don't highlight
			// it

			$class = in_array($a[0], $GLOBALS["usual_ports"])
				? false
				: "solidamber";

			$col = ($a[2] == "inetd")
				? $this->cols->icol("box", "red")
				: false;

			$txt .= ($a[1] != "")
				? "$a[1]/"
				: "-/";

			$txt .= ($a[2] != "")
				? "$a[2])"
				: "-)";

			$c_arr[] = array($txt, $class, $col);
		}

		return new listCell($c_arr);
	}

	protected function show_route($data)
	{
		// Look at routes

		foreach($data as $datum) {
			$a = explode(" ", $datum);
			$class = false;

			if ($a[0] == "default") {

				// Default routes. Just print the route and (default) after
				// it. If not in /etc/defaultrouter then a[2] will say so,
				// and we put it on an amber field

				$txt = "<strong>default:</strong> $a[1]";

				if (isset($a[2]))
					$class = "solidamber";

			}
			else {

				// normal routes. Print network: gateway and put the
				// interface after, if we have it. If it's a persistent
				// route a[2] will say so, and we put it in a green box

				$txt = "<strong>$a[0]</strong>&nbsp;->&nbsp;$a[1]";

				// a[2] can be "persistent" or an interface name

				if (isset($a[2])) {

					if ($a[2] == "(persistent)")
						$class = "boxgreen";
					else
						$txt .= "&nbsp;$a[2]";

				}

			}


			$c_arr[] = array($txt, $class);
		}


		return new listCell($c_arr, "smallauditl");
	}

	private function format_mac_addr($mac)
	{
		// Take a MAC address and make all the octets two digits

		$octets = explode(":", $mac);
		$mac = "";

		foreach($octets as $o) {
			$mac .= (strlen($o) == 1)
				? "0${o}:"
				: "${o}:";
		}

		return preg_replace("/:$/", "", $mac);
	}

	public function show_net($nic_arr)
	{
		// $data is an array of NIC lines in machine-parseable format. Each
		// element is a network object of some kind

		// The info is presented as a "|" delemited string which, when
		// exploded, gives the following elements (s-audit.sh names in
		// brackets)
		//
		//   [0] - device name ($nic)
		//   [1] - device type ($type)
		//   [2] - IP address ($addr)
		//   [3] - MAC address ($mac)
		//   [4] - host/zone name ($hname)
		//   [5] - link speed as speed-duplex ($speed)
		//   [6] - underlying NIC for aggregates/vsw etc ($over)
		//   [7] - IPMP group ($ipmp)
		//   [8] - extra info ($xtra) (+vsw/aggr policy/dhcp)

		if (!is_array($nic_arr))
			return new Cell("no information");

		$c_arr = array();

		foreach($nic_arr as $nic) {
			$class = $col = false;
			$id = array();
			$na = explode("|", $nic);

			// First row has the NIC name in bold if it's cabled, light if
			// not, then the IP address or state of the interface

			//$txt = (!$anic || $na[1] == "etherstub")
				//? "<strong>" . $na[0] . ": <u>$na[1]</u></strong>"
				//: "$na[0]: $na[1]";

			$txt = ($na[2] == "uncabled" || $na[2] == "unconfigured")
				? "<div class=\"faint\"><strong>$na[0]</strong> :
				$na[2]</div>"
				: "<strong>" . $na[0] . ": <u>$na[2]</u></strong>";

			if ($na[4]) $txt .= " ($na[4])";

			// Type of object. Some need to made more human-readable

			if ($na[1] == "phys" || $na[1] == "legacy")
				$d["type"] = "NIC";
			elseif ($na[1] == "aggr")
				$id["type"] = "aggregate";
			elseif ($na[1] == "clprivnet")
				$id["type"] = "Sun Cluster private";
			else
				$id["type"] = $na[1];

			// MAC address, if we have one

			if (!empty($na[3]))
				$id["MAC"] = "<tt>" . $this->format_mac_addr($na[3]) .
				"</tt>";

			// Speed-duplex

			if ($na[5]) {

				// Split the speed/duplex into two parts

				$sa = preg_split("/:|-/", $na[5]);

				// Now $sa[0] is the speed, $sa[1] is the duplex. I don't
				// want the "b" on the speed.

				$sa[0] = str_replace("b", "", $sa[0]);

				// Make the speed "1G" if it's 1000M. Also look out for long
				// strings from kstat.

				if ($sa[0] == "1000M" || $sa[0] == "1000000000")
					$sa[0] = "1G";
				elseif ($sa[0] == "100000000")
					$sa[0] = "100M";
				elseif ($sa[0] == "10000000")
					$sa[0] = "10M";

				// Make the duplex part "full" if it's only "f", and "half"
				// if it's only "h"

				if (sizeof($sa) > 1) {

					if ($sa[1] == "f")
						$sa[1] = "full";
					elseif ($sa[1] == "h")
						$sa[1] = "half";

					$speed = "${sa[0]}bit/$sa[1] duplex";
				}

				if (isset($sf[1])) $speed .= " $sf[1]";

				if (isset($speed)) $id["speed"] = $speed;
			}

			// IPMP? Put the IPMP group and colour it if we're not using
			// subnet_cols

			if ($na[7]) {
				$id["IPMP"] = $na[7];

				if (!defined(SUBNET_COLS))
					$col = $this->cols->icol("solid", "green");

			}

			// Underlying NICs?

			if ($na[6]) {
				if ($na[6] != "?") $id["over"] = preg_replace("/,/", ", ",
					$na[6]);
			}

			if ($na[8]) {

				if ($na[8] == "DHCP") {
					$id["DHCP"] = "yes";

					if (!defined(SUBNET_COLS))
						$col = $this->cols->icol("solid", "amber");
				}
				elseif($na[8] == "+vsw")
					$id["has vswitch"] = "yes";
				elseif($na[1] == "aggr")
					$id["policy"] = $na[8];

			}


			// Colours. We can colour on device type, or on subnet

			if (!defined(SUBNET_COLS)) {

				$type = ($na[1] == "legacy")
					? "phys"
					: $na[1];

				$class = "boxnet${type}" ;
			}


			/*

			// That's all the info that goes in the cells. Now we need to
			// work out how to colour them. We do that with inline colour

			$subnet = false;

			// If there's an IP address, try to get its subnet.

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
					elseif($na[1] == "unconfigured")
						$subnet = "unconfigured";
					elseif ($na[3])
						$subnet = "vlan";

				}

			}
			elseif ($na[1] == "etherstub")
				$subnet = $na[1];
			elseif (preg_match("/vswitch/", $na[1]))
				$subnet = "vswitch";
			elseif (preg_match("/vlanonly/", $na[1]))
				$subnet = "vlan";
			elseif (preg_match("/exclusive/", $na[1])) {

				// Colour exclusive IP instances by getting the name of the
				// zone which holds the IP instance, and getting its primary
				// NIC's subnet.

				if (isset($this->servers[$na[3]]["net"]["NIC"])) {
					$n = $this->servers[$na[3]]["net"]["NIC"];

					foreach($n as $nr) {
						$snn = explode("|", $nr);

						if ($snn[0] == $na[0]) {
							$subnet = PlatformGrid::get_subnet($snn[1]);
							break;
						}

					}

				}

			}

			// We know the subnet, so we can get the colour for the cells

			$class = ($subnet)
				? "net" . preg_replace("/\./", "", $subnet)
				: false;

			// If it's a virtual interface, use a box. Otherwise use solid

			if (preg_match("/:/", $na[0])) $class = "box$class";
			*/

			$c_arr[] = array($txt . $this->indent_print($id), $class, $col);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}


	//-- tools and applications ----------------------------------------------

	protected function show_sun_cc($data)
	{
		// Parse a list of Sun CC versions, make them more human-readable,
		// and colour them.

		$c_arr = array();

		foreach ($data as $datum) {

			$sccarr = $new_data = array();
			preg_match("/(^.*)@=(.*$)/", $datum, $sccarr);

			$sccver = preg_replace("/ .*$/", "", $sccarr[1]);

			if (isset($this->latest["Sun CC"])) {

				$bg_class =  ($sccver == $this->latest["Sun CC"])
					? "ver_l"
					: "ver_o";

			}
			else
				$bg_class = false;

			$new_data = (in_array($sccver, array_keys($this->sun_cc_vers)))
				? preg_replace("/^${sccver}/", "<strong>" .
				$this->sun_cc_vers[$sccver] . "</strong>", $sccarr[1])
				: $sccarr[1];

			$c_arr[] = array($new_data, $bg_class, false, false, false,
			$sccarr[2]);
		}

		return new listCell($c_arr);
	}

	protected function show_apache_so($data)
	{
		// Print a list of Apache shared modules. The module lists contain
		// the version number of the Apache to which they belong. If there's
		// only one Apache on this box, strip that extraneous information
		// out

		sort($data);

		if (sizeof($this->cz["Apache"]) == 1)
			$data = preg_replace("/ .*$/", "", $data);

		// If the parent apache is of an unknown version, the auditor just
		// puts () after the module. Change that to (unknown)

		$data = preg_replace("/\(\)/", "(unknown)", $data);

		// Strip off the .so if we have it

		$data = preg_replace("/\.so/", "", $data);

		return new listCell($data, "smallaudit");
	}

	protected function show_mod_php($data)
	{
		// Parse PHP modules.

		$data = preg_replace("/\(\)/", "(unknown)", $data);

		if (isset($this->cz["Apache"])) {

			$data = (sizeof($this->cz["Apache"]) == 1)
				? preg_replace("/\(apache.*$/", "(apache)", $data)
				: preg_replace("/module\) \(/", "", $data);
			}

		foreach($data as $datum) {
			$ver = preg_replace("/ .*$/", "", $datum);
			$vc = $this->ver_cols($ver, "mod_php", false);
			$c_arr[] = array($vc[0], $vc[1], $vc[2]);
		}

		return new listCell($c_arr);
	}

	protected function show_svn_server($data)
	{
		// Display Subversion server. Do versioning for svnserve, but NOT
		// for Apache

		foreach($data as $datum) {
			$vc = $this->ver_cols($datum, "svn server");

			// We don't have the version of the apache module, so colour it
			// orange

			if (preg_match("/^apache/", $vc[0]))
				$vc[1] = "solidorange";

			$c_arr[] = array($vc[0], $vc[1]);
		}

		return new listCell($c_arr);

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
				$subname = $sshvend = "OpenSSH";
			elseif (preg_match("/Sun_SSH/", $datum)) {
				$sshvend = "Sun";
				$subname = "Sun_SSH";
			}
			else {
				$sshvend = preg_replace("/_[\d].*$/", "", $datum);
				$subname = "unknown";
			}

			$vc = $this->ver_cols("$sshvend $sshver", "sshd", $subname);
			$c_arr[] = array($vc[0], $vc[1], $vc[2]);
		}

		return new listCell($c_arr);
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
		// Zpools are now reported differently in parseable mode. If we
		// don't have parseable output, pass the data on to the legacy
		// method
		//
    	//  [0]  => pool name
		//  [1]  => status
		//  [2]  => size
		//  [3]  => %full (capacity)
		//  [4]  => time of last scrub
		//  [5]  => zpool version
		//  [6]  => highest supported zpool version
		//  [7]  => pool layout
		//  [8]  => number of devices in pool
		//  [9]  => clustered (blank or literal string 'CLUSTERED')
        //  [10] => log|cache


		if (!preg_match("/|/", $data[0])) {
			return $this->show_zpool_legacy($data);
		}

		$c_arr = false;

		foreach($data as $pool) {
			$id = $idc = array();
			$a = explode("|", $pool);

			// If the pool is FAULTED, that's all the info we have.

			if ($a[1] == "FAULTED") {
				$c_arr[] = array("<strong>$a[0]</strong> ($a[1])",
				"solidred");
			}
			else {
				// Pool name in bold, (status) after it

				$txt = "<strong>$a[0]</strong> ($a[1])";

				// Pool size, and how much of it is used. Highlight in amber or
				// red if it's nearly full

				$id["size"] = $a[2] . " ($a[3] used)";

				if ($a[3] > 90)
					$idc["size"] = "solidred";
				elseif ($a[3] > 80)
					$idc["size"] = "solidamber";

				// pool layout, and the number of devices it contains

				$id["layout"] = $a[7];
				$id["devices"] = $a[8];

                if ($a[10]) {

                    if (preg_match("/log/", $a[10])) {
                        $id["log device"] = "yes";
                    }

                    if (preg_match("/cache/", $a[10])) {
                        $id["cache device"] = "yes";
                    }

                }


				// Is the pool under cluster control? Highlight this, because
				// it's unusual

				if ($a[9] == "CLUSTERED") {
					$id["clustered"] = "yes";
					$idc["clustered"] = "solidpink";
				}

				// The pool version. If a higher version is supported (i.e. the
				// pool can be upgraded) put it on an orange field

				if ($a[6] == "feature flags") {
					$id["version"] = "n/a (feature flags)";
				}
				elseif ($a[5] == $a[6])
					$id["version"] = $a[5];
				else {
					$id["version"] = "$a[5] ($a[6] supported)";
					$idc["version"] = "solidorange";
				}

				// When the pool was last scrubbed. See legacy function for the
				// $c[] array

				if ($a[4] == "none")
					$id["scrubbed"] = $a[4];
				else {
					$c = preg_split("/\W+/ ", $a[4]);
					$id["scrubbed"] = "$c[2] $c[1] $c[6]";
				}

				// Get the class for the cell. This depends on the pool's status

				$class = ($a[1] == "DEGRADED")
					? "boxamber"
					: "boxgreen";


				$c_arr[] = array($txt . $this->indent_print($id, $idc),
				$class);
			 }


		}

		return new listCell($c_arr, "auditl", false, 1);
	}

	protected function show_zpool_legacy($data)
	{
		// List Zpools. Legacy version. This won't be updated.

		$c_arr = false;

		foreach($data as $row) {
			$id = $idc = array();
			$txt = $class = "";

			if (preg_match("/FAULTED$/", $row))
				preg_match("/(\w+) (\w+)/", $row, $a);
			else
				preg_match("/(\w+) (\w+) \(last scrub: ([^\)]+)\)(.*)$/",
				$row, $a);

			// So $a[] is of the form:
			// [1] => pool name
			// [2] => pool status
			// [3] => last scrub - ddd mmm DD HH:MM:SS YYYY | none
			// [4] => [version/supported_version] | empty

			// UNLESS the pool is faulted
			// [1] => pool name
			// [2] => "FAULTED"

			$txt = "<strong>$a[1]</strong> ($a[2])";

			if ($a[2] == "FAULTED")
				$class = "solidred";
			else {
				$class = "boxgreen";

				// Split the version and colour if there's a higher
				// supported version than the pool is using. (Assuming we
				// have the version. Originally ZFS couldn't tell you what
				// version it was.)

				if (!empty($a[4])) {
					preg_match("/\[(\d+)\/(\d+)\]/", $a[4], $b);

					if ($b[1] == $b[2])
						$id["version"] = $b[1];
					else {
						$id["version"] = "$b[1] ($b[2] supported)";
						$idc["version"] = "solidorange";
					}

				}

				// Now the scrub. Don't bother reporting it if there isn't
				// one

				if ($a[3] != "none") {
					$c = preg_split("/\W+/ ", $a[3]);

					// So c[] is of the form:
					// [0] => day of week
					// [1] => month
					// [2] => day of month
					// [3] => hour
					// [4] => minutes
					// [5] => seconds
					// [6] => year

					$id["scrubbed"] = "$c[2] $c[1] $c[6]";
				}

			}

			// if the pool is in a degrated state, override the background
			// colour

			if ($a[2] == "DEGRADED")
				$class = "boxamber";

			$c_arr[] = array($txt . $this->indent_print($id, $idc),
			$class);
		}

		return new listCell($c_arr, "auditl", false, 1);
	}

	protected function show_disk_group($data)
	{
		// VxVM disk groups. There can be many of these, so we need a
		// listCell. It's quite a complicated string we get, and needs a
		// fair bit of processing

		foreach($data as $dg) {
			$id = $idc = array();

			preg_match("/(\w+) \((\S+)\) \[([^\]]*)\](.*)$/", $dg, $a);

			// Gives us and array of the form:
			// [1] => disk group name
			// [2] => disk group state
			// [3] => n disks/n+m subdisk/n vol/n+m plex
			// [4] => [ERRS: n disk/m plex] or nothing

			// The name and state are easy

			// If the state has "cds" or whatever tagged on, remove it

			$a[2] = preg_replace("/,.*/", "", $a[2]);

			$txt = "<strong>$a[1]</strong> ($a[2])";

			// Now the disk/subdisk/volume/plex string

			preg_match("/(\d+) disk\/(\d+)\+(\d+) subdisk\/(\d+) "
			. "vol\/(\d+)\+(\d+) plex/", $a[3], $b);

			// so b[] is an array with:
			// [1] => number of disks
			// [2] => number of subdisks
			// [3] => number of unused subdisks
			// [4] => number of volumes
			// [5] => number of plexes
			// [6] => number of unused plexes

			// disks

			$id["disks"] = $b[1];
			$id["subdisks"] = $b[2];

			if ($b[3] > 0) {
				$id["subdisks"] .= " ($b[3] unused)";
				$idc["subdisks"] = "solidamber";
			}

			$id["volumes"] = $b[4];
			$id["plexes"] = $b[5];

			if ($b[6] > 0) {
				$id["plexes"] .= " ($b[6] unused)";
				$idc["plexes"] = "solidamber";
			}

			// Do we have errors?

			if (!empty($a[4])) {
				preg_match("/\[ERRS:(\d+) disk\/(\d+).*$/", $a[4], $c);

				// so $c is of the form:
				// [1] => number of errored disks
				// [2] => number of errored plexes

				if ($c[1] > 0) {
					$id["disk errors"] = $c[1];
					$idc["disk errors"] = "solidred";
				}

				if ($c[2] > 0) {
					$id["plex errors"] = $c[2];
					$idc["plex errors"] = "solidred";
				}

			}

			if ($a[2] == "enabled")
				$class = "green";
			elseif ($a[2] == "disabled")
				$class = "red";
			else
				$class = "amber";

			$c_arr[] = array($txt . $this->indent_print($id, $idc),
			"box$class");
		}

		return new listCell($c_arr, "auditl", false, 1);
	}

	protected function show_metaset($data)
	{
		// show metasets on multiple lines. If we own the set, outline the
		// box in green
		// input of the form:
		//   set_name (n disks/n hosts) [owner]
		// [owner] may or may not be there

		sort($data);

		foreach($data as $set) {
			$id = $idc = array();
			preg_match("/(\S+) \((\d+) .*\/(\d+).*\)(.*)$/", $set, $a);

			// LEGACY SUPPORT - we just used to get the set name. So if the
			// preg_match() failed, print the set string

			if(count($a) == 0) {

				$class= (preg_match("/OWNER/", $set))
					? "green"
					: "red";

				$list_class = "audit";

				$c_arr[] = array(preg_replace("/ .*$/", "", $set), $class);
			}
			else {

				// [1] => set name
				// [2] => number of disks
				// [3] => number of hosts
				// [4] => blank or " [owner]"

				$txt = "<strong>$a[1]</strong>";

				$class = empty($a[4])
					? "boxred"
					: "boxgreen";

				$id["disks"] = $a[2];
				$id["hosts"] = $a[3];

				$list_class = "auditl";

				$c_arr[] = array($txt . $this->indent_print($id, $idc),
				$class);
			}

		}

		return new listCell($c_arr, $list_class, false, 1);
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

		$xcol = (preg_match("/mirror/", $data[0]))
			? false
			: $this->cols->icol("solid", "amber");

		return new Cell($data[0], $fstyp, $xcol);
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

		// directory fs_type [b_used/b_avail (pc%) used] // (dev:opts:x_opts)

		$c_arr = array();

		foreach($data as $row) {
			$id = $idc = $ic = array();
			$class = false;

			preg_match("/^(\S+) (\w+) \[([^\]]+)\] \(([^\)]+)\)(.*)$/",
			$row, $a);

			// For normal, mounted, filesystsems, this gives us an array
			// where
			// [0] = whole string from s-audit.sh
			// [1] = mountpoint
			// [2] = fs type
			// [3] = space used. Usually "x/y (z%) used"
			// [4] = device;mount options;zfs options (if applicable)
			// [5] = "not in vfstab" or empty

			// For unmounted ZFS filesystems
			// [1] => "unmounted"
			// [2] => zfs
			// [3] => referenced size
			// [4] => dataset name

			if (count($a) != 6) {
				$c_arr[] = array("ERROR: $a[0]", "error");
				continue;
			}

			if ($a[1] == "unmounted") {
				$txt = "<div class=\"faint\"><strong>$a[4]</strong>"
				. " unmounted ZFS ($a[3] referenced)</div>";
				$c_arr[] = array($txt);
				continue;
			}

			// expand the mount options

			$b = explode(";", $a[4]);

			// So now b is an array with
			// [0] = device
			// [1] = mount options
			// [2] = ZFS options (if applicable)

			// Certain filesystems are of no interest to us. Don't bother
			// with anything mounted on /platform, /dev, or any mountpoint
			// in / beginning .S. These are all system related things
			// required for various zone types to run, and not relevant
			// here.

			if ($a[2] == "lofs" &&
			preg_match(":^/platform\b|^/dev\b|^/\.S:", $a[1]))
				continue;

			// The cell's class comes from the filesystem type

			$class = $a[2];

			// ROW 1 is the mountpoint followed by the fs type and device
			// path

			$txt = "<strong>$a[1]</strong> (" . strtoupper($a[2]);

			// In local zones, loopback mounted filesystems have the same
			// device name as the mountpoint. Don't bother showing that.
			// Otherwise, add on the device path/zfs dataset name

			if ($a[1] != $b[0]) $txt .= ":$b[0]";

			$txt .= ")";

			// Again, if not in vfstab, this time just say so

			if (!empty($a[5])) $txt .= " (not in vfstab)";

			// If this isn't in the vfstab, put the first line on a pink
			// field

			$txt = (empty($a[5]))
				? "\n<div>$txt</div>"
				: "\n<div class=\"solidpink\">$txt</div>";

			// ROW 2 is disk usage. Use amber and red fields for fses more
			// than 80 and 90% full respectively

			if ($a[3] == "unknown capacity")
				$id["capacity"] = "unknown";
			else {
				preg_match("/(\d+)\/(\d+) \((\d+)%\).*$/", $a[3], $c);

				// c is now an array with
				// [0] full string of $a[3]
				// [1] bytes used
				// [2] bytes available
				// [3] percentage used

				$id["capacity"] = units::from_b($c[1]) . "/" .
				units::from_b($c[2]) . " ($c[3]%) used";

				if ($c[3] > 90)
					$idc["capacity"] = "solidred";
				elseif ($c[3] > 80)
					$idc["capacity"] = "solidamber";

			}

			// ROW 3 is mount options. Colour this if it's read-only. Other
			// odd options we leave it to the viewer to spot (for now)

			if ($b[1]) $id["opts"] = preg_replace("/,/", " ", $b[1]);

			if (preg_match("/\bro\b/", $b[1])) $idc["opts"] = "solidgrey";

			// Extended options. You get these for ZFS filesystems

			if ($a[2] == "zfs" && isset($b[2])) {
				$d = explode(",", $b[2]);
				$row = "";

				foreach($d as $e) {
					$f = explode("=", $e);
					$key = $f[0];
					$val = $f[1];

					// Don't print anything that's "off"

					if ($val == "off") continue;

					// If the quota is non-zero, make it human-readable and
					// display it

					if ($key == "quota") {

						if ($val != 0)
							$row .= " quota=" . units::from_b($val);
					}

					// If the version isn't the maximum supported version,
					// put up a warning

					elseif ($key == "version") {
						$v = explode("/", $val);
						$row .= " version=$v[0]";

						if ($v[0] != $v[1]) {
							$row .= " (v$v[1] supported)";
							$idc["ZFS opts"] = "solidorange";
						}

					}

					// Print the key/value of anything else. This way if
					// anything's added to the client, it will be displayed,
					// even if it isn't processed

					else
						$row = $e;

				}

				$id["ZFS opts"] = $row;

			}
			elseif (isset($b[2]))
				$id["ZFS opts"] = $b[2];

			$c_arr[] = array($txt . $this->indent_print($id, $idc), $class);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	protected function show_export($data)
	{
		// Nicely present exported filesystems and disks. Does NFS, SMB (via
		// ZFS options of the old-skool daemon), iSCSI, LDOM VDisks,
		// VirtualBox disks and xVM disks.  Colouring is done from the
		// dynamic stylesheet.

		$fold = (defined("SINGLE_SERVER"))
			? 80
			: 40;

		$c_arr = false;

		foreach($data as $row) {
			$col = false;
			$id = array();

			preg_match("/(\S+) \(([^\)]*)\) \[(.*)\](.*)$/", $row, $a);

			// Gives us a n array of the form:
			// [1] => path to export
			// [2] => export type
			// [3] => options
			// [4] => description for NFS

			$fstyp = $class = $a[2];

			if ($a[2] == "nfs") {
				$fstyp = "NFS";

				if (!empty($a[4]))
					$id["desc"] = preg_replace("/\"\]$/", "", $a[4]);

				// For NFS, we strip off the domain name, if it's defined in
				// STRIP_DOMAIN, and fold.

				$opts = $a[3];

				if (STRIP_DOMAIN)
					$opts = $this->fold_line(str_replace("." .
					STRIP_DOMAIN, "", $opts), $fold);

				$id["opts"] = preg_replace("/,/", " ", $opts);

				// Now we look to see if anything else has mounted this
				// filesystem. $mntd_nfs is an array which counts the number
				// of times each NFS filesystem is mounted. If we don't have
				// that array, skip this step

				if (isset($this->mntd_nfs) && sizeof($this->mntd_nfs) > 0) {
					$key = $this->cz["hostname"][0] . ":" . $a[1];

					$mnts = (in_array($key, array_keys($this->mntd_nfs)))
						? $this->mntd_nfs[$key]
						: 0;

					if ($mnts == 0)
						$col = $this->cols->icol("solid", "amber");

					$id["known mounts"] = $mnts;
				}

			}
			elseif ($a[2] == "xVM disk") {
				$id["binding"] = str_replace("on ", "", $a[3]);
				$class = "xvm";
			}
			elseif ($a[2] == "vdisk") {
				$class = "vdisk";

				preg_match("/(\S+) bound to (.*)$/", $a[3], $x);

				$id["device"] = $x[1];

				if (preg_match("/unassigned$/", $a[1])) {
					$id["bound to"] = $x[2];
					$col = $this->cols->icol("solid", "amber");
				}
				else
					$id["bound to"] = $x[2];


			}
			elseif (preg_match("/^smb/", $a[2])) {

				// For SMB exports, we have a name

				$id["name"] = preg_replace("/[\[\]]/", "", $a[3]);
				$class = "smb";
			}
			elseif ($a[2] == "iscsi") {
				$fstyp = "iSCSI";
				$id["shareiscsi"] = $a[3];
			}


			$c_arr[] = array("<strong>$a[1]</strong> ($fstyp)" .
			$this->indent_print($id), $class, $col);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	//-- hosted services -----------------------------------------------------

	protected function show_website($data)
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

			$id = $idc = array();
			$dr = $s["dr"];
			$ws = $s["ws"];
			$uri = $s["uri"];
			$row1 = "";

			// We can have multiple URIs. They're server aliases

			foreach($uri as $u) {

				// Make the URI link coloured if it looks like we have a
				// parsed IP list


				if (isset($this->ip_list) && sizeof($this->ip_list) > 0) {

					$lc = (in_array($u, array_keys($this->ip_list)))
						? "strongg"
						: "strongr";

				}
				else
					$lc = "strong";

				$txt = "<a class=\"$lc\" href=\"http://${u}\">${u}</a> ($ws)";

			}

			// On the next line down, we print the document root. This is
			// outlined in red if it's an NFS mount, and amber if there are
			// NFS mounts somewhere under it. We only do this if there's a
			// populated NFS directory array

			$hn = $this->cz["hostname"][0];

			if (isset($this->nfs_dirs) && sizeof($this->nfs_dirs) > 0 &&
				in_array($hn, array_keys($this->nfs_dirs))) {

				foreach($this->nfs_dirs[$hn] as $nd) {
					unset($col);

					// First look for directory roots inside NFS mounts,
					// then for directory roots with NFS mounts somewhere
					// underneath them

					if ($dr == "UNDEFINED" || preg_match("|^$nd.*|", $dr))
						$idc["doc root"] = "red";
					elseif (preg_match("|^$dr.*|", $nd))
						$idc["doc root"] = "amber";

				}

			}

			$id["doc root"] = $dr;

			// Finally we do the config file. We highlight this if its name
			// doesn't end .conf. This might not be suitable for all sites.

			// For iPlanet this field is the instance

			if ($ws == "iPlanet")
				$id["instance"] = $cf;
			else {

				if (!preg_match("|\.conf$|", $cf))
					$idc["config"] = "solidamber";

				$id["config"] = $cf;
			}

			$c_arr[] = array($txt . $this->indent_print($id, $idc), $ws);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
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
			$txt = "<strong>$arr[1]</strong> ($arr[0]) $arr[2]b";
			$col = false;
			$id = array();

			if ($arr[3]) {
				$id["last update"] = $arr[3];
				$col = $this->cols->icol("solid", "amber");
			}

			$c_arr[] = array($txt . $this->indent_print($id), $arr[0], $col);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	protected function show_AI_service($data)
	{
		// Present AI service information. Put the service name in bold.
		// Sometimes we'll have a service alias on the end in [square
		// brackets], sometimes not

		$c_arr = array();

		foreach($data as $datum) {
			$id = array();
			$a = preg_split("/\s/", $datum);

			// Now
			// a[0] is the service name
			// a[1] is the service path
			// a[2] is (arch/status)
			// a[3] is [aliased_service] or unset

			$txt = "<strong>$a[0]</strong>";
			$id["path"] = $a[1];

			$xtra = preg_split("/[\(\)\/]/", $a[2]);
			$id["arch"] = $xtra[1];
			$id["status"] = $xtra[2];

			$class = ($xtra[1] == "x86")
				? "x86"
				: "sparc";

			// Solaris 11 11/11 introduced the service aliases. If we have
			// one, report it.

			if (isset($a[3])) {
				$alias = trim($a[3], "[]");

				if ($alias != "-") {
					$id["alias of"] = $alias;
				}
			}

			$c_arr[] = array($txt . $this->indent_print($id), $class);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	protected function show_AI_client($data)
	{
		// AI clients

		$c_arr = array();

		// Sort by MAC address

		sort($data);

		foreach($data as $datum) {
			$id = array();
			$a = preg_split("/\s/", $datum);
			$txt = "<strong>$a[0]</strong>";

			$id["service"] = $a[1];
			$id["arch"] = trim($a[2], "()");

			$class = ($id["arch"] == "x86")
				? "x86"
				: "sparc";

			$c_arr[] = array($txt . $this->indent_print($id), $class);

		}

		return new listCell($c_arr, "smallauditl", false, 1);

	}

	//-- security ------------------------------------------------------------

	protected function show_user($data)
	{
		// Work on user data. If any user name comes up twice, but with a
		// different UID the second time, flag it red

		$c_arr = array();

		// We first want to get users which aren't installed on this version
		// of Solaris by default.  We also look at users which are there by
		// default, but are missing on this system

		if (isset($this->curr_omit["users"])) {
			$x_arr = array_diff($data, $this->curr_omit["users"]);
			$m_arr = array_diff($this->curr_omit["users"], $data);
		}
		else {
			$x_arr = $data;
			$m_arr = array();
		}


		foreach($x_arr as $e) {
			preg_match("/^(\S+) \((\d+)\)$/", $e, $a);

			if (count($a) != 3)
				continue;

			$un = $a[1];	// username
			$ui = $a[2];	// UID
			$class = false;

			// Is the username already known? If so, does it have the same
			// UID it did before? If it's not, add it to the known users
			// list

			if (in_array($un, array_keys($this->known_users))) {

				if (!in_array($ui, $this->known_users[$un])) {
					$class = "solidred";
					$this->known_users[$un][] = $ui;
					$e .=  "<div class=\"small\">&quot;$un&quot; "
					. "elsewhere assigned UID: "
					. $this->list_user_colls($ui, $this->known_users[$un])
					. "</div>";
				}

			}
			else
				$this->known_users[$un][] = $ui;

			// Has the UID already been used with a different username?

			if (in_array($ui, array_keys($this->known_uids))) {

				if (!in_array($un, $this->known_uids[$ui])) {
					$class = "boxred";
					$this->known_uids[$ui][] = $un;
					$e .=  "<div class=\"small\">uid $ui used elsewhere for: "
					. $this->list_user_colls($un, $this->known_uids[$ui])
					. "</div>";
				}

			}
			else
				$this->known_uids[$ui][] = $un;

			$c_arr[] = array($e, $class);

		}

		foreach($m_arr as $m) {
			$c_arr[] = array($m, "solidamber");
		}

		return new listCell($c_arr);
	}

	protected function list_user_colls($u, $arr)
	{
		// Used by show_user to give a list of UIDs or usernames in case of
		// collision

		$ret = "";
		$colls = array_diff($arr, array($u));

		if (count($colls) == 1)
			$ret = $colls[0];
		else  {

			foreach($colls as $c) {
				$ret .= ", $c";
			}

		}

		return $ret;
	}

	protected function show_authorized_key($data)
	{
		// Display authorized key data. Not much processing to do here. Root
		// keys are highlighted in red, everything else just goes in a
		// list with the username in bold.

		foreach($data as $row) {
			$a = explode(" ", $row);

			// omit massive "error" names

			if (strlen($a[0]) > 40) continue;

			$user = preg_replace("/[\(\)]/", "", $a[1]);

			$class = ($user == "root")
				? "solidred"
				: false;

			$c_arr[] = array("<strong>$user</strong>: $a[0]", $class);
		}

		return new listCell($c_arr);
	}

	protected function show_ssh_root($data)
	{
		// Highlight the box in red if root can SSH in

		if ($data[0] == "yes")
			$class = "solidred";
		elseif($data[0] == "unknown")
			$class = "solidorange";
		else
			$class = false;

		return new Cell($data[0], $class);
	}

	protected function show_user_attr($data)
	{
		// List user_attr info. Put the user in bold and fold the lines.

		$c_arr = array();

		// Are we going to hide standard attrs?

		if (defined("OMIT_STANDARD_ATTRS")) {

			if (isset($this->curr_omit["user_attrs"])) {
				$x_arr = array_diff($data, $this->curr_omit["user_attrs"]);
				$m_arr = array_diff($this->curr_omit["user_attrs"], $data);
				$omit = true;
			}
			else {
				$x_arr = $data;
				$m_arr = array();
				$omit = false;
			}

			if (defined("OMIT_MISSING_ATTRS"))
				$m_arr = array();

			// x_arr[] => non-standard attrs
			// m_arr[] => standard attrs which haven't been found

			// If we only have standard attrs - that is, none missing and no
			// extras, we're done

			if (count($x_arr) == 0 && count($m_arr) == 0)
				return new Cell("standard attrs");

			$attr_arr = array_merge($x_arr, $m_arr);

		}
		else
			$attr_arr = $data;

		sort($attr_arr);

		foreach($attr_arr as $attr) {
			$txt = preg_replace("/^(\w+):/", "<strong>$1</strong>:",
			$this->fold_line(htmlentities($attr), 60, '[,;]'));

			if ($omit) {

				if (in_array($attr, $x_arr))
					$class = "boxgreen";
				elseif(in_array($attr, $m_arr))
					$class = "boxamber";
				else
					$class = false;
			}
			else
				$class = false;

			$c_arr[] = array($txt, $class);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	protected function show_cron_job($data)
	{
		// List cron jobs. Put the user in bold and the time on the first
		// line, the command folded underneath.

		$c_arr = array();

		// Are we going to hide standard cron jobs?

		if (defined("OMIT_STANDARD_CRON")) {

			if (isset($this->curr_omit["crontabs"])) {
				$x_arr = array_diff($data, $this->curr_omit["crontabs"]);
				$m_arr = array_diff($this->curr_omit["crontabs"], $data);
				$omit = true;
			}
			else {
				$x_arr = $data;
				$m_arr = array();
				$omit = false;
			}

			if (defined("OMIT_MISSING_CRON"))
				$m_arr = array();

			// x_arr[] => non-standard cron jobs
			// m_arr[] => standard cron jobs which haven't been found

			// If we only have standard jobs - that is, none missing and no
			// extras, we're done

			if (count($x_arr) == 0 && count($m_arr) == 0)
				return new Cell("standard jobs");

			$job_arr = array_merge($x_arr, $m_arr);

		}
		else
			$job_arr = $data;

		sort($job_arr);

		foreach($job_arr as $job) {
			preg_match("/(\w+):(.*$)/", $job, $a);
			$b = preg_split("/\s+/", $a[2], 6);
			$txt = "<strong>$a[1]</strong>";

			// Space the time fields out a bit, for legibility

			$id["time"] = "<tt>$b[0]&nbsp;&nbsp;$b[1]&nbsp;&nbsp;$b[2]"
			. "&nbsp;&nbsp;$b[3]&nbsp;&nbsp;$b[4]</tt>";

			$id["job"] = "<tt>" . $this->fold_line(htmlentities($b[5]), 60)
			. "</tt>";

			if ($omit) {

				if (in_array($job, $x_arr))
					$class = "boxgreen";
				elseif(in_array($job, $m_arr))
					$class = "boxamber";
				else
					$class = false;

			}
			else
				$class = false;

			$c_arr[] = array($txt . $this->indent_print($id), $class);
		}

		return new listCell($c_arr, "smallauditl", false, 1);
	}

	protected function show_empty_password($data)
	{
		// Highlight all these in amber, except root, which is RED. Surely
		// people don't still have empty passwords do they? (I bet they do.)

		foreach($data as $datum) {

			$class= ($datum == "root")
				? "solidred"
				: "solidamber";

			$c_arr[] = array($datum, $class);
		}

		return new listCell($c_arr);
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

			$c_arr[] = array($ta[0], $class, false, false, false, $path);
		}

		return new listCell($c_arr);
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
		$vm = sizeof($this->map->list_vmws());
		$xvm = sizeof($this->map->list_domus());
		$kvm = sizeof($this->map->list_kvms());
		$unk = sizeof($this->map->list_unknowns());
		$others = $this->map->all - PER_PAGE;
		$parts = 0;

		// Logical domains, virtualboxes VMWware instances, XEN domains and
		// physical servers all have global zones, so work out how many are
		// actual hosts there are

		$phys = $gz - $ld - $vb - $vm - $xvm - $unk;

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

		// XEN domains

		if ($xvm > 0) {
			$ret_str .= ($lz == 0 && $ld == 0 && $vm == 0 && $unk == 0 &&
			$kvm == 0)
				? " and"
				: ",";

			$ret_str .= " <strong>$xvm</strong> XEN domain";

			if ($xvm != 1) $ret_str .= "s";

			$parts++;
		}

		// VMWare hosts

		if ($vm > 0) {
			$ret_str .= ($lz == 0 && $ld == 0 && $unk == 0 && $kvm == 0) ?
			   	" and" : ",";

			$ret_str .= " <strong>$vm</strong> VMware instance";

			if ($vm != 1) $ret_str .= "s";

			$parts++;
		}

		// Now LDOMs

		if ($ld > 0) {
			$ret_str .= ($lz == 0 && $unk == 0 && $kvm == 0) ? " and" : ",";

			$ret_str .= " <strong>$ld</strong> logical domain";

			if ($ld != 1) $ret_str .= "s";

			$parts++;
		}


		// KVM guests

		if ($kvm > 0) {
			$ret_str .= ($lz == 0 && $unk == 0) ? " and" : ",";

			$ret_str .= " <strong>$kvm</strong> KVM guest";

			if ($ld != 1) $ret_str .= "s";

			$parts++;
		}

		// Undetermined platforms

		if ($unk > 0) {
			$ret_str .= ($lz == 0) ? " and" : ",";

			$ret_str .= " <strong>$unk</strong> undetermined platform";

			if ($unk != 1) $ret_str .= "s";

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
			$ret_str .= "<div class=\"banner\"><strong>$others</strong> "
			. " machines not shown. (Total of " . $this->map->all
			. " physical and virtual machines, not counting local
			zones.)</div>";

		return $ret_str;
	}

	public function prt_toggle()
	{
		// Print a link which lets the user show or hide zones, or common
		// data, whichever is appropriate. Also gives you the "next page"
		// and "previous page" links, if they are required.

		// Will we need previous/next links? Only if there are more known
		// servers than the current PER_PAGE limit

		$next_str = $prev_str = "";
		$os = $this->map->offset;
		$no = $os + PER_PAGE;
		$po = $os - PER_PAGE;

		$qs = new queryString();

		if ($this->map->all > PER_PAGE) {

			if ($os != 0) {

				if ($po < 0)
					$po = 0;

				$prev_str = "<a href=\"$_SERVER[PHP_SELF]$qs&amp;o=${po}\">&lt;"
				. "previous</a> :: ";
			}

			if ($no < $this->map->all)
				$next_str = " :: <a href=\"$_SERVER[PHP_SELF]$qs&amp;o=${no}\">next"
				. "&gt;</a>";
		}

		// Are zones currently shown or hidden? Offer the alternative.

		$qs = new queryString(true);

		$txt = (defined("NO_ZONES"))
			? "show"
			: "hide";

		return "${prev_str}<a href=\"" . $_SERVER["PHP_SELF"]
		. "${qs}\">$txt $this->toggle_str</a>${next_str}";
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

	protected function get_paired_list($class, $prop1, $prop2)
	{
		// Make an array of the latest version of prop2 for each unique
		// prop1 in audit class $class

		$lo = array();

		foreach ($this->servers as $server) {
			$sc = $server[$class];

			if (!isset($sc[$prop1][0]) || !isset($sc[$prop2][0]))
				continue;

			$p1 = $sc[$prop1][0];
			$p2 = $sc[$prop2][0];

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
	protected $type = "platform";

	protected $latest_obps = array();
		// An array of "hardware" => "obp"

	protected $latest_aloms = array();
		// An array of "hardware" => "alom"

	protected $key_filename = "key_platform.php";
		// Helps us include they key file automatically

	protected $card_db;	// Card definitions from misc.php
	protected $hw_db;	// Card definitions from misc.php

	protected $def_fields = array("hardware", "virtualization", "CPU",
	"memory", "OBP", "ALOM f/w", "LOM f/w", "ALOM IP", "LOM IP", "storage",
	"multipath", "EEPROM", "serial number", "printer", "card");

	public function __construct($map, $servers, $c)
	{
		// The constructor is the standard HostGrid one, but it also
		// generates the $latest[] OBP and ALOM arrays

		parent::__construct($map, $servers, $c);
		$this->latest_obps = $this->get_paired_list("platform", "hardware",
		"OBP");
		$this->latest_aloms = $this->get_paired_list("platform",
		"hardware", "ALOM f/w");

		// We need the card definitions in misc.php

		require_once(DEF_DIR . "/misc.php");
		$defs = new defs();
		$this->card_db = $defs->get_data("card_db");
		$this->hw_db = $defs->get_data("hw_db");
	}

}

//==============================================================================
// O/S GRID

class OSGrid extends PlatformGrid
{
	protected $type = "os";

	protected $latest_kerns;
		// An array of OS ver => kernel version. OS ver is made up of
		// distribution + SunOS version + architecture
		// e.g.
		// solaris5.10s => 142900-01
		// opensolaris5.11x => 129
		// where "s" denotes SPARC and "x" x86/amd64

	protected $key_filename = false;

	protected $sol_upds;	// Solaris update dates, from misc.php

	protected $def_fields = array("distribution", "version", "release",
	"kernel", "hostid", "packages", "patches", "repository", "VM",
	"scheduler", "SMF services", "boot env", "timezone", "uptime");

    public function __construct($map, $servers, $c)
	{
		// The constructor is the standard HostGrid one, but it also
		// generates the $latest[] array

		parent::__construct($map, $servers, $c);
		$this->latest_kerns = $this->get_latest_kerns();

		// We need the definition file

		require_once(DEF_DIR . "/misc.php");
		$defs = new defs();
		$this->sol_upds = $defs->get_data("updates");
	}

	protected function mk_ver_arch_str($zn, $dist, $sver)
	{
		// Make a version/arch string for the given zone by concatenating
		// the distribution, version, and architecture

		$dist = preg_replace("/ zone/", "", $dist);
		$osver = preg_replace("/\W/", "", $dist . $sver);

		$z = ($this->map->is_global($zn))
			? $zn
			: $this->map->get_parent_zone($zn);

		$arch = (preg_match("/SPARC/",
		$this->servers["$z/$z"]["platform"]["hardware"][0]))
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

			if (!isset($server["os"]))
				continue;

			$d = $server["os"];

			if (!isset($d["distribution"][0]) || !isset($d["version"][0]))
				continue;

			$kp = $d["kernel"][0];

			// Disregard the virtual kernels in branded zones

			if ($kp == "Virtual") continue;

			$osver = $this->mk_ver_arch_str($d["hostname"][0],
			$d["distribution"][0], $d["version"][0]);

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
// NET AUDIT

class NetGrid extends HostGrid {

	protected $type = "net";

	protected $def_fields = array("name service", "DNS server", "NTP",
	"SNMP", "name server", "domainname", "route", "routing", "port",
	"NFS domain", "net");
}

//==============================================================================
// FS AUDIT

class FSGrid extends HostGrid {

	protected $type = "fs";

	protected $mntd_nfs = array();

		// This array counts the number of times each NFS mount is used. It
		// is populated by show_fs(), and read by show_exports(). It's here
		// because we only want it to be populated when we do a proper FS
		// audit, not when we're comparing or showing a single server.

	protected $def_fields = array("capacity", "ZFS version", "zpool",
	"disk group", "metaset", "fs", "root fs", "export");

	public function __construct($map, $servers, $c)
	{
		parent::__construct($map, $servers, $c);
		$this->mntd_nfs = $this->get_nfs_mounts();
	}

	protected function get_nfs_mounts()
	{
		// populate an array pairing NFS mounted filesystems, in the form
		// server:/full/path, with the amount of times we see them mounted.
		// Used by the exports column on the fs audit page

		// Get the filesystems for each zone

		$ta = array();

		foreach($this->map->list_all() as $srvr) {

			if (isset($this->servers[$srvr]["fs"]["fs"]))
				$fslist = $this->servers[$srvr]["fs"]["fs"];

			if (! isset($fslist) || !is_array($fslist)) continue;

			foreach(preg_grep("/^\S+ nfs /", $fslist) as $fs) {
				preg_match("/^.*\(([^;]*).*$/", $fs, $a);
				$ta = array_merge($ta, array($a[1]));
			}

		}

		return array_count_values($ta);
	}

}

//==============================================================================
// SECURITY AUDIT

class SecurityGrid extends HostGrid{

	protected $type = "security";

	// By default, the security audit page leaves out a lot of data, in an
	// attempt to keep the interface clean.

	protected $user_list = array();
		// Working array of users we encounter

	protected $known_users = array();
		// A list of known username -> array(uids), used to catch collisions

	protected $known_uids = array();
		// A list of known UID -> array(username), used to catch collisions

	protected $defs = array();
		// caches the omit file data. Elements are named dist-os.

	protected $curr_omit;
		// show_server() puts the omit data for the current server in this
		// so the correct data is always available to the methods that use
		// it

	protected $def_fields = array("user", "empty password",
	"authorized key", "user_attr", "SSH root", "dtlogin", "JASS applied",
	"cron job", "root shell");

	public function show_server($server)
	{
		// If we are omitting data, we need the appropriate security
		// definition file. This function looks to see if we've already read
		// the file, which is cached in $this->defs[dist-version], and if
		// not, it reads it in.

		// Whether it's cached or loaded, the omit data is put into the
		// $curr_omit variable

		// The function then calls the show_server method in the parent
		// class

		$this->curr_omit = array();

		$global_name = "$server/$server";

		if (isset($this->servers[$global_name]["os"])) {
			$dist = preg_replace("/ /", "_",
			$this->servers[$global_name]["os"]["distribution"][0]);

			preg_match("/.*(5\.\d+).*/",
			$this->servers[$global_name]["os"]["version"][0], $a);

			// We don;t specify a SunOS version for some of the Illumos
			// distributions, but they're all 5.11

			$sunosrev = (isset($a[1]))
				? $a[1]
				: "5.11";

			$myos = "${dist}-$sunosrev";

			if (isset($this->defs[$myos]))
				$this->curr_omit = $this->defs[$myos];
			else {

				$sd_file = DEF_DIR . "/security/sec_defs-${myos}.php";

				if (file_exists($sd_file)) {
					require_once($sd_file);
					$this->curr_omit = $this->defs[$myos] = $sec_data;
				}
				else
					page::warn("No security definition file for
					&quot;$sd_file&quot;.");

			}

		}

		return parent::show_server($server);
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

	public function __construct($map, $servers, $c)
	{
		// The constructor is the standard HostGrid one, but it also
		// generates the $latest[] array

		parent::__construct($map, $servers, $c);
		$this->latest = $this->get_latest();
	}

	protected function ver_cols($data, $field, $subname = false)
	{
		// Prep all the class and inline style info for the show_generic()
		// function

		$class = $style = $path =false;
		$ta = preg_split("/@=/", $data);

		$path = (isset($ta[1]))
			? $ta[1]
			: false;

		// If we can, set the cell background colour depending on the
		// version number. Can't do this in a single server audit

		if (method_exists($this, "strip_out_version")) {

			$sw_ver = $this->strip_out_version($data);
			$recent = $this->how_recent($field, $sw_ver, $subname);

			if ($sw_ver && $recent && $subname != "NOBG") {

				if ($recent == 2)
					$class = "ver_l";
				elseif($recent == 1)
					$class = "ver_o";
			}

		}

		// If the version is unknown, use a solid orange field

		if (preg_match("/unknown/", $ta[0]))
			$class = "solidorange";

		// And box the cell in red if something's not running

		if (preg_match("/not running/", $ta[0]))
			$style = $this->cols->icol("box", "red");

		return array($ta[0], $class, $style, false, false, $path);
	}

	public function show_generic($data, $field = false, $subname = false)
	{
		// This is a replacement show_generic() only used on the application
		// and tool audits.  It can handle single and multiple rows of data.
		// It differs from the standard version in that it colours the cells
		// according to version numbers. If subname is set to "NOBG", don't
		// do background colouring.

		$call = $col = $ret_str = false;

		if (is_string($data)) {
			$vc = $this->ver_cols($data, $field, $subname);
			$ret = new Cell($vc[0], $vc[1], $vc[2], $vc[3], $vc[4], $vc[5],
			$vc[6]);
		}
		else {
			$c_arr = array();

			foreach($data as $datum) {
				$vc = $this->ver_cols($datum, $field, $subname);
				$c_arr[] = array($vc[0], $vc[1], $vc[2], $vc[3], $vc[4],
				$vc[5]);
			}

			$ret = new listCell($c_arr);
		}

		return $ret;
	}

	//-- sorting -------------------------------------------------------------

	private function get_latest()
	{
		// Get the latest version of each piece of software, ignoring
		// software in the ignore_version array. Produces an array like
		// this:
		// [samba] => 3.0.28
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

		foreach($this->servers as $hostname=>$cd) {

			if (isset($cd[$this->c]))
				$all_sw = array_merge_recursive($cd[$this->c], $all_sw);
		}

		// In the loop below, $sw is the name of the software, and $vers is
		// an unsorted array of all the version numbers we have. Strip
		// extraneous info off the version, sort what's left, then pick off
		// the final one, and store it

		foreach($all_sw as $sw=>$vers) {

			// Skip anything we don't need to sort. We're potentially doing
			// a lot of work in this loop, and we want to minimize it as
			// much as we can.

			if (in_array($sw, $this->ignore_version) ||
				$sw == "audit completed")
				continue;

			// Minimize the array by removing duplicate keys, then use a
			// callback to strip out any "unknown" version strings

			$ver_arr = array_filter(array_unique($vers), array($this,
			"cb_no_unknowns"));

			// Chop off all the software paths. preg_replace can be used on
			// every element in an array simultaneously, then unique the
			// array again

			$ver_arr = array_unique(preg_replace("/(@=| \().*$/", "",
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

	private function get_latest_svn_server($ver_arr)
	{
		// Subversion can say "apache module", or "svnserve x.y.z". We just
		// want the latest x.y.z. First strip out the "svnserve" string to
		// just get the version numbers, like we get with everything else

		$ret = preg_replace("/^svnserve /", "", $ver_arr);

		// Lose the apache line, if there is one, otherwise it would come
		// last in the sort and be the latest version

		if ($idx = array_search("apache module", $ret))
			unset($ret[$idx]);

		natsort($ret);
		end($ret);

		return current($ret);
	}

	private function get_latest_multi($ver_arr, $regex)
	{
		foreach($ver_arr as $ver) {
			preg_match("/$regex/", $ver, $a);

			if (sizeof($a) > 1) $arr[$a[1]][] = $a[2];
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

		return $this->grid_key_header($nf) . "<tr>" .
		$this->grid_key_col($this->grid_key["hostname"]) .
		$this->grid_key_col($this->grid_key["general"], ($nf - 2)) .
		$this->grid_key_col($this->grid_key["audit completed"]) . "</tr>";
	}

}

//----------------------------------------------------------------------------
// APPGRID

class AppGrid extends SoftwareGrid
{
	protected $type = "app";

	protected $ignore_version = array("hostname", "apache_so", "AI server",
	"audit completed");
		// Don't try to find the latest versions of these fields

	protected $def_fields = array("powermt", "VxVm", "VxFS", "VCS",
	"Sun Cluster", "SMC", "sshd", "BIND", "X server", "chef-client",
	"sendmail", "exim", "Samba", "ldm", "AI server", "Apache", "apache
	so", "mod_php", "Tomcat", "Glassfish", "iPlanet web", "Nginx",
	"Squid", "Oracle", "MySQL server", "Postgres", "svn server",
	"Networker clnt", "Networker srvr"  );
}

//----------------------------------------------------------------------------
// TOOL GRID

class ToolGrid extends SoftwareGrid
{
	protected $type = "tool";

	protected $ignore_version = array("hostname", "audit completed");
		// Don't try to find the latest versions of these fields

	protected $sun_cc_vers;
		// Sun Studio versions from misc.php

	protected $def_fields = array("PCA", "OpenSSL", "Java",
	"perl", "Python", "PHP cmdline", "ruby", "node.js", "Sun CC", "GCC",
	"sqlplus", "MySQL client", "Postgres client", "svn client", "rsync",
	"Explorer", "VTS", "JASS", "JET", "Sneep", "SunCAT", "s-audit");
	public function __construct($map, $servers, $c)
	{

		// We need the defs file for Sun Studio

		require_once(DEF_DIR . "/misc.php");
		$defs = new defs();
		$this->sun_cc_vers = $defs->get_data("sun_cc_vers");

		parent::__construct($map, $servers, $c);
	}
}

//==============================================================================
// HOSTED SERVICES GRID

class HostedGrid extends HostGrid
{
	protected $type = "hosted";

	// The resolved array keeps track of server names we've already
	// resolved, so we don't waste time doing duplicates

	protected $ip_list = array();
	protected $nfs_dirs = array();

	protected $def_fields = array("website", "database", "AI service",
	"AI client");

	public function __construct($map, $servers, $c)
	{
		// This does a bit more than the usual grid class. We try to get a
		// list of our resolved external IP addresses, and we try to get a
		// list of all NFS mounted directories

		parent::__construct($map, $servers, $c);
		$this->ip_list = $this->get_ip_list();

		$this->nfs_dirs = $this->get_nfs_dirs();
	}

	private function get_nfs_dirs()
	{
		// Get a list of all NFS mounted directories. We use this to work
		// out whether or not document roots are NFS mounted. Each NFS mount
		// is an element in an array, one array per host

		return;
		// XXX does not work. As you can only see SOME of the servers if you
		// have more than 20
		$ra = array();

		foreach($this->servers as $h=>$s) {

			foreach($s["fs"]["fs"] as $fsl) {
				preg_match("/^([^ ]+) \(([^:]+):.*$/", $fsl, $m);

				if ($m[2] == "nfs")
					$ra[$h][] = $m[1];
			}

		}

		return $ra;
	}

	protected function get_ip_list()
	{
		// The s-audit_dns_resolver.sh should have left us a file which maps
		// site names to their external IP addresses. It's not easy to have
		// dig produce that file exactly how we'd like it, so we manipulate
		// a little here.

		// Let's see if the map file is there. If not, exit now.

		$map_file = $this->map->get_path("uri_map");

		if (!file_exists($map_file))
			return array();

		$ra = array();
		$t_arr = file($map_file);

		// The dig batch lookup only returns IP addresses for A records.
		// For CNAMEs it returns the name of the alias. I want to display IP
		// addresses only, so we'll change anything  in $ip_map[] that looks
		// like a CNAME into its IP address.

		// We know that the sort command in the script puts IP addresses are
		// at top of the audit file and CNAMES at the bottom. Thus we can
		// keep a running array of resolved_name => IP_address, and use it
		// to look up CNAMES

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

//- HTML and template stuff --------------------------------------------------

class Page {

	// Classes used to generate HTML pages. This is an abstract class, and
	// all page types extend it

	protected $title;
		// The page title. Used in the <title> tag

	protected $type;
		// "platform audit" or whatever

	protected $styles = array("basic.css");
		// Stylesheets to apply

	private $metas = array(
		"Content-Type" => "text/html; charset=utf-8",
		"pragma" => "no-cache"
	);
		// HTTP meta tags. Key/value pairs

	protected $mystring = "interface";
		// What kind of pages we're doing. In a variable so it can be
		// overridden

	protected $link_to = ROOT_URL;
		// Where the s-audit in the top left corner links to

	protected $no_class_link = false;
		// set this to true on non-audit class pages

	protected $link_root = "";

	protected $h_links = false;

	public function __construct($title)
	{
		if (empty($this->link_to))
			$this->link_to = "/";

		$this->type = $title;
		$this->title = SITE_NAME . " s-audit $this->mystring :: $title";
		$GLOBALS["verstr"] = "interface version " . MY_VER;
		$this->styles[] = "dynamic_css.php?" . basename($_SERVER["PHP_SELF"]);


		// Set a global variable to say the page has been properly opened.
		// This is checked by f_error() so it can print a valid HTML page if
		// it is called before the page has been properly created.

		$GLOBALS["pg_open"] = true;

		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional'
		. '//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
		. "\n\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
		. "\n<head>"
			. $this->add_styles()
			. $this->add_metas()
			. "\n  <title>$this->title</title>\n";

		// If there's Javascript for this page, include it

			if (isset($this->my_js))
				require_once(LIB . "/js/" . $this->my_js);

		echo "\n</head>\n" . "\n<body>" . $this->add_header();
	}

	private function add_styles()
	{
		// insert HTML for each stylesheet defined in the $styles variable

		$ret = "";

		foreach($this->styles as $style) {
			$ret .= "\n  <link rel=\"StyleSheet\" href=\"" . CSS_URL .
			"/${style}\" type=\"text/css\" media=\"all\" />";
  		}

		return $ret;
  	}

	private function add_metas()
	{
		// HTML for meta tags

		$ret = "\n";

		foreach($this->metas as $key=>$val) {
  			$ret .= "\n  <meta http-equiv=\"$key\" content=\"$val\" />";
  		}

		return $ret;
  	}

	protected function add_header()
	{
		// The banner at the top of all pages

		$fn = basename($_SERVER["PHP_SELF"]);

		$ret = "\n<div id=\"header\"><span id=\"logo\"><a href=\""
		. $this->link_to . "\">s-audit</a></span> ::"
		. " $this->type";

		if (isset($_GET["g"]))
			$ret .= $this->header_group_name($_GET["g"]);

		if (!empty($this->s_count))
			$ret .= " :: $this->s_count";

		if (method_exists($this, "add_doc_links"))
			$ret .= $this->add_doc_links($fn);

		return $ret . "\n</div> <!-- end header -->" . $this->add_content();
	}

	protected function header_group_name($group)
	{
		// Print the name of the group we're viewing. Split out into a
		// separate method so it can be overridden on single server views

		return " of <strong><a href=\"single_server.php?g=$group"
		. "\">$group</a></strong> group ";
	}

	protected function add_content()
	{
		// Open up DIV(s) for the page content. The corresponing
		// close_page() function must remember to close them

		$nav = new navigateHoriz($this->h_links, $this->link_root);
		return $nav->display_navbar() . "\n<div id=\"content\">";
	}

	static function spacer()
	{
		return "\n\n<div class=\"spacer\">&nbsp;</div>";
	}

	static function close_page()
	{
		echo "</div>" . Page::add_footer() . "\n</body>\n</html>";
	}

	static function add_footer()
	{
		// The bar at the bottom of every page

		$ret = "\n\n<div id=\"footer\">This is &quot;" .  SITE_NAME
		. "&quot; s-audit web interface ::";

		if (isset($GLOBALS["verstr"]))
			$ret .= " " . $GLOBALS["verstr"] ." ::";

		$ret .= " (c) " . C_YEAR . " <a href=\"http://snltd.co.uk\">SNLTD</a>";

		if (SHOW_SERVER_INFO)
			$ret .= " :: Running under PHP " . phpversion() . " on " .
			php_uname("n");

		return $ret . "</div>";

	}

	static function warn($msg = "undefined error", $depth = 0) {
		echo "\n\n<div class=\"f_err\">\n<h3>S-AUDIT WARNING</h3>\n\n"
		. "<p>$msg</p></div>";
	}

	static function error($msg = "undefined error", $depth = 0) {
		Page::err_box($msg, "Error", $depth);
	}

	static function f_error($msg = "undefined error", $depth = 0) {
		Page::err_box($msg, "Fatal Error", $depth);
	}

	static function err_box($msg = "undefined error", $title, $depth = 0)
	{
		// For fatal errors outside a grid. The $depth argument is to close
		// off open DIVs. Use with care and the pages will stay valid.

		// We may have been called before the page was constructed. If so,
		// create the HTML we're missing

		if (!isset($GLOBALS["pg_open"])) {
			$pg = new Page($title);
		}

		echo "\n\n<div class=\"f_err\">\n<h3>S-AUDIT "
		. strtoupper($title) . "</h3>\n\n"
		. "<p>$msg</p>";

		if (preg_match("/error/", $title))
			echo "\n\n<p><a href=\"" . ROOT_URL
			. "/index.php\">Return to front page</a>.</p>";

		echo "</div>";

		if ($depth)
			for ($i = 1; $i < $depth; $i++) echo "\n\n</div>";

		Page::close_page();

		exit();
	}

}

//----------------------------------------------------------------------------

class audPage extends Page {

	// Generates audit grid pages

	protected $styles = array("basic.css", "audit.css");
		// Stylesheets to apply

	protected $s_count = false;
		// the "displaying..." text in the header

	protected $toggle = false;
		// the "show zones" string in the header

	protected $h_links = array(
		"index.php" => "platform",
		"os.php" => "O/S",
		"net.php" => "networking",
		"fs.php" => "filesystem",
		"application.php" => "applications",
		"tools.php" => "tools",
		"hosted.php" => "hosted services",
		"security.php" => "security",
		"single_server.php" => "single server view",
		"compare.php" => "compare two servers",
		"ip_listing.php" => "IP address listing"
	);
		// static links

	public function __construct($title, $s_count, $toggle = false) {
		$this->s_count = $s_count;
		$this->toggle = $toggle;
		$this->link_root = dirname($_SERVER["PHP_SELF"]);
		parent::__construct($title);
	}

	protected function add_doc_links($fn)
	{
		// Links to documentation

		$class_link = ($fn == "index.php")
			? "class_platform.php"
			: "class_$fn";

		$dl = DOC_URL;

		$ret = "\n<div id=\"headerr\">\n<strong>documentation</strong>"
		. ":: <a href=\"${dl}/index.php\">main</a> / <a href=\"$dl"
		. "/03_interface/${class_link}\">this page</a>";

		if (!$this->no_class_link)
			$ret .= "/ <a href=\"${dl}/02_client/${class_link}\">this class</a>";

		if (method_exists($this, "add_toggle_link"))
			$ret .= $this->add_toggle_link();

		$ret .= "</div>";

		return $ret;
	}

	protected function add_toggle_link()
	{
		return "<br/>$this->toggle";
	}

}

//----------------------------------------------------------------------------

class ipPage extends audPage {

	// Generates the IP listing page

	protected $no_class_link = true;
		// There's no "this class" documentation link for IP listing pages

	protected $styles = array("basic.css", "audit.css", "ip_listing.css");
		// Stylesheets to apply

}

//----------------------------------------------------------------------------

class ssPage extends audPage {

	// Special class for single server view

	protected $no_class_link = true;
		// There's no "this class" documentation link

	protected $styles = array("basic.css", "audit.css", "single_server.css");

	public function __construct($title)
	{
		if (preg_match("/@/", $title))
			$title = preg_replace("/@/", " (zone on ", $title) . ")";

		parent::__construct($title, false);
	}

	protected function header_group_name($group)
	{
		// Print the name of the group we're viewing. Split out into a
		// separate method so it can be overridden on single server views

		return (isset($_GET["s"]))
			? " (member of <strong><a href=\"single_server.php?g=$group"
			. "\">$group</a></strong> group)"
			: parent::header_group_name($group);
	}


}

//----------------------------------------------------------------------------

class docPage extends Page {

	protected $mystring = "documentation";

	protected $styles = array("basic.css", "audit.css", "doc.css",
	"script.css", "ip_listing.css");
		// Stylesheets to apply

	protected $h_links = array(
		"." => "documentation home",
		"02_client" => "s-audit.sh client",
		"03_interface" => "web interface",
		"04_extras" => "extra files and support scripts",
		"05_misc" => "miscellany"
		);

	protected $link_root = DOC_URL;
		// The base of the $h_links

	public function __construct($title)
	{
		require_once(LIB . "/doc_classes.php");

		parent::__construct($title);

		// Get the documentation version. Documentation is kind of a
		// separate entity from the interface, so it has its own version,
		// which is stored in a file.

		$verfile = DOC_ROOT . "/.version";

		// Yes, I know all about using global variables. This used to be a
		// private class variable, but now we have to call all these methods
		// statically all the time, it's got to go global. I wouldn't do
		// this if I wasn't discontinuing the software.

		$GLOBALS["verstr"] = (file_exists($verfile))
			? " documentation version " . file_get_contents($verfile)
			: "";
    }

	protected function add_content()
	{
		// We need to add the docwrapper and doccontent divs

		$nav = new navigateHoriz($this->h_links, $this->link_root);

        return $nav->display_navbar()
		. "\n<div id=\"docwrapper\">\n<div id=\"doccontent\">";
	}

	static function dyn_menu()
	{
		// Put the dynamic menu on the right of the page. For documentation
		// pages. First close the "content" div, and open another. That'll
		// be closed by the close_page() function

		$vm = new NavigationDynamicVert();

		return "\n</div>\n<div id=\"vmenu\">" . $vm->print_list() . "</div>";
	}

	static function close_page()
	{
		echo  docPage::dyn_menu() . "</div>" . docPage::spacer() .
		Page::add_footer() . "\n\n</body>\n</html>";
	}

}

//----------------------------------------------------------------------------

class indexPage extends docPage {

	// Class for the "available groups" default landing page

	protected $styles = array("basic.css", "audit.css", "doc.css",
	"landing.css");
		// Stylesheets to apply

	protected $link_to = "http://snltd.co.uk/s-audit/";

	protected $h_links = array(
		"docs" => "documentation home");

	protected $link_root = ROOT_URL;

	static function close_page()
	{
		echo "</div></div>" . Page::spacer() . indexPage::add_footer() .
		"\n\n</body>\n</html>";
	}

}

//----------------------------------------------------------------------------

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
		$this->all_d = Filesystem::get_files(DOC_ROOT, "d");
		$this->all_f = Filesystem::get_files($this->my_d, "f");
		$this->fs = new filesystem();
    }

	public function print_list()
	{
		$ret = "\n<ul class=\"vmd\">";

		foreach($this->all_d as $d) {
			$da = $this->fs->all_fnames($d);

			// Ignore "hidden" directories. That is, ones whose names start
			// with an underscore

			if (preg_match("/^_/", $da["link"]))
				continue;

			$da["link"] = preg_replace("/^\d+_/", "", $da["link"]);

			$ret .= ($this->my_d == $d)
				? "\n  <li class=\"thispage\">$da[link]</li>"
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
			$da = $this->fs->all_fnames($f);
			eval($this->fs->getline($da["file"], "menu_entry"));

			$ret .= ($f == $this->my_f)
				? "\n  <li class=\"thispage\">$menu_entry</li>"
				: "\n  <li><a href=\"$da[url]\">$menu_entry</a></li>";
		}

		return $ret . "\n</ul></li>";
	}

}

//----------------------------------------------------------------------------

class navigateHoriz {

	// Print the link bar across the top of the page. Sometimes this is
	// generated dynamically, other times it's static. You just feed the
	// constructor an array of "filename => description".

	private $o;
		// Value of _GET[o]

	private $qs;
		// query string to tag on to links

	public function __construct($link_arr, $root = "")
	{
		// Second argument lets you give a common root to all the links

		$this->o = isset($_GET["o"]) ? $_GET["o"] : 0;
		$this->links = $link_arr;
		$this->root = $root;

		// Add in the s-monitor page, if it exists.

		if (file_exists(ROOT . "/monitor/index.php"))
			$this->pages["row2"]["/monitor/index.php"] = "Monitor";

		$this->qs = new queryString();
	}

	public function display_navbar()
	{
		// Make an unordered list of everything in the $links array. CSS
		// will do the rest

		$ret = "\n<ul class=\"navlist\" id=\"navlist\">";
		$here = $_SERVER["PHP_SELF"];

		if (is_array($this->links)) {

			foreach($this->links as $pg => $txt) {

				$match = "$this->root/$pg";

				// If match doesn't end .php, assume it's a directory, and
				// tag on the name of this page

				if (!preg_match("/\.php$/", $match))
					$match .= "/" . basename($_SERVER["PHP_SELF"]);

				// Don't link to the page we're already on.  You can get
				// multiple "/"s in the match string

				$ret .= (preg_replace("/\/{2,}/", "/", $match) == $here)
					? "\n<li class=\"here\">$txt</li>"
					: "<li><a href=\"$this->root/${pg}$this->qs\">$txt"
					. "</a></li>";

			}
		}

		return $ret . "</ul>";
	}

}

//----------------------------------------------------------------------------

class queryString {

	// This class generates HTTP query strings for links to other audit
	// pages, and also to turn zone display on and off

	private $qs;

	public function __construct($tz = false)
	{
		// Generate a query string, carrying through any of these values:
		// h = hide zones/common data (1 == hide them)
		// o = offset of first server (i.e. don't show first x)
		// g = name of server group

		// set $tz is you want to toggle 'h'

		$qs = false;

		// o is always carried through as-is

		if (isset($_GET["o"])) $qs = "o=$_GET[o]";

		// g is always carried through as-is

		if (isset($_GET["g"])) {

			if ($qs) $qs .= "&amp;";

			$qs .= "g=$_GET[g]";
		}
		elseif (isset($_POST["g"])) {

			if ($qs) $qs .= "&amp;";

			$qs .= "g=$_POST[g]";
		}

		// h may be carried through, or toggled if $tz is set

		if ($tz) {

			if (!isset($_GET["h"])) {

				if ($qs) $qs .= "&amp;";

				$qs .= "h=1";
			}

		}
		elseif (isset($_GET["h"])) {

			if ($qs) $qs .= "&amp;";

			$qs .= "h=1";
		}


		$this->qs = (empty($qs))
			? ""
			: "?" . $qs;
	}

	public function __toString()
	{
		return $this->qs;
	}


}

//----------------------------------------------------------------------------

class html {

	public function dialog_submit($name, $value)
	{

		// code to produce a generic submit button

		return "\n<input type=\"submit\" name=\"$name\" value=\"$value\" />";
	}

	public function dialog_form($page, $method = "post")
	{

    	// open a form

		return "\n<form action=\"$page\" method=\"$method\">\n";
	}

	public function dialog_hidden($name, $value) {

		// return correct HTML for a HIDDEN FORM field

		return "\n<input type=\"hidden\" name=\"$name\" value=\"$value\" />";
	}

	public function dialog_cycle($name, $data, $default = false)
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

//----------------------------------------------------------------------------

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

//----------------------------------------------------------------------------

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

		if ($this->span && $this->span > 1)
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
		return "\n<td";
		//return "\n<td align=\"center\" valign=\"top\"";
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

//----------------------------------------------------------------------------

class listCell {

	// put lists in cells for multiples

	private $html;

	public function __construct($data, $lclass = false, $span = 1, $nofill
	= false)
	{
		// Args are:

		// $data     - array containing data in the same form Cell:: expects
		//             it
		// $lclass   - the class for the list itself (the <ul> element)
		// $span     - colspan passed to Cell::
		// $noexpand - if this is true, a single element will still be put
		//             into a list (that is, it won't be expanded to fill
		//             the entire cell)

		// Don't do "smallaudit" classes in single server view

		if (defined("SINGLE_SERVER"))
			$lclass = preg_replace("/^small/", "", $lclass);

		// If there's only one element in the $data class and $noexpand
		// isn't set, just do a plain cell

		if ($data == false)
			$this->html = new Cell();
		elseif (sizeof($data) == 1 && !$nofill) {
			$a = array();

			if (is_string($data[0])) $data[0] = array($data[0]);

			for ($i = 0; $i < 7; $i++) {

				$a[$i] = isset($data[0][$i])
					? $data[0][$i]
					: false;
			}

			$this->html = new Cell($a[0], $a[1], $a[2], $a[3], $a[4], $a[5],
			$a[6]);
		}

		else {
			$h = "\n\n<ul";

			if ($lclass) $h .= " class=\"$lclass\"";

			$h .= ">";

			if (is_array($data[0])) {

				foreach($data as $arr) {
					$h .= "\n  <li";

					if (isset($arr[1]) && ($arr[1]))
						$h.= " class=\"$arr[1]\"";

					if (isset($arr[2]) && $arr[2])
						$h .= " style=\"$arr[2]\"";

					if (isset($arr[5]))
						$h .= " title=\"$arr[5]\"";

					$h .= ">$arr[0]</li>";
				}
			}
			else {
				foreach($data as $txt) $h .= "\n  <li>$txt</li>";
			}

			$this->html = new Cell($h . "\n</ul>\n", "nopad",
			false, false, $span);
		}

	}

	public function __toString()
	{
		return (string) $this->html;
	}

}

class singleServerLink {

	// return a link to the single-server view of the given host. If the
	// host isn't known to the system, return plain text

	protected $html;

	public function __construct($host, $class = false, $parent = false)
	{
		$map = ZoneMap::getInstance();

		$s_name = ($parent) ? "${parent}/$host" : $host;

		if (!$map->has_data($s_name))
			$this->html = $host;
		else {
			$qs = "g=" . $_GET["g"] . "&amp;s=";

			$qs .= ($parent)
				? "$host@$parent"
				: "$host";

			$this->html = "<a ${class}href=\"single_server.php?$qs\">$host</a>";
		}
	}

	public function __toString()
	{
		return $this->html;
	}

}

function pr($var)
{
	echo "<pre>", print_r($var), "</pre>";
}
?>
