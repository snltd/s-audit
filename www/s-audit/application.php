<?php

//============================================================================
//
// application.php
// ---------------
//
// Show application software.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
// v1.1 Updated to use new AuditKey class. RDF 20/10/09
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");
require_once(ROOT . "/_lib/reader_file_classes.php");
require_once(ROOT . "/_lib/display_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = new ZoneFileMap(LIVE_DIR);
$s = new GetServersApp($map);
$grid = new SoftwareGrid($map, $s->get_array());

$pg = new audPage("application software audit", $grid->server_count(),
$grid->zone_toggle());

echo $grid->show_grid();

require_once(ROOT . "/_keys/software_key.php");

$pg->close_page();

?>
