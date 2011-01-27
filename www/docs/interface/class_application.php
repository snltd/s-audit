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

// Include the key file for this page to help us document the colour-coding.
// This help keep things consistent.

include(KEY_DIR . "/" . preg_replace("/class/", "key",
basename($_SERVER["PHP_SELF"])));
include(KEY_DIR . "/key_generic.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "application audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry, $generic_key);
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
will say &quot;unkown&quot; and use a dark red field.</dd>

<dd>The applications presented on this page typically run as daemons. If
<tt>s-audit.sh</tt> thought that a piece of software should be running, but
found it wasn't, the interface will highlight this by putting a red box
around that installation, and appending &quot;not running&quot; to the
version string. Of course, you may not <em>want</em> some software to be
running, for instance an X server, or an MTA.</dd>

<?php

$dh->doc_class_end();
$pg->close_page();

?>

