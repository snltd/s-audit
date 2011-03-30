<?php

//============================================================================
//
// compare_classes.php
// -------------------

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

class compareView extends HostGrid {
	
	// This class groups together all the functions needed to compare two
	// servers, and display the results of that comparison

    private $sa;
    private $sb;
        // The two servers on which we're doing an a/b comparison

    private $omit = array("hostname", "serial number");
        // Fields we never want to compare

	private $no_col = array("hostid", "packages", "patches", "hardware",
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
		$this->map = $map;

		// The columns are the hostnames of the two servers

        $this->fields = array(
            $this->sa["platform"]["hostname"][0],
            $this->sb["platform"]["hostname"][0]
        );
    }

    public function grid_header()
    {
		// We override the HardwareGrid function because we want to specify
		// column widths

        return "\n<tr><td></td><th width=\"40%\">". $this->fields[0]
		. "</th><th width=\"40%\">". $this->fields[1] . "</th></tr>";
    }

    public function grid_body()
    {
		// Loop through each audit class

		$ret = "";

		foreach($this->sa as $type=>$data) {
			$class = "compare$type";
	
			if (!class_exists($class))
				$class = "compareGeneric";
				
			$ret .= new $class($this->map, $data, $this->sb[$type]);
		}

		return $ret;
    }

	protected function grid_key()
	{
		// We don't currently have a key
		
		return false;
	}

    public function compare_bar($all_zones, $z1, $z2)
    {
		// This is the bar at the bottom of the screen that lets you choose
		// the servers to compare from cycle gadgets.
		
		$html = new html;

        return "\n<p>"
        . $html->dialog_form($_SERVER["PHP_SELF"])
        . $html->dialog_submit("c", "compare")
        . $html->dialog_cycle("z1", $all_zones, $z1, false) . " with "
        . $html->dialog_cycle("z2", $all_zones, $z2, false)
        . "</form>\n</p>";
    }

}

class compareGeneric extends HostGrid {

	protected $da;
	protected $db;
		// Data for zone A and zone B
	
	protected $html;
		// HTML we return

	protected $omit = array("hostname", "audit completed", "fs");
		// These fields are not shown
	
	protected $no_col = array();

	public function __construct($map, $da, $db)
	{
		// First off get a list of rows. There may be elements in $da that
		// aren't in $db and vice versa

		$rows = array_unique(array_merge(array_keys($da), array_keys($db)));

		$this->da = $da;
		$this->db = $db;

		$this->cols = new Colours;

		$this->map = $map;

		foreach($rows as $row) {
			$this->html .= $this->compare_row($row);
		}

	}

    public function compare_row($row)
    {
		// This function does most of the work in comparing servers. It
		// is fed in the name of the data to compare ($row -- it's the
		// left-hand column label on the grid, and it's also the key of the
		// arrays we're comparing). If looks to see if a dedicated method
		// exists for comparing the data, and if not, it does the comparison
		// itself.  Watch out for multiple exits!

        $a = $b = false;

		// It's possible that we don't even want to compare this data

        if (in_array($row, $this->omit))
            return;

		// A bit of shorthand

        if (isset($this->da[$row]))
            $a = $this->da[$row];

        if (isset($this->db[$row]))
            $b = $this->db[$row];

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

		$class = (preg_match("/colspan/", $fields))
			? "solidgreen"
			: "solidred";

		return ($fields)
			? "\n<tr class=\"server\"><td class=\"$class\" "
			. "width=\"20%\">$row</td>$fields</tr>"
			: false;
    }

    public function compare_generic($row, $a, $b)
    {
		// Generic comparison function. Colours the highest numbered field
		// green and the lowest red, unless the row we're studying is in the
		// no_cols[] array

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
				$ret = $this->show_col($row, $a, 2);
            }
            else {

				// Unless we've been asked not to, compare $a and $b. 
				// Colour the larger number green and the smaller red.
				// Because we're dealing with funny version numbers, a
				// direct "<" comparison can't be trusted

                if (!in_array($row, $this->no_col) && $a != "" && $b != "") {

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

	private function show_col($row, $data, $span = false)
	{
		// Use the show_ functions to present data

		$method = "show_$row";

		if ($span) {

			$ret = (method_exists($this, $method))
				? preg_replace("/<td/", "<td colspan=\"2\"", 
				$this->$method($data))
				: new listCell($data, false, 2);
			
		}
		else {

			$ret = (method_exists($this, $method))
				? $this->$method($data)
				: new listCell($data);
		}

		return $ret;

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
			$ret = $this->show_col($row, $a, 2);
        }
        else {
            $diffed_a = array_diff($a, $b);
            $diffed_b = array_diff($b, $a);
            $combined = array_merge($diffed_a, $diffed_b);
            sort($combined);

            $col_a = $col_b = "\n<ul>";

            $i = 1;
            $rows = sizeof($combined);

            foreach($combined as $element) {

                $prt = $element;

                if ($fn)
                    $prt = $this->$fn($prt);

                $col_a .= "\n  <li>";
                $col_b .= "\n  <li>";

                // It's in one list or the other. Otherwise, we wouldn't be
                // here

                if (in_array($element, $diffed_a)) {
                    $col_a .= $prt;
                    $col_b .= "&nbsp;";
                }
                else {
                    $col_a .= "&nbsp";
                    $col_b .= $prt;
                }

                $col_a .= "</li>";
                $col_b .= "</li>";

            }
            $ret = new Cell("${col_a}\n</ul>") . new
            Cell("${col_b}\n</ul>");
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

	public function __toString()
	{
		return $this->html;
	}

}

class comparePlatform extends compareGeneric{
	
	protected $no_col = array("hardware", "CPU", "serial number", "ALOM IP");
}

class compareOS extends compareGeneric{
	
	protected $no_col = array("hostid", "uptime");
}

class compareNet extends compareGeneric{
	
	protected $no_col = array("NIC");
}


/*

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

    protected function $ret["a"] .= new Cell($a_prt, $myclass);
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

	*/


?>
