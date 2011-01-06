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
// DATA COLLECTION





//----------------------------------------------------------------------------
// DATA DISPLAY

Class serverListGrid
{
	// This class displays a grid of server and zone names known to the
	// system. It doesn't extend the existing grid classes because it's
	// completely different. For a start, it uses a merged map of both live
	// and obsolete servers.

	private $map;		// merged map of live and obsolete servers
	private $l_map;		// map of live servers

	public function __construct($m_map, $l_map)
	{
		$this->m_map = $m_map;
		$this->l_map = $l_map;
	}
	
	public function show_grid()
	{
		// This function prints out a whole grid, ready for echoing.

		$ret = "\n\n<table width=\"70%\" align=\"center\">";

		// Loop through all the physical servers, calling a function which
		// will return us the HTML for that server's zones

		foreach($this->m_map->list_globals() as $server)
			$ret .= $this->show_server($server);
		
		return $ret . "\n</table>\n";
	}

	private function show_server($server)
	{
		// Return HTML <table> rows in which each element contains a
		// clickable link to a zone which is part of the server given as the
		// only argument. A new row is started when HOST_COLS global zones
		// have been handled
		// Get all the zones belonging to this server

		$zones = $this->m_map->list_server_zones($server);
		
		// Get the zones for this server, put them in alphabetical order,
		// and work out whether or not we have to pad out the last row. 

		if ($zones) {
			sort($zones);
			$zc = sizeof($zones);
			$last_row = $zc % HOST_COLS;
			$pad = ($last_row > 0) ? (HOST_COLS - $last_row) : 0;
		}
		else {
			$zones = array();
			$pad = HOST_COLS;
		}

		// Is this a live zone or an obsolete one? We look at the live list
		// rather than obsolete because, if a server's somehow got into
		// both, we'd rather see the live one.

		$gclass = (!in_array($server, $this->l_map->list_globals()))
			? "osvhn"
			: "svhn";

		$ret = "<tr>" . new Cell($this->s_link($server), $gclass);
		$i = 0; // column count. Should not exceen HOST_COLS

		// Make up a valid HTML, properly padded, table.

		foreach($zones as $z) {

			// Is this zone live?

			$zclass = (!in_array($z, $this->l_map->list_locals()))
				? "ozhn"
				: "zhn";

			// here's the table cell with the clickable link

			$ret .= new Cell($this->s_link($z), $zclass);

			// handle row padding. This probably isn't scrictly necessary, I
			// think all browsers handle short rows properly, but I like to
			// do things RIGHT!

			if (++$i % HOST_COLS == 0) {
				$ret .="</tr>";
				
				// If there are more local zones to come, indent the row

				if ($i < $zc)
					$ret .= "\n<tr><td></td>";
			}

		}

		// add on the padding cell

		if ($pad > 0)
			$ret .= new Cell(false, false, false, false, $pad);

		return $ret;
	}
	
	public function s_link($server)
	{
		// Just returns an HTML link to this page, with the right query
		// string to display the named server.

		return "<a href=\"$_SERVER[PHP_SELF]?s=$server\">$server</a>";
	}

}

?>
