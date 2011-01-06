<?php

//============================================================================
//
// netbackup.php
// -------------
//
// Compare servers so you can see differences and commonalities.
//
// PHP script to analyze filtered bpdbjobs output and produce HTML reports.
// Requires PHP-5.2 or above. No non-core PHP functionality is needed. If
// PHP's safe mode is set, allowances must be made for the script to run the
// 'awk' and 'sort' binaries.
//
// R Fisher 08/06
// Please record any changes below
// 02/08/06 Initial release RDF
//
// 05/06/08 v1.1 NWP
// 	    Fixed for NBU6 (pid of composite jobs parent now same as id, not 0.
//	    Also added handling of a 12th field so other job types such catalog
//	    backups and image clean ups display nicely. 
//
//  v2.0 Moved to new auditor OO framework. Most code still intact but much
//       rearranged. RDF 19/09/08
//
//=============================================================================

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");
require_once(ROOT . "/_lib/reader_file_classes.php");
require_once(ROOT . "/_lib/display_classes.php");
require_once(ROOT . "/_lib/netbackup_classes.php");

$pg = new Page("Netbackup Monitor");

// Pull the context ($c) out of the $_GET variable.

$context = (isset($_GET["c"])) ? $_GET["c"] : false;

if ($context == "history") {

//- SINGLE SERVER HISTORY ----------------------------------------------------

	// In this case we want to show the enitre known backup history of a
	// host. Run off and get it.

	define("DATE_STR", "H:i:s d/m/y");
	$heading = "NetBackup history for $_GET[host]";
	$datafile = false;
	$d = new GetNbDataHistory($_GET["host"]);
	$r = new nbReportGrid($d->get_array());
	$r->display_report($heading);
}
else {

//- STANDARD REPORT ----------------------------------------------------------

	define("DATE_STR", "H:i:s");
	
	// We do backups at 20:00 onwards so the default day probably ought to
	// be yesterday

	$datafile = (isset($_GET["datafile"])) 
		? NB_DIR . "/$_GET[datafile]" 
		: NB_DIR . "/bpdbjobs-" .  date("Ymd", mktime(1,0,0) - 86400) . ".csv";

	if ($context == "breakdown") {
		$pid = $_GET["pid"];
		$heading = "NetBackup breakdown for job $pid";
	}
	else {
		$pid = 0;
		$heading = "NetBackup reports for " .
		date("l jS F Y", nbCalendarGrid::filename_to_mktime($datafile));
	}

	// Maybe there's no suitable datafile. This would indicate a problem
	// with the retrieval script. Better make sure.

	if (file_exists($datafile) && filesize($datafile) > 0)  {
		$d = new GetNbData($datafile, $pid);
		$r = new nbReportGrid($d->get_array());
		$r->display_report($heading);
	}
	else {
		print ("<p>No data is available.</p><p> [ expected
		$datafile ]</p><p>Please check that
		<code>/usr/local/bin/bpdb_get_records.sh</code> is being run
		regularly on cs-backup-01 (it should be in the <code>root</code>
		user's crontab). Files are created in <code>/var/tmp/bpdbout</code>
		on cs-backup-01, then copied to 
		<code>/var/becta/nb_data</code> on this server.</p>");
	}
	
}

$cal = new nbCalendarGrid($datafile);
echo  "<h3>History</h3>", $cal->show_grid();
	
$pg->close_page();
	
?>
