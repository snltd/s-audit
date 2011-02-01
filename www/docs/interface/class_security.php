<?php

//============================================================================
//
// class_security.php
// ------------------
//
// Security audit page of s-audit web interface documentation. The main
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

// Include the key file for this page to help us document the colour-coding.
// This help keep things consistent.

include(KEY_DIR . "/" . preg_replace("/class/", "key",
basename($_SERVER["PHP_SELF"])));
include(KEY_DIR . "/key_generic.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "security audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry, $generic_key);
$dh->doc_class_start();

?>

<dt>user</dt>
<dd>This column pairs users with UIDs. Standard Solaris users, which exist
by default on all systems, are not shown. The list of standard users is
kept in the <a
href="../extras/omitted_data.php"><tt>omitted_data.php</tt></a> file.</dd>

<dd>As the interface produces this column, it keeps track of usernames and
UIDs it has seen before. If it comes across a username which it has
previously seen paired with a different UID, it highlights that username
with a solid red field. If it sees a UID that has already been paired with a
different username, then that username is highlighted with a red box. This
can help you find username/UID clashes across your system.</dd>

<dt>authorized key</dt>
<dd>Lists users with authorized keys. The 

<dt>SSH root</dt>
<dd>This field tells you whether <tt>sshd_config</tt> permits root
logins. Please refer to the <a href="../client/class_security.php">client
security class page</a> for limitations on this information.</dd>

<?php

$dh->doc_class_end();
$pg->close_page();

?>

