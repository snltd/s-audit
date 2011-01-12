<?php

//============================================================================
//
// fs.php
// ------
//
// Show fs audit data.
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

//----------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = new ZoneFileMap(LIVE_DIR);
$s = new GetServersFS($map);
$grid = new FSGrid($map, $s->get_array());

$pg = new audPage("filesystem audit", $grid->server_count(),
$grid->zone_toggle());

//-- FILESYSTEM AUDITS -------------------------------------------------------

// Create list of objects of all the servers in the audit directory

echo $grid->show_grid();

$key = new auditKey();

echo $key->open_key(),
$key->key_global(),

$key->key_row("boxred", false, "in <strong>zpool</strong> column,
indicates a ZFS pool which can be upgraded"),

$key->key_row("solidamber", false, "in <strong>capacity</strong> column,
shows the machine has more than 85% of its available disk space in use"),

$key->key_row("boxred", false, "in the <strong>fs</strong> column, denotes
either an <strong>NFS</strong> filesystem which is not in the systems's
<tt>vfstab</tt>, or a <strong>ZFS</strong> filesystem which can be
upgraded"),

$key->key_row("boxred", false, "in the <strong>export</strong> column,
indicates an NFS filesystem which, so far as this interface knows, is not
mounted. Be aware that it may be mounted by an un-audited machine, or
mounted at a time other than when the auditor ran");

foreach(colours::$fs_cols as $name=>$hex) 
	echo $key->key_row("solid$name", false, "in the <strong>fs</strong> "
	. "and <strong>export</strong> fields, denotes '$name' filesystem");

echo $key->key_time(), $key->close_key(),

$key->key_extra_info("Notes on NFS", "In the NFS <strong>share</strong>
column, the <tt>" . STRIP_DOMAIN . "</tt>  domain name has been removed from
hostnames for legibility. Remember that hostnames in NFS share options (or
ZFS <tt>sharenfs</tt> settings) must be fully qualified."),

$pg->close_page();

?>
