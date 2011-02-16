<?php

//============================================================================
//
// net.php
// -------
//
// Networking audit base page.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = new ZoneMap(LIVE_DIR);
$s = new GetServersNet($map);
$grid = new NetGrid($map, $s->get_array());

$pg = new audPage("networking audit", $grid->server_count(),
$grid->zone_toggle());

echo $grid->show_grid(), $pg->close_page();

?>
