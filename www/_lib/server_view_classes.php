<?php

//============================================================================
//
// server_view_classes.php
// -----------------------
//
// Classes which display complete overviews of full servers and zones.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
//============================================================================

//----------------------------------------------------------------------------
// DATA DISPLAY

Class serverListGrid
{
	// This class displays a grid of server and zone names known to the
	// system. It doesn't extend the existing grid classes because it's
	// completely different.

	private $map;		// map of all servers
	private $gkey;		// grid key
	private $cols; 		// columns in table

	public function __construct($map)
	{
		$this->map = $map;
		require_once(KEY_DIR . "/key_server.php");
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
			$class = "ldom";
		else
			$class = "svhn";

		$ret = "<tr>" . new Cell($this->s_link($server), $class);

		// Now do the local zones

		foreach($zones as $z) {
			$ret .= new Cell($this->s_link($z), "zhn");

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
	
	private function s_link($server)
	{
		// Just returns an HTML link to this page, with the right query
		// string to display the named server.

		return "<a href=\"$_SERVER[PHP_SELF]?s=$server\">$server</a>";
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
