<?php

//============================================================================
//
// fs.php
// ------
//
// Filesystem audit base page.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");

//----------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = new ZoneMap(LIVE_DIR);
$s = new GetServersFS($map);
$grid = new FSGrid($map, $s->get_array());

$pg = new audPage("filesystem audit", $grid->server_count(),
$grid->zone_toggle());

echo $grid->show_grid(), $pg->close_page();

?>
