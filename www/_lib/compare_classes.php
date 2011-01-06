<?php

//============================================================================
//
// compare_classes.php
// -------------------
//
// Classes which compare a pair of servers, highlighting differences and
// formatting information.
//
// Requires the general_audit_classes.php, hardware_classes.php and
// general_classes.php files.
//
// R Fisher 2009
//
// Please record changes below.
//
// v1.0  Initial release.
//
// v1.1  Fixed smart_compare function. Fixed to work with hardware/os audits
//       instead of platform. Changed eregs to pregs. Added compare_zone()
//       and compare_ldom(). RDF 09/12/09.
//
//============================================================================

//----------------------------------------------------------------------------
// DATA DISPLAY

//- "Normal" - i.e. list context ---------------------------------------------

class CompareList extends HostGrid
{
	// This class creates and displays a list of paired servers which can be
	// compared. Single column

    protected $fields = array("server pair");
		// this is the only column in this table

    protected $friends = array(
		"cs-db-01" => "cs-db-02",
		"cs-infra-01" => "cs-infra-02",
		"cs-w-01" => "cs-w-02"
		);
		// "friends" are pairs of servers which the user is likely to want
		// to compare. They are used to generate the clickable "preset"
		// queries

    public function __construct($map, $friends) {
		// Just assign a couple of variables

        $this->map = $map;
    }

    public function show_grid($width = "30%")
    {
		// Return the HTML that prints the list

        return $this->grid_head($width) . $this->grid_body()
		.  $this->grid_foot();
    }

    public function grid_body()
    {
		// Build up the body of the grid. That is, the list of server pairs,
		// each of which is clickable.

        $ret_str = "";

        foreach($this->friends as $a=>$b) {
            $link = $_SERVER["PHP_SELF"] . "?c=compare&amp;z1=${a}"
            . "&amp;z2=${b}";

            $class = ($this->map->is_global($a))
                ? "server"
                : "zone";

			$ret_str .= "\n<tr class=\"$class\">"
			. new Cell("<a href=\"$link\">$a and $b</a>") . "</tr>";
        }

        return $ret_str;
    }

}

//- "compare" context --------------------------------------------------------

class CompareGrid extends HardwareGrid {
	
	// This class groups together all the functions needed to compare two
	// servers, and display the results of that comparison

    private $sa;
    private $sb;
        // The two servers on which we're doing an a/b comparison

    private $omit = array("hostname", "serial number");
        // Fields we never want to compare

	private $nocol = array("hostid", "packages", "patches", "hardware",
	"ALOM IP", "NIC", "uptime", "zone type", "audit completed", "shared
	module", "local zone", "LDOM", "virtualization", "root fs", "MAC",
	"capacity");
		// fields we don't colour to denote differences

    protected $fields;
    protected $rows;

    protected $pairs = array(
        "packages" => "package",
        "patches" => "patch"
    );

    public function __construct($map, $servers)
    {
		// set up some variables and call the HardwareGrid constructor

        $this->sa = current($servers);
        $this->sb = next($servers);
        parent::__construct($map, $servers);
        $this->rows = $this->fields;

		// The columns are the hostnames of the two servers

        $this->fields = array(
            $this->sa["hostname"][0],
            $this->sb["hostname"][0]
        );
    }

    public function grid_header()
    {
		// We override the HardwareGrid function because we want to specify
		// column widths

        return "\n<tr><th></th><th width=\"40%\">". $this->fields[0]
		. "</th><th width=\"40%\">". $this->fields[1] . "</th></tr>";
    }

    public function grid_body()
    {
        // Build up the body of the comparison grid by repeatedly calling
		// compare_row with each row of data. $row is the name of the
		// element we want to compare

        $ret_str = "";

        foreach($this->rows as $row)
            $ret_str .= $this->compare_row($row);

        return $ret_str;
    }

    public function compare_row($row)
    {
		// This function does most of the work in comparing servers. It
		// is fed in the name of the data to compare ($row -- it's the
		// left-hand column label on the grid, and it's also the key of the
		// arrays we're comparing). If looks to see if a dedicated method
		// exists for comparing the data, and if not, it does the comparison
		// itself). If looks to see if a dedicated method exists for
		// comparing the data, and if not, it does the comparison itself.
		// Watch out for multiple exits!

        $a = $b = false;

		// It's possible that we don't even want to compare this data

        if (in_array($row, $this->omit))
            return;

		// A bit of shorthand

        if (isset($this->sa[$row]))
            $a = $this->sa[$row];

        if (isset($this->sb[$row]))
            $b = $this->sb[$row];

		// Is there any data to compare? if not, let's go.

		if (!$a && !$b)
			return;

		// Some fields have special functions which we use to compare them.
		// The rest use the compare_generic() method. Work out what that
		// method should be called, see if it exists, and call

		$method = preg_replace("/\s/", "_", "compare_$row");

		$fields = (method_exists($this, $method))
			? $this->$method($row, $a, $b)
			: $this->compare_generic($row, $a, $b);

		return ($fields) ? "\n<tr class=\"server\"><td class=\"comprow\" "
		. "width=\"20%\">$row</td>$fields</tr>" : false;
    }

    protected function compare_uptime($row, $a, $b, $fn = false)
	{
		// Use the display function to make uptime nicely human-readable

		return $this->show_uptime($a) . $this->show_uptime($b);
	}

	protected function compare_fs($row, $a, $b)
	{
		$ao = $bo = array();

		if (is_array($a)) {
			sort($a);

			foreach ($a as $el) {
				$z = preg_split("/\s+/", $el, 2);
				$ao[] = "<div><strong>$z[0]</strong></div><div>$z[1]</div>";
			}

		}
		
		if (is_array($b)) {
			sort($b);

			foreach ($b as $el) {
				$z = preg_split("/\s/", $el, 2);
				$bo[] = "<div><strong>$z[0]</strong></div><div>$z[1]</div>";
			}

		}

		return new multicell($ao, "smallrow") . new multicell($bo, "smallrow");
	}

	public function compare_local_zone($row, $a, $b)
	{
		// Compare zones. Just sort the lists and print them side by side,
		// with a bit of nice formatting. Also works for LDOMs.

		$ao = $bo = array();

		if (is_array($a)) {
			sort($a);

			foreach ($a as $el) {
				$z = preg_split("/\s+/", $el, 3);
				$ao[] = "<div><strong>$z[0]</strong></div><div>$z[1]</div>"
				. "<div>$z[2]</div>";
			}

		}
		
		if (is_array($b)) {
			sort($b);

			foreach ($b as $el) {
				$z = preg_split("/\s/", $el, 3);
				$bo[] = "<div><strong>$z[0]</strong></div><div>$z[1]</div>"
				. "<div>$z[2]</div>";
			}

		}

		return new multicell($ao, "smallrow") . new multicell($bo, "smallrow");
	}

	protected function compare_ldom($row, $a, $b)
	{
		// I refer you to compare_zone();

		return $this->compare_local_zone($row, $a, $b);
	}

    public function compare_generic($row, $a, $b)
    {
        // Generic comparison function. Colours the highest numbered field
        // green and the lowest red, unless the row we're studying is in the
        // nocols[] array

		// If we've been given arrays with multiple elements, pass lists to
		// the compare_lists() function

        if (sizeof($a) != 1 || sizeof($b) != 1) {
            $ret = $this->compare_lists($row, $a, $b);
        }
        else {

			// We have single element arrays. Convert them to strings, trim
			// them, and if we're left with just a "-", set it to a blank
			// string

            $a = trim($a[0]);
            $b = trim($b[0]);

            // Do nothing if both fields are empty strings

            if ($a == "" && $b == "")
                $ret = false;

			// If both strings are the same, put them in a green box

            elseif ($a == $b) {
                $ret = new Cell($a, "boxgreen", false, false, 2);
            }
            else {

				// Unless we've been asked not to, compare $a and $b. 
				// Colour the larger number green and the smaller red.
				// Because we're dealing with funny version numbers, a
				// direct "<" comparison can't be trusted

                if (!in_array($row, $this->nocol) && $a != "" && $b != "") {

                    if ($this->safe_compare($a, $b)) {
                        $lcol = "solidgreen";
                        $rcol = "solidred";
                    }
                    else {
                        $lcol = "solidred";
                        $rcol = "solidgreen";
                    }

                }
                else
                    $lcol = $rcol = false;

                $ret = new Cell($a, $lcol) .  new Cell($b, $rcol);
            }

        }

        return $ret;
    }

	public function safe_compare($a, $b)
	{
		// If you're comparing version numbers and you hit, say, 2.2.4 and
		// 2.2.11, a normal >/< type comparison will tell you 2.2.4 is the
		// later version, which it plainly isn't. This functon uses PHP's
		// natural sort algorithm to get the higher version. It also ignores
		// anything that's not part of the version string. (i.e. anything
		// after the first space)
		
		// returns true if a > b
		// returns false otherwise

		$ea = preg_replace("/ .*$/", "", $a);
		$eb = preg_replace("/ .*$/", "", $b);

		$arr = array($a, $b);
		natsort($arr);

		return (current($arr) == $a) ? false : true;
	}

    protected function compare_nic($row, $a, $b)
    {
        // We don't do a comparison, just embed the NIC stuff in the table.
        // Probably useless, but looks nice.

        return HardwareGrid::show_NIC($a) . HardwareGrid::show_NIC($b);
    }

	protected function compare_cpu($row, $a, $b)
	{
		// Compare CPUs. The "best" one is the one with the highest value
		// returned by get_cpu_speed_mhz(). See that function for
		// disclaimer.

		$sa = $this->get_cpu_speed_mhz($a[0]);
		$sb = $this->get_cpu_speed_mhz($b[0]);

		if ($sb == $sa)
			return new Cell($a[0], "boxgreen", false, false, 2);
		elseif ($sa > $sb) {
			$lcol = "solidgreen";
			$rcol = "solidred";
		}
		else {
			$lcol = "solidred";
			$rcol = "solidgreen";
		}
				
		return new Cell($a[0], false, $lcol) . new Cell($b[0], false, $rcol);
			
	}

	private function get_cpu_speed_mhz($str)
	{
		// Feed it a CPU speed string in any of the forms produced by the
		// auditor, and it will return an effective speed in MHz. Clock
		// speed * cores. I know it's not really that simple, but this does
		// the job.

		// If there's a space in the string, we have multiple cores or
		// multiple CPUs

		if (preg_match("/\s/", $str)) {
			preg_match("/^(\d+)\s.*\s(.*)$/", $str, $s);
			$cores = $s[1];	
			$speed = $s[2];
		}
		else {
			$cores = 1;
			$speed = $str;
		}
	
		// Get the clock speed in MHz. They can be supplied from the auditor
		// script in GHz

		$hz = preg_replace("/\D/", "", $speed);

		if (preg_match("/ghz/i", $speed))
			$hz = $hz * 1024;

		return $hz * $cores;
	}

    protected function compare_patch($row, $a, $b)
    {
		// This is fairly involved. We're essentially comparing two arrays
		// of patch numbers, but it gets a bit more complicated than that.
		// We generate two lists, one of patches only on server a, one of
		// patches on server b, then use those lists to create two embedded
		// tables of patches, one for each server

        // If the patches are the same, we're done

        if ($a == $b) {
            $a = $b = array("identical (" . sizeof($a) . " patches)");
            return $this->compare_generic("patches", $a, $b);
		}

		// Look at both patch list and from them produce arrays of the form
		// patch_number => revision.  All revisions are recorded

		// Remove the identical patches by making two lists of patches which
		// are only on server a and only on server b

		$only_on_a = array_diff($a, $b);
		$only_on_b = array_diff($b, $a);

		// Now get a numerical ordered, unique list of the patch NUMBERS
		// which occur on only one server

		$patch_arr = array();

		foreach(array_merge($only_on_a, $only_on_b) as $patch) {
			$pnum = preg_replace("/-\d{2}$/", "", $patch);

			if (!in_array($pnum, $patch_arr))
				$patch_arr[] = $pnum;
		}

		sort($patch_arr);

		// Make two more associative arrays, full_a and full_b, which are
		// made up of arrays whose key is a patch number and whose value(s)
		// are the revisions of those patches on server

		$lists = array("a", "b");

		foreach($lists as $list) {
			$full = "full_$list";
			$l = "only_on_$list";
			$data = $$l;
			$$full = array();
			sort($data);

			// Because we sorted the patch array, we hit the lowest revision
			// first.

			foreach($data as $patch) {
				preg_match("/^(\d{6})-(\d{2})$/", $patch, $match);
				${$full}[$match[1]][] = $match[2];
			}

		}

		$i = 1;
		$rows = sizeof($patch_arr);

		// Build up two tables, one for server a and one for server b, which
		// we will nest in the big table.

		$ta = $tb = "\n<table width=\"100%\" cellpadding=\"0\" " .
		"cellspacing=\"0\">";

		foreach($patch_arr as $num) {
			$on_a = $on_b = false;

			if (in_array($num, array_keys($full_a)))
				$on_a = $full_a[$num];

			if (in_array($num, array_keys($full_b)))
				$on_b = $full_b[$num];

			$class = ($i++ < $rows) ? "rowbar" : false;
			$rows = $this->display_patch($num, $class, $on_a, $on_b);
			$ta .= $rows["a"];
			$tb .= $rows["b"];
		}

        $ta .= "\n</table>";
        $tb .= "\n</table>";

        return new Cell($ta) . new Cell($tb);
    }

    protected function display_patch($num, $myclass, $on_a = false, $on_b =
    false)
    {
		// This function is called once for each patch number, and it
		// returns two strings in an array, one string for each server.
		// Those strings, $a and $b, will be an equal number of HTML table
		// rows.

        $ret= array("a" => "", "b" => "");

		// Reverse-sort the two patch lists we've been given

		/*
        if (is_array($on_a)) rsort($on_a);
        if (is_array($on_b)) rsort($on_b);
		*/

        $rows = (sizeof($on_a) > sizeof($on_b))
            ? sizeof($on_a)
            : sizeof($on_b);

		// For each patch, start a new HTML table row.

        for($i = 0; $i < $rows; $i++) {
            $ret["a"] .= "\n<tr>";
            $ret["b"] .= "\n<tr>";

            $a = (isset($on_a[$i])) ? $on_a[$i] : false;
            $b = (isset($on_b[$i])) ? $on_b[$i] : false;

            $a_prt = "${num}-$a";
            $b_prt = "${num}-$b";

            if ($i > 1) {
                $a_prt = "($a_prt)";
                $b_prt = "($b_prt)";
            }
            else {
                $a_prt = $this->patch_link($a_prt);
                $b_prt = $this->patch_link($b_prt);
            }

            if ($a && $b) {

                if ($a > $b) {
                    $acolr = "solidgreen";
                    $bcolr = "solidred";
                }
                else {
                    $acolr = "solidred";
                    $bcolr = "solidgreen";
                }

                $ret["a"] .= new Cell($a_prt, $myclass, $acolr);
                $ret["b"] .= new Cell($b_prt, $myclass, $bcolr);
            }
            elseif($a) {
                $ret["a"] .= new Cell($a_prt, $myclass);
                $ret["b"] .= new Cell("&nbsp;", $myclass);
            }
            elseif($b) {
                $ret["a"] .= new Cell("&nbsp;", $myclass);
                $ret["b"] .= new Cell($b_prt, $myclass);
            }

            $ret["a"] .= "</tr>";
            $ret["b"] .= "</tr>";
        }

        return $ret;

    }

    protected function package_link($name)
    {
        // Make a chunk of HTML to have little mouseover descriptions of
        // package names. Requires the pkg_defs.php file, which must be
        // generated on cs-build-01

        global $pkgdefs;

        $tip = "unknown package";

        if (isset($pkgdefs)) {

            if (isset($pkgdefs[$name])) {
                $tip = $pkgdefs[$name];
            }

        }

        return "<div title=\"$tip\">$name</div>";
    }

    protected function patch_link($prt)
    {
        // Turns patch numbers into clickable links to sunsolve

        return "<a href=\"http://sunsolve.sun.com/search/document.do"
        . "?assetkey=1-21-${prt}-1\">$prt</a>";
    }

    protected function compare_database($row, $a, $b)
	{
		// Just format a list of databases.

        return $this->compare_trimmed_lists($row, $a, $b);
    }

    protected function compare_website($row, $a, $b)
	{
		// Just format a list of sites.

        return $this->compare_trimmed_lists($row, $a, $b);
    }

    protected function compare_trimmed_lists($row, $a, $b)
    {
        // Strip off all extraneous data and pass what's left (after
        // removing duplicates) to the compare_lists function

        $call_a = $call_b = array();

        if (!is_array($a))
            $a = array();

        if (!is_array($b))
            $b = array();

        foreach($a as $el)
            $call_a[] = preg_replace("/ .*$/", "", $el);

        foreach($b as $el)
            $call_b[] = preg_replace("/ .*$/", "", $el);

        return $this->compare_lists($row, array_unique($call_a),
        array_unique($call_b));
    }

    protected function compare_package($row, $a, $b)
    {
        return $this->compare_lists($row, $a, $b, "package_link");
    }

    protected function compare_lists($row, $a, $b, $fn = false)
    {
        // Compare lists of things on two servers. This is a simplified
        // version of compare_patch. See that function for comments.

        if (!is_array($a))
            $a = array();

        if (!is_array($b))
            $b = array();

        if ($a == $b) {
            $a = $b = array("identical (" . sizeof($a) . " ${row}s)");
            $ret = $this->compare_generic($row, $a, $b);
        }
        else {
            $diffed_a = array_diff($a, $b);
            $diffed_b = array_diff($b, $a);
            $combined = array_merge($diffed_a, $diffed_b);
            sort($combined);

            $col_a = $col_b = "\n<table width=\"100%\" cellpadding=\"0\" "
            . "cellspacing=\"0\">";

            $i = 1;
            $rows = sizeof($combined);

            foreach($combined as $element) {

                $prt = $element;

                if ($fn)
                    $prt = $this->$fn($prt);

                $class = ($i++ < $rows) ? "rowbar" : false;
                $col_a .= "<tr>";
                $col_b .= "<tr>";

                // It's in one list or the other. Otherwise, we wouldn't be
                // here

                if (in_array($element, $diffed_a)) {
                    $col_a .= new Cell($prt, $class);
                    $col_b .= new Cell("&nbsp;", $class);
                }
                else {
                    $col_a .= new Cell("&nbsp;", $class);
                    $col_b .= new Cell($prt, $class);
                }
                $col_a .= "</tr>";
                $col_b .= "</tr>";

            }
            $ret = new Cell("${col_a}\n</table>") . new
            Cell("${col_b}\n</table>");
        }

        return $ret;
    }

    public function compare_bar($all_zones, $z1, $z2)
    {
		// This is the bar at the bottom of the screen that lets you choose
		// the servers to compare from cycle gadgets.

        return "\n<div class=\"zonetog\">"
        . html::dialog_form($_SERVER["PHP_SELF"])
        . html::dialog_submit("c", "compare")
        . html::dialog_cycle("z1", $all_zones, $z1, false) . " with "
        . html::dialog_cycle("z2", $all_zones, $z2, false)
        . "</form>\n</div>";
    }

}

?>
