<?php

//============================================================================
//
// server_view_classes.php
// -----------------------
//
// Classes to show everything we know about a single host. Extends classes
// found in display_classes.php.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

//----------------------------------------------------------------------------
// SINGLE SERVER VIEW

class serverView extends HostGrid {

	// This class handles presentation of all audit files for a single
	// server or zone. It doesn't do a great deal of work itself, but
	// depends on the singleClasses to handle each audit type
	
	protected $hostname;
		// The name of this server or zone

	protected $parent;
		// if $hostname is a local zone, the name of the parent global

	protected $data;
		// The parsed audit data, in a big-ass array

	protected $map;
		// the usual map

	protected $gzd;
		// global zone platform and O/S audit data

	public function __construct($data, $map)
	{
		// Populate the variables above

		$this->hostname = $_GET["s"];

		$this->map = $map;
		$this->data &= $data;

		if (preg_match("/@/", $this->hostname)) {
			$zn = explode("@", $this->hostname);
			$this->hostname = $zn[0];
			$this->parent = $zn[1];
		}
		else
			$this->parent = $this->hostname;

		$this->zdata = $data[$this->hostname];
		$this->gzd = $data[$this->parent];
	}

	public function show_grid()
	{
		// The grid in this case is a list of tables, one for each audit
		// type. Each table is created by its own class

		$ret = false;

		foreach($this->zdata as $type=>$data) {
			$class = "single$type";

			if (!class_exists($class))
				$class = "singleGeneric";

			$ret .= new $class($type, $data, $this->map, $this->gzd);
		}

		return $ret;
	}
}

//----------------------------------------------------------------------------

class singleGeneric extends HostGrid {

	// This class isn't currently used, as I've created an extension class
	// for each audit type. It's left as it is though, because if a new
	// audit class is created, this class will automatically, if
	// imperfectly, display it

	protected $width = "700px";
		// the width of the table used to present the information

	protected $columns = 2;
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

	protected $gzd;
		// global zone's platform audit data

	public function __construct($type, $data, $map, $gzd)
	{
		// The printed name of the audit class comes from capitalizing the
		// first letter of the class name. This can be overriden by setting
		// $type in the inheriting class

		$this->cols = new Colours;

		if (!isset($this->type))
			$this->type = ucfirst($type);

		// Set some class variables

		$this->gzd =& $gzd;

		//if (isset($this->parent))
			//$this->gzo = $this->server

		$this->data = $data;
		$this->map = $map;

		// And start populating the $html variable with the title of this
		// audit class

		$this->html = "\n\n<table align=\"center\" width=\"$this->width\">"
		. "\n<tr><td><h1>$this->type audit</h1></td></tr>\n</table>\n";

		// Now call show_class() to get the table 

		$this->html .= $this->show_class();
	}

	protected function show_class()
	{

		$ret = "\n\n<table class=\"audit\" align=\"center\"" .
		" width=\"$this->width\">\n";

		if (sizeof($this->data) == 2)
			return $ret . "<tr>" . new Cell("No data") . "</tr>\n</table>\n";

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
					$ret .= new Cell(false, false, false, false,
					(($this->columns - $c) * 2)) . "</tr>\n<tr class=\"zone\">";

				$val_cell = preg_replace("/<td/", "<td colspan=\"" .
				(($this->columns * 2) - 1) . "\"", $this->show_cell($field,
				$val));
				
				$c = $this->columns;
			}
			else {
				$val_cell = $this->show_cell($field, $val);
				$c++;
			}

			$ret .= "<th>$field</th>" . $val_cell;

			if ($c == $this->columns || in_array($field, $this->one_cols)) {
				$ret .= "</tr>";
				$c = 0;
			}

		}

		if ($c !=0)
			$ret .= "</tr>";

		return $ret . $this->completed_footer($this->columns * 2);
	}

	protected function completed_footer($columns)
	{
		// Print an "audit completed" bar across the whole table
	
		return "\n<tr class=\"zone\"><th colspan=\"" . ($columns - 1)
		. "\">audit completed</th>"
		. $this->show_audit_completed($this->data["audit completed"])
		. "</tr>";
	}

	protected function show_cell($field, $val)
	{
		// Split out because it was overriden in the app/tool class. Isn't
		// any more, but still split out

		$method = preg_replace("/\W/", "_", "show_$field");

		return (method_exists($this, $method))
			? $this->$method($val)
			: $this->show_generic($val);
	}

	public function __toString()
	{
		return $this->html;
	}

}

//----------------------------------------------------------------------------

class singlePlatform extends singleGeneric {

	// Platform needs the card definition database
	
	public function __construct($type, $data, $map, $gzd)
	{
		require_once(LIB . "/defs.php");
		$defs = new defs();
		$this->card_db = $defs->get_data("card_db");
		parent::__construct($type, $data, $map, $gzd);
	}
}

//----------------------------------------------------------------------------

class singleOS extends singleGeneric {

	// Change the name and put zones  and LDOMs in a single column

	protected $type = "O/S";
	protected $one_cols = array("local zone", "LDOM");

	public function __construct($type, $data, $map, $gzd)
	{
		// Get the Solaris version defs

		require_once(LIB . "/defs.php");
		$defs = new defs();
		$this->sol_upds = $defs->get_data("updates");

		parent::__construct($type, $data, $map, $gzd);
	}

}

//----------------------------------------------------------------------------

class singleNet extends singleGeneric {

	protected $one_cols = array("NIC");

}

//----------------------------------------------------------------------------

class singleFS extends singleGeneric {
	protected $type = "Filesystem";
	protected $one_cols = array("fs", "export");
}

//----------------------------------------------------------------------------

class singleApp extends singleGeneric {

	// We use a flexible number of columns here, and override the ver_cols()
	// function because we don't want to do any cell colouring

	protected $type = "Application";

	public function __construct($type, $data, $map, $gzd)
	{
		$d = (sizeof($data) - 2);

		// We need the defs file for the Sun Studio version

		require_once(LIB . "/defs.php");
		$defs = new defs();
        $this->sun_cc_vers = $defs->get_data("sun_cc_vers");

		if ($d <= 4)
			$this->columns = $d;
		else
			$this->columns = 4;

		parent::__construct($type, $data, $map, $gzd);
	}

	protected function show_generic($data)
	{
		// Call the softwareGrid version, not the singleGeneric version

		return softwareGrid::show_generic($data, false);
	}

	public function ver_cols($data)
	{
		// Break a prog@=/path into prog and path, and put in an uncoloured
		// cell. Much simpler than the softwareGrid:: version

		$ta = preg_split("/@=/", $data);
		
		$path = (isset($ta[1]))
			? $ta[1]
			: false;

		return array($ta[0], false, false, false, false, $path);
	}

}

//----------------------------------------------------------------------------

class singleTool extends singleApp {

	// Just change the displayed name.

	protected $type = "Tool";
}

//----------------------------------------------------------------------------

class singleHosted extends singleGeneric {

	// Change the name and make websites and databases span the whole table

	protected $type = "Hosted Services";
	protected $one_cols = array("website", "database");
}

//----------------------------------------------------------------------------

class singleSecurity extends singleGeneric {

	// We have a few wide things to print and fold

	protected $one_cols = array("port", "cron job", "user_attr");
	protected $hard_fold = 80;

	protected function show_user($data)
	{
		// This needs a special function. We show ALL users and highlight
		// anything with UID 0

		$ret = "<ul>";

		foreach($data as $row) {

			$cl = (preg_match("/\(0\)$/", $row))
				? " class=\"solidred\""
				: false;

			$ret .= "\n  <li$cl>$row</li>";
		}

		return new Cell($ret . "</ul>");
	}

}

//----------------------------------------------------------------------------

class singlePatch extends singleGeneric
{
	// Patches and packages are handled in a special way. They're typically
	// very long lists

	protected $type = "Patch and Package";

	protected function show_class()
	{
		// This function handles patches and packages

		$ver = $hw = $ret = "";
		$blocks = sizeof($this->data) / 2;
		$i = 1;
		$hn = $this->data["hostname"][0];
		$pkg_arr = $hover_arr = array();

		// Get the operating system from the global zone's O/S audit

		$dist = $this->gzd["os"]["distribution"][0];

		foreach($this->data as $field=>$val) {

			if ($field == "hostname" || $field == "audit completed")
				continue;

			// Work out what the hover map is likely to be called

			$fn = ($this->map->is_global($hn))
				? "get_zone_prop"
				: "get_parent_prop";

			$hw = (preg_match("/SPARC/",
			$this->gzd["platform"]["hardware"][0]))
				? "sparc"
				: "i386";

			$ver = preg_replace("/^.*SunOS ([0-9.]*).*$/", "\\1",
			$os = $this->gzd["os"]["version"][0]);

			// How many columns? And do we have a "hover" map?

			//-- package lists -----------------------------------------------

			if ($field == "package") {
				$pdef = 5 ;	// 5 columns for packages
				$hover = PKG_DEF_DIR . "/pkg_def-${dist}-${ver}-${hw}.php";
			}
			//-- patch lists -------------------------------------------------
			else {
				$pdef = 7;	// 12 columns for patches
				$hover = PCH_DEF_DIR . "/pch_def-${ver}-${hw}.php";
			}

			// Include the hover map, if we have it

			if (file_exists($hover)) {
				$footnote = "Using definition file at $hover.";
				include_once($hover);
				$have_hover = true;
				// Get a list of hover map keys

				$hkeys = array_keys($hover_arr);
			}
			else {
				$footnote = "No definition file at $hover.";
				$have_hover = false;
			}

			$ret .= "<p class=\"center\">$footnote</p>";

			$columns = (sizeof($val) > $pdef)
				? $pdef
				: sizeof($val);

			$ret .= "\n\n<table class=\"plist\" align=\"center\" "
			. "width=\"$this->width\">"
			. "\n  <tr><th colspan=\"$columns\">$field</th></tr>";

			$c = 0;

			foreach($val as $p) {
				$fcol = false;
			
				if ($c == 0) 
					$ret .= "\n  <tr class=\"zone\">";

				// Highlight partially installed packages with a red border

				if (preg_match("/ \(/", $p)) {
					$bcol = inlineCol::box("red");
					$p = preg_replace("/ .*$/", "", $p);
				}
				else
					$bcol = false;

				// Highlight patches which don't start with a 1. These are
				// for things like NetBackup - i.e.  non-Sun products

				if ($field == "patch" && !preg_match("/^1/", $p))
					$fcol = "solidamber";

				if ($have_hover) {

					// Get ready to scan the hover map. Strip the revision
					// number off patches

					$pm = ($field == "package")
						? $p
						: substr($p, 0, 6);

					$pmnox = (preg_match("/x$/", $pm))
						? preg_replace("/x$/", "", $pm)
						: false;

					// If we have the package hover map, highlight packages
					// not in it. This should point to third-party software

					if ($field == "package" && ! in_array($pm, $hkeys))
					{
						
						// Some packages have a .u and a .v version for
						// SPARC, or i for i386. Some versions of Solaris
						// tagged "x" on for 64-bit. Look for those

						if (!in_array("${pm}.u", $hkeys) &&
						!in_array("${pm}.v", $hkeys) && !in_array("${pm}.i",
						$hkeys) && (($pmnox) && !in_array($pmnox, $hkeys)))
							$fcol = "solidamber";
					}

					// Now look to see if there's an entry in  the hover
					// map. We have to trim the revision off for patches

					$hover = (in_array($pm, $hkeys))
						? $hover_arr[$pm]
						: false;

				}
				else
					$hover = false;

				$ret .= new Cell($p, $fcol, $bcol, false, false, $hover);
				$c++;

				if ($c == $columns) {
					$ret .= "</tr>";
					$c = 0;
				}

			}

			if ($c > 0)
				$ret .= "</tr>";

			$ret .= "\n</table>\n\n<div class=\"spacer\">&nbsp;</div>";
		}

		return $ret;
	}

}

//============================================================================
// LIST OF SERVERS VIEW

Class serverListGrid
{
	// This class displays a grid of server and zone names known to the
	// system. It doesn't extend the existing grid classes because it's
	// completely different.

	private $map;		// map of all servers
	private $gkey;		// grid key
	private $columns; 		// columns in table

	public function __construct($map)
	{
		$this->map = $map;
		require_once(KEY_DIR . "/key_single_server.php");
		$this->gkey = $generic_key;

		// See how many zones each server has. The maximum will be the
		// number of columns in the table, unless SS_HOST_COLS is exceeded

		$high = 0;

		foreach($this->map->list_globals() as $g) {
			$i = count($this->map->list_server_zones($g));

			if ($i > SS_HOST_COLS) {
				$high = SS_HOST_COLS;
				break;
			}
			elseif ($i > $high)
				$high = $i;
		}

		$this->cols = $high;
	}
	
	public function show_grid()
	{
		// This function prints out a whole grid, ready for echoing.

		$ret = "\n\n<table class=\"ssall\" width=\"70%\" align=\"center\">";

		// Loop through all the physical servers, calling a function which
		// will return us the HTML for that server's zones

		foreach($this->map->list_globals() as $server)
			$ret .= $this->show_server($server);
		
		return $ret . $this->grid_key() . "\n</table>\n";
	}

	private function show_server($server)
	{
		// Return HTML <table> rows in which each element contains a
		// clickable link to a zone which is part of the server given as the
		// only argument. A new row is started when HOST_COLS global zones
		// have been handled
		// Get all the zones belonging to this server

		$zones = $this->map->list_server_zones($server);

		// Get the zones for this server, put them in alphabetical order,
		// and work out whether or not we have to pad out the last row. 

		if ($zones) {
			sort($zones);
			$zc = sizeof($zones);
			$last_row = $zc % $this->cols;
			$pad = ($last_row > 0) ? ($this->cols - $last_row) : 0;
		}
		else {
			$zones = array();
			$pad = $this->cols;
		}

		$i = 0; // column count. Should not exceed $this->cols

		// The first column is a global zone. But is it an LDOM or a Vbox?

		if (in_array($server, $this->map->list_vbox()))
			$class = "vb";
		elseif (in_array($server, $this->map->list_ldoms()))
			$class = "ldm";
		else
			$class = "svhn";

		$ret = "<tr>" . new Cell($this->s_link($server), $class);

		// Now do the local zones

		foreach($zones as $z) {
			$ret .= new Cell($this->s_link($z, $server), "zhn");

			// handle row padding. This probably isn't scrictly necessary, I
			// think all browsers handle short rows properly, but I like to
			// do things RIGHT!

			if (++$i % $this->cols == 0) {
				$ret .="</tr>";
				
				// If there are more local zones to come, indent the row

				if ($i < $zc)
					$ret .= "\n<tr><td></td>";
			}

		}

		// add on the padding cell, if we need one

		if ($pad > 0)
			$ret .= new Cell(false, false, false, false, $pad) . "</tr>";

		return $ret;
	}
	
	private function s_link($server, $parent = false)
	{
		// Just returns an HTML link to this page, with the right query
		// string to display the named server.

		$lserver= ($parent)
			? "${server}@$parent"
			: $server;

		return "<a href=\"$_SERVER[PHP_SELF]?s=$lserver\">$server</a>";
	}
	
	private function grid_key()
	{
		$ret = "\n\n<tr><td class=\"keyhead\" colspan=\"" . ($this->cols +
		1) . "\">key</td></tr>\n";
		
		return $ret . "\n<tr>" . new listCell($this->gkey["col_1"]) . new
		listCell($this->gkey["others"], false, $this->cols, 1) . "</tr>";
	}

}

?>
