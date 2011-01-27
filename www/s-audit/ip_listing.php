<?php

//============================================================================
//
// ip_listing.php
// --------------
//
// This produces a list of used IP addresses from three sources. First, it
// uses the results of a scan run by the s-audit_subnet.sh script, and
// stored in the IP_LIST_FILE. Second, it pulls IP addresses out of the
// audit files, and finally, it can use an optional "reserved list", stored
// in the IP_RES_FILE.
//
// A used address is filled in according to the IP_LIST_FILE.  Addresses
// which are not in the IP_LIST_FILE, but are found in audit files are
// displayed in a way that indicates they may be used, but weren't found by
// the last scan.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/reader_classes.php");
require_once(LIB . "/display_classes.php");
require_once(LIB . "/ip_listing_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$list_doc = "<a href=\"" . DOC_URL ."/extras/ip_list_file.php\">network scan
file</a>";
$res_doc = "<a href=\"" . DOC_URL ."/extras/ip_res_file.php\">reserved IP list
file</a>";

$map = new ZoneMap(LIVE_DIR);
$s = new GetIPList($map);
$grid = new IPGrid($s);
$pg = new ipPage("IP address list");

// Put explanatory text in.

echo "\n<p class=\"center\">";

echo (file_exists(IP_LIST_FILE))
	? "This table incorporates data from a $list_doc generated on " .
	$s->scan_host . " at " .  date("H:i D d/m/Y", $s->timestamp["IP_LIST"])
	: "<p class=\"center\">You do not have a $list_doc";

echo ".</p>\n\n<p class=\"center\">";

echo (file_exists(IP_RES_FILE))
	? "This table incorporates data from a $res_doc last modified at ". 
	date("H:i D d/m/Y", $s->timestamp["IP_RES"])
	: "<p class=\"center\">You do not have a $res_doc";

echo ".</p>", $grid->show_grid("70%");

$pg->close_page();

?>
