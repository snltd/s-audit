<?php

//============================================================================
//
// s-audit/fs.php
// --------------
//
// Filesystem audit base page.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");

$map = ZoneMap::getInstance();

$s = new GetServers($map, false, "fs");
$grid = new FSGrid($map, $s->get_array(), "fs");

$pg = new audPage("filesystem audit", $grid->server_count(),
$grid->prt_toggle());

echo $grid->show_grid();

$pg->close_page();

?>
