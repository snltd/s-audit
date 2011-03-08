<?php

//============================================================================
//
// class_os.php
// ------------
//
// O/S audit page of s-audit web interface documentation. The main docPage()
// class is in display_classes.php.
//
// Note that the first part of the documentation for all class pages is
// printed by the docHelper::doc_class_start() function, and the end by
// docHelper::doc_class_end().
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

// Include the key file for this page to help us document the colour-coding.
// This help keep things consistent.

include(KEY_DIR . "/" . preg_replace("/class/", "key",
basename($_SERVER["PHP_SELF"])));
include(KEY_DIR . "/key_generic.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "O/S audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry, $generic_key);
$dh->doc_class_start();

?>

<dt>distribution</dt>
<dd>Displays the Solaris &quot;distribution&quot;. For Solaris 10 and older,
Sun branded releases, this will be &quot;Solaris&quot;. Solaris
Nevada/Express Community Edition is reported as &quot;SXCE&quot;. Other
distributions include BeleniX, OpenSolaris, OpenIndiana, Solaris 11 Express,
and Nexenta.</dd>

<dt>version</dt>
<dd>Displays the version of the operating system. For Solaris 10 and earlier
this is the marketing release number, for instance &quot;Solaris 2.6&quot;,
with the SunOS release number, e.g. 5.6, in parentheses. For the newer
OpenSolaris based distributions, only the SunOS version is displayed.</dd>

<dd>If a local zone has a different version to its global parent, it will be
in an amber box.</dd>

<?php
	echo $dh->colour_key(array(
		array("different version in local zone", "boxamber", false)));
?>

<dt>release</dt>
<dd>For Solaris 10 and earlier, this box displays the release date and
update number. For instance, a Solaris 10 update 8 installation will display
&quot;10/09 (update 8)&quot;. OpenSolaris based releases display the release
of the distribution. For instance, a Nexenta release may display as
&quot;3.0 (Hardy 8.04/b134+)&quot;, and an SXCE release as
&quot;snv_129&quot;. Some distributions do not provide a useful release
number, in which case no information will be displayed.</dd>

<dd>If the release in a local zone is different to that of its global
parent, it will be highlighted by an amber box.</dd>

<?php
	echo $dh->colour_key($grid_key["release"]);
?>

<dt>kernel</dt>
<dd>For SYSV packaged systems, i.e. Solaris 10 and earlier, this displays
the revision of the kernel patch installed on the system (for instance
144488-04). Most 5.11 based releases display the kernel number (e.g. 130),
but some distributions, for instance BeleniX, use their own numbering
system.</dd>

<dd>If you have a number of machines with the same Solaris version, that is
with identical values in the <strong>distribution</strong> and
<strong>version</strong> fields, and the same hardware architecture, then
the machines in that set with the most recent kernel version will have this
box highlighted in green. Older kernel versions will be red.  This helps you
see which, if any, machines may require patching.</dd>

<dd>Local zones which do not have the same kernel version as their global
parent are highlighted by an amber box. Note that branded zones display
their kernel version as &quot;Virtual&quot;</dd>

<?php
	echo $dh->colour_key($grid_key["kernel"]);
?>

<dt>hostid</dt>
<dd>Displays the hostid of the server or zone.</dd>

<?php
	echo $dh->colour_key($grid_key["hostid"]);
?>

<dt>SMF services</dt>
<dd>Shows the number of online SMF services in <strong>bold face</strong>,
followed by the total number of installed services and, if there are any,
the number of services in a mainetenence state. If services are in
maintenence, a red field is used.</dd>


<dt>local zone</dt>
<dd>In global zones, this cell gives a list of all local zones on the
system. The zone name is in <strong>bold face</strong>, with the zone root
directory in parentheses. If a zone is not &quot;running&quot; or
&quot;installed&quot;, its state is displayed between the zone name and the
zone root. Branded zones have their brand type displayed in square
brackets.</dd>

<dd>The following colour-coding is used.</dd>

<?php
	echo $dh->colour_key($grid_key["local zone"]);
?>

<dd>This field is blank for local zones. If you do not have any zoned
machines, this field will not be displayed.</dd>

<dt>LDOM</dt>
<dd>In a primary logical domain, this field displays a list of the guest
domains configured on the box. The domain name is in <strong>bold
face</strong> on the first line, followed by, in parentheses, the number of
VCPUS and the amount of physical RAM assigned to the damoin. For domians
other than the primary, the console port number is shown underneath in
square brackets. The domain's state is highlighted by the following
colour-coding.</dd>

<?php
	echo $dh->colour_key($grid_key["local zone"]);
?>

<dd>If you do not have any logical domains, this field will not be
displayed.</dd>

<dt>scheduler</dt>
<dd>Displays the process scheduler class. If none of your machines have had
the scheduler class changed, this column will not be displayed.</dd>

<dt>uptime</dt>
<dd>Displays the uptime of the machine at the point when the audit was
performed. Global zones which have rebooted in the last 24 hours are
highlighted, as are local zones which have been rebooted since the local
zone last booted.</dd>

<?php
	echo $dh->colour_key($grid_key["uptime"]);
?>

<dt>packages</dt>

<dd>Displays the number of packages in the zone, along with their type
in square brackets. SYSV systems which have partially installed packages
are highlighted, and the number of partial installs is displayed in
parentheses.</dd>

<?php
	echo $dh->colour_key($grid_key["packages"]);
?>

<dd>You can see exactly which packages are installed on the zone's
single server audit page.</dd>

<dt>patches</dt>
<dd>On systems with SYSV packaging, this field gives the number of
patches installed in the zone. If a sparse local zone has the same
amount of packages, but a different number of patches to
its global parent, it is highlighted on an amber field.</dd>

<dd>This field is blank on systems which use other packaging
methods.</dd>

<?php
	echo $dh->colour_key($grid_key["patches"]);

	$dh->doc_class_end();
	$pg->close_page();

?>

