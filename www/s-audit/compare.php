<?php

//============================================================================
// 
// compare.php
// -----------
//
// Compare servers so you can see differences and commonalities.
//
// R Fisher
//
// Please record changes below.
//
// v1.0  Initial release.
//
// v1.1  Fixed to work with new hardware/os audits rather than platform. RDF
//       09/12/09
//
//============================================================================

// Get the classes we need. A couple of the compare classes extend a
// Hardware class, so we need the hardware classess too.

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");
require_once(LIB . "/reader_file_classes.php");
require_once(LIB . "/display_classes.php");
require_once(LIB . "/compare_classes.php");

// If we have a package definition file, load its definitions

if (file_exists(LIB . "/pkg_defs.php"))
	include(LIB . "/pkg_defs.php");

//-----------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new Page("Server comparison tool");

$context = (isset($_GET["c"])) ? $_GET["c"] : false;
$z1 = (isset($_GET["z1"])) ? $_GET["z1"] : false;
$z2 = (isset($_GET["z2"])) ? $_GET["z2"] : false;

if (isset($_POST["c"])) 
	$context = $_POST["c"];

if (isset($_POST["z1"]))
	$z1 = $_POST["z1"];

if (isset($_POST["z2"]))
	$z2 = $_POST["z2"];

// Create a zone file map. We need that whatever we're doing

$map = new ZoneFileMap(LIVE_DIR);

// We also want the list of paired servers every time

$friends = $map->get_pairs();
$pair_list = new CompareList($map, $friends);

switch($context) {

//-- COMPARE TWO ZONES -------------------------------------------------------

	case "compare":

		// Do a few sanity checks

		if (!(isset($z1) && isset($z2)))
			$pg->error("need two zones");

		if (!(is_string($z1) && is_string($z2)))
			$pg->error("undefined zones");

		if (!$map->has_data($z1))
			$pg->error("no audit data for $z1");

		if (!$map->has_data($z2))
			$pg->error("no audit data for $z2");

		if ($z1 == $z2)
			$pg->error("You can't compare a zone with itself");

		$s_arr = array($z1, $z2);

		$s = new GetServersCompare($map, $s_arr);
		$servers = $s->get_array();

		$comparison = new CompareGrid($map, $servers);
		$key = new auditKey();

		echo $comparison->show_grid("40%", true),

		$key->open_key(),
		$key->key_row("solidgreen", false, "newest version/greater number"),
		$key->key_row("solidred", false,"oldest version/lower number"),
		$key->key_row("boxgreen", false, "identical values"),
		$key->key_time(),
		$key->close_key();

		$key->key_extra_info("Notes", "Information shown on the <a
		href=\"security.php\">security audit page</a> is not included in the
		above comparison.<br>The most recent revisions of each patch are
		clickable links. They take you the Sunsolve page for that patch.
		Note that some of those pages require you to log in to Sunsolve
		before they will be shown.<br> Any information shown regarding
		databases or websites is limited.  Refer to the <a
		href=\"hosted.php\">hosted services</a> page for more details.");

		break;

	default:

		
//-- DEFAULT CASE ------------------------------------------------------------

?>

<p>This page lets you perform direct comparisons of zones or servers. Select
a pair of twinned zones from the list below, or use the gadget at the bottom
of the page to select any two zones to compare.</p>

<?php
		// Peel the values off the top of the friends array as defaults for
		// the cycle gadgets

		$z1 = current(array_keys($friends));
		$z2 = current(array_values($friends));
}

$key = new auditKey();

echo $pair_list->show_grid(),

$key->open_key(),
$key->key_global(),
$key->close_key(),

CompareGrid::compare_bar($map->list_all(), $z1, $z2);

$pg->close_page();

?>
