<?php

//============================================================================
//
// os.php
// ------
//
// Show operating system audit data.
//
// R Fisher
//
// Please record changes below.
//
// v1.0  initial release
//
// v1.1  Use show_os_version from hardware class. RDF 19/12/09
//
// v1.2  Changed to work with new class layout. RDF 16/02/10
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");
require_once(ROOT . "/_lib/reader_file_classes.php");
require_once(ROOT . "/_lib/display_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new Page("O/S audit");

//-- PLATFORM AUDITS ---------------------------------------------------------

// Create list of objects of all the servers in the audit directory

$map = new ZoneFileMap(LIVE_DIR);
$s = new GetServersOS($map, HostGrid::display_all_zones($map));
$grid = new OSGrid($map, $s->get_array());
echo $grid->show_grid(), $grid->zone_toggle();

require_once(ROOT . "/_keys/os_key.php");

$pg->close_page();

?>
