<?php

//============================================================================
//
// s-audit/hosted.php
// ------------------
//
// Hosted services audit base page.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");

$map = new ZoneMap();
$s = new GetServers($map, false, array("hosted", "fs"));
$grid = new HostedGrid($map, $s->get_array(), "hosted");

$pg = new audPage("hosted services", $grid->server_count(),
$grid->prt_toggle());

$um = "<a href=\"" . DOC_URL . "/04_extras/uri_map_file.php" .
"\">URI map file</a>";

$umf = $map->get_path("uri_map");

if (file_exists($umf)) {
	$f_info = stat($umf);

	$txt = (($f_info["size"]) == 0)
		? "You have a $um file, but it contains no entries."
		: "This table incorporates data from a $um generated at "
		. date("H:i D d/m/Y", filemtime($umf));
}
else
	$txt = "You do not have a $um";

echo "\n<p class=\"center\">${txt}.</p>", $grid->show_grid();

$pg->close_page();

?>
