<?php

//============================================================================
//
// manage.php
// ----------
//
// Manage the servers that the auditor knows about. Allows you to move
// servers and zones between "live" and "obsolete", and delete them
// completely.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");
require_once(ROOT . "/_lib/platform_classes.php");
require_once(ROOT . "/_lib/compare_classes.php");
require_once(ROOT . "/_lib/manage_server_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new Page("Hosting platform manager");

$mng = new ManageServers();

$fs_errs = $mng->check_manage_dirs(array(LIVE_DIR, OBSOLETE_DIR, TRASH_DIR));

// Check the directory structure looks okay

if ($fs_errs > 0)
	$pg->error("found $fs_errs problem(s) with directory structure.");

$context = (isset($_GET["c"])) ? $_GET["c"] : false;
$server = (isset($_GET["s"])) ? $_GET["s"] : false;
$src_dir = (isset($_GET["d"])) ? $_GET["d"] : false;

if ($context) {
	
//- move some servers --------------------------------------------------------

	switch($context) {

		case "obsolete":
			$target = OBSOLETE_DIR;
			$to_show = "ManageObsoleteList";
			break;

		case "reinstate":
			$target = LIVE_DIR;
			$to_show = "ManageLiveList";
			break;

		case "remove":
			$target = TRASH_DIR;
			$to_show = "ManageTrashList";
			break;
	}

	if (move_server($server, $src_dir, $target)) {

		echo "<p>Successfully performed &quot;${context}&quot; operation on
		$server.</p>\n\n<p>The two affected lists are displayed below. <a
		href=\"$_SERVER[PHP_SELF]\">To return to the main &quot;manage
		servers&quot; page, click here</a>.</p>";

		switch($src_dir) {
			case "live":
				$info_2 = new ManageLiveList();
				break;

			case "obsolete":
				$info_2 = new ManageObsoleteList();
				break;

			case "trash":
				$info_2 = new ManageTrashList();
				break;
		}

		$info_1 = new $to_show();

		echo $info_1->show_title(), $info_1->show_grid(),
		$info_2->show_title(), $info_2->show_grid();
	}
	else
		$pg->error("couldn't move ${server}.");

}
else {

//- print the lists of manageable servers ------------------------------------

	// Create list of objects of all the servers in the audit directory

	$live_dat = new ManageLiveList();
	$obs_dat = new ManageObsoleteList();
	$trash_dat = new ManageTrashList();

	echo $live_dat->show_title(), "<p>A live server can be moved the the
	<a href=\"obsolete.php\">obsolete servers page</a> by clicking the
	appropriate <strong>obsolete</strong> link in the table below. If the
	server has local zones, they will also be made obsolete. Note that you
	if you make a single zone obsolete whilst leaving its parent server
	live, you will see errors on the obsolete page about missing global
	zones.</p>\n\n<p>Servers and zones may be
	removed from the auditor through the <strong>remove</strong> links. Note
	that removing a server removes all its local zones, and also be aware
	that performing a remove operation here does not mean the server won't
	continue to be audited. Once a server is removed, it goes in the trash,
	from where it may be restored.</p>", $live_dat->show_grid();

	echo $obs_dat->show_title(), "<p>Servers may be removed from the
	<a href=\"obsolete.php\">obsolete servers page</a> with the
	<strong>remove</strong> links in the table below.</p>\n\n<p>Servers may
	also be moved into the live server pages with the
	<strong>reinstate</strong> links. This will make any software, hosted
	services or security information visible, and allow comparisons with
	other live servers.</p> \n\n<p>Moving or removing a global zone also
	moves or removes any local zones.</p>\n\n<p>You cannot move a server
	back into live if an audit for it is already there.</p>",
	$obs_dat->show_grid();

	echo $trash_dat->show_title(), "<p>Here you can restore a server to
	either live or obsolete status, so long as an audit for that server does
	not already exist.</p>", $trash_dat->show_grid();

}

$key = new auditKey();

echo $key->open_key(),
$key->key_global(),
$key->key_time(),
$key->close_key();
$pg->close_page();

?>
