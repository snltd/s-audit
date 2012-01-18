<?php

//============================================================================
//
// class_compare.php
// ------------------
//
// Comparison page of s-audit web interface documentation. The main
// docPage() class is in display_classes.php.
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

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "Host Comparison";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry);

?>

<p>This page lets you compare servers. It was designed to highlight
differences in machines which were supposed to be built and configured as
identical pairs. Currently you can only compare two hosts at a time.

<h2>Landing Page</h2>

<p>The default view for this page presents two cycle gadgets which let the
user select two hosts for comparison. An error occurs if you try to compare
a host with itself. It is possible to compare physical hosts with local
zones.</p>

<p>If you have defined a <a href="../04_extras/friends.php">friends file</a>
for the current server group, then a clickable lists of friends is shown at
the top of the page.</p>

<h2>Comparison Page</h2>

<p>Each audit class is presented in its own table, with each check on its own
row, rather than in a column as in all other views.</p>

<p>The leftmost column shows the name of the check. A red field in this
columns shows that the check produced different results on the two hosts. In
that case, the values of the check are shown in the respective host columns.
A green check field shows that the check produced the same data on both
hosts, and that value will be shown spread across both host columns.</p>

<p>Where the author has considered that a check's value could be considered
&quot;better&quot; on one host than another, for instance a newer kernel
patch or version of Apache, the &quot;better&quot; value will be highlighted
by a red field, the &quot;worse&quot; by red.</p>

<p>Some checks, such as hostid or routing information, will always produce
different results on different hosts, and other checks such as patch or
package counts do not produce values which are quantifiably &quot;better or
worse&quot;, so these checks are not coloured in.</p>

<p>The audit completed field adds an extra line showing the time difference
between the two audits. If the audits were performed within one hour of each
other, the time difference is shown on a green field. Less than twenty-four
hours is denoted by an amber field, and audits further spaced than that are
on a red field. The audit times are displayed on the next row.</p>

<h2>Notes</h2>

<h3>Platform Audit Comparison</h3>

<p>Systems with more CPUs are considered &quot;better&quot; than those with
fewer, regardless of clock speed or number of cores.</p>

<h3>O/S Audit Comparison</h3>

<p>If the Solaris distribution is the same for both hosts, the version,
release and kernel fields will be coloured, the newer version being
considered &quot;best&quot;. If the distribution differs, these fields are
not coloured as comparing, for instance, a Solaris 10 kernel patch number to
a Belenix release number is meaningless.</p>

<h3>Net Audit Comparison</h3>

<p>If <tt>OMIT_PORT_THRESHOLD</tt> is defined in <tt>site_config.conf</tt>
then ports above that number will be screened out prior to comparison, and
not displayed in the <strong>ports</strong> field.</p>

<p>NICs only display the device name, IP address, speed, and
IPMP/aggregate/VNIC/DHCP fields.  Other information, such as MAC address and
zone, was not considered relevant in a comparison.</p>

<h3>Filesystem Audit Comparison</h3>
<p>Scrub information is removed from the zpool field.</p>

<p>Capacity is not currently compared, as it's hard to decide whether a
larger capacity or a larger amount of free space should be considered
&quot;better&quot;. However, capacity is shown in <strong>bold
face</strong>.</p>

<p>VxVM disk groups only display the name of the group and the number of
disks the group contains.</p>

<p>Unmounted ZFS filesystems are not displayed.</p>

<h3>Application Audit Comparison</h3>

<p>Paths to audited applications are shown in [square brackets] after the
version number.</p>

<p>If multiple versions of the same software are found, the highest version
number is highlighted by a green field, others by red.  For an installed
application to be considered identical across servers, its version and path
must be the same.</p>

<p>If both OpenSSH and SunSSH are found, no version colouring is done in the
&quot;sshd&quot; field.</p>

<p>If XSun is found, no colouring is done in the &quot;X Server&quot;
field.</p>

<h3>Security Audit Comparison</h3>

<p>Standard users, cron jobs and user_attrs are not currently removed from
the data used on the comparison page. This may change.</p>

<h3>Patch and Package Comparison</h3>

<p>All patches and packages are displayed.</p>

<p>If you have a great many package (over 1000), certain older web browsers
may render this table incorrectly. The comparisons are still valid and
correct, but the columns can be skewed.</p>

<?php
$pg->close_page();

?>
