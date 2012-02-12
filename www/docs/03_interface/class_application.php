<?php

//============================================================================
//
// class_application.php
// ---------------------
//
// Application software audit page of s-audit web interface documentation.
// The main docPage() class is in display_classes.php.
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

$menu_entry = "application audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry);
$dh->doc_class_start();
?>

<dt>All Fields</dt>
<dd>Every piece of software recognized up by <tt>s-audit.sh</tt> is given
its own column. Only the applications s-audit found will be shown, so if,
for instance, none of your audited machines have Exim installed, you will
not see an Exim column on the application audit page. If you install Exim
somewhere, and audit that machine again, the Exim column will automatically
appear.</dd>

<dd>For each installed application, the version number is displayed. If
multiple versions of the same application were found on one host, each
version is displayed. Hovering your mouse pointer over the version number
will produce a tooltip showing you the path to the binary which was used to
get the version number.</dd>

<dd>s-audit looks at all versions of each application that you have
installed, and puts the most recent on a green field. Note that that does
not mean the green coloured SSH is the most up-to-date available, it just
means it is the most recent you have on all your audited systems. Older
versions are put on a pale red field. This helps you find out-of-date
software, and helps you synchronize versions across machines. If
<tt>s-audit.sh</tt> was not able to find the version of an application, it
will say &quot;unkown&quot; and use a dark orange field.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["general"]);
?>

<dd>The applications presented on this page typically run as daemons. If
<tt>s-audit.sh</tt> thought that a piece of software should be running, but
found it wasn't, the interface will highlight this by putting a red box
around that installation, and appending &quot;not running&quot; to the
version string. Of course, you may not <em>want</em> some software to be
running, for instance an X server, or an MTA.</dd>

<dd>As a general rule, the more &quot;deeply integrated&quot; an application
is to Solaris, the nearer the left of the page it will appear.</dd>

<dt>Exim and Sendmail</dt>
<dd>If these programs are installed, but not running as daemons, their
fields will be bordered with red. However, in many cases, not running them
as a daemon will be the correct behaviour.</dd>

<dt>sshd</dt>
<dd>s-audit currently supports Sun SSH and OpenSSH. If you have both
installed, the most recent version of <em>each</em> is highlighted.</dd>

<dt>NB client</dd>
<dd>If the Netbackup client is being run via <tt>inetd</tt>, that fact is
noted.</dd>

<dt>Apache</dt>
<dd>This page simply shows the versions of any Apache installations. To see
what sites those Apaches are running, please examine the <a
href="class_hosted.php">hosted services page</a>.</dd>

<dt>apache so</dt>
<dd>Lists Apache modules loaded into a running server. Please refer to the
<a href="../02_client/class_application.php">client page</a> for more
information.</dd>

<dt>mod_php</dt>
<dd>If multiple versions of Apache are installed, each installed PHP module
will show to which Apache it belongs.</dd>

<dt>iPlanet web</dt>
<dd>Displays the version of the admin server, and the number of HTTP
servers.</dd>

<dt>Samba</dt>
<dd>This page simply shows the versions of any Samba installations. The
filesystems which Samba is exporting are shown on the 
<a href="class_fs.php">filesystem page</a>.</dd>

<dt>X-Server</dt>
<dd>Because XSun, which was used on older versions of Solaris, doesn't
report a version, XSun fields are not coloured.</dd>

<?php

$dh->doc_class_end();
$pg->close_page();

?>

