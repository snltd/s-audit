<?php

//============================================================================
//
// obsolete.php
// ------------
//
// Display information on obsolete servers. Akin to the "platform audit"
// page.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_obsolete/obsolete_config.php");
require_once(ROOT . "/_obsolete/obsolete_classes.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new Page("Hosting platform audit");

//-- PLATFORM AUDITS ---------------------------------------------------------

// Create list of objects of all the servers in the audit directory

$map = new ZoneFileMap(OBSOLETE_DIR, true);
$s = new GetServersPlatform($map, HostGrid::show_zones($map));
$grid = new PlatformGrid($map, $s->get_array());
echo $grid->show_grid(), $grid->zone_toggle();

?>

<p>This page concentrates on servers and zones at the platform level.</p>

<p>Amber warnings in the packages column are for partially installed
packages.</p>

<p>Amber warnings int the patches column notify that a local zone has fewer
patches than its parent global zone. Local zones which have more patches
(because they are whole root and have more packages) are not coloured.</p>

<p>Solid colours in the NIC  and ALOM columns identify the colour of the
cable that is (or should be) plugged into the corresponding port.</p>

<p>The absence of ALOM information does not necessarily mean that server has
no ALOM configuration. It is not possible to query the LOMs on T200 platform
machines from Solaris. The firmware version currently has to remain unknown,
but the interface tries to guess missing ALOM IP addresses. A "guessed" IP
address in the ALOM IP field is denoted by an orange border, and is acquired
by doing a DNS lookup on hostname-lom. It may not be correct.</p>

<p>Red or amber &quot;audit completed&quot; boxes warn that the machine was
not audited today.</p>

<?php

$pg->close_page();

?>
