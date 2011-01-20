<?php

//============================================================================
//
// os.php
// ------
//
// Operating system audit base page.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/reader_file_classes.php");
require_once(LIB . "/display_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = new ZoneFileMap(LIVE_DIR);
$s = new GetServersOS($map);
$grid = new OSGrid($map, $s->get_array());

$pg = new audPage("O/S audit", $grid->server_count(), $grid->zone_toggle());

echo $grid->show_grid(), $pg->close_page();

?>
