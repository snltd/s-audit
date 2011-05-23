<?php

//============================================================================
//
// s-audit/application.php
// -----------------------
//
// Application software audit base page.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");

$map = new ZoneMap();
$s = new GetServers($map, false, "app");
$grid = new SoftwareGrid($map, $s->get_array(), "app");

$pg = new audPage("application software audit", $grid->server_count(),
$grid->zone_toggle());

echo $grid->show_grid(), $pg->close_page();

?>
