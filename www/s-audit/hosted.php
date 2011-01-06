<?php

//============================================================================
//
// hosted.php
// ----------
//
// Show hosted services, like web sites and databases.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
// v1.1  Changed to work with new class layout. RDF 16/02/10
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");
require_once(ROOT . "/_lib/reader_file_classes.php");
require_once(ROOT . "/_lib/display_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new Page("hosted services");

//-- PLATFORM AUDITS ---------------------------------------------------------

// Create list of objects of all the servers in the audit directory

$map = new ZoneFileMap(LIVE_DIR);
$s = new GetServersHosted($map, HostGrid::display_all_zones($map));
$grid = new HostedGrid($map, $s->get_array());
echo $grid->show_grid(), $grid->zone_toggle();

$key = new auditKey();

echo $key->open_key(),

$key->key_row("boxamber", false, "in the <strong>database</strong> field,
means that the database has not been updated in a long time. The time of the
last update is displayed");

foreach(colours::$db_cols as $name=>$hex)
    echo $key->key_row("small$name", false, "in the <strong>database</strong> "
	. "shows the site is hosted by a '$name' webserver");

echo $key->key_row("boxgreen", false, "in the <strong>website</strong> column
shows sites whose name has been resolved externally"),
$key->key_row("boxred", false, "in the <strong>website</strong> column
shows sites whose name could not be resolved externally. If no boxes are
outlined, DNS resolution was not attempted"),
$key->key_row("solidamber", false, "amber highlighting on the
<strong>doc_root</strong> row of the the <strong>website</strong> column
means that some part of that website's content is NFS mounted"),
$key->key_row("solidred", false, "red highlighting on the
<strong>doc_root</strong> row of the the <strong>website</strong> column
means that the document root is on an NFS mounted directory"),
$key->key_row("solidamber", false, "an amber <strong>config</strong> field
in the 
<strong>website</strong> highlights config files not called
<tt>*.conf</tt>");

foreach(colours::$ws_cols as $name=>$hex)
    echo $key->key_row("small$name", false, "in the <strong>website</strong> "
	. "shows the site is hosted by a '$name' webserver");

echo $key->key_time(),
$key->close_key();

$pg->close_page();

?>
