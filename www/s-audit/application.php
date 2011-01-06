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

$pg = new Page("application software audit");

$map = new ZoneFileMap(LIVE_DIR);
$s = new GetServersApp($map, HostGrid::display_all_zones($map));
$grid = new SoftwareGrid($map, $s->get_array());
echo $grid->show_grid(), $grid->zone_toggle();

require_once(ROOT . "/_keys/software_key.php");

$pg->close_page();

?>
