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
by default on all systems, are not shown. The list of standard users is kept
in <a href="../extras/omitted_data.php"><tt>OMITTED_DATA_FILE</tt></a>.</dd>

<dd>On this system the omitted users are:</dd>

<?php
    echo $dh->list_omitted("omit_users");
?>

<dd>As the interface produces this column, it keeps track of usernames and
UIDs it has seen before. If it comes across a username which it has
previously seen paired with a different UID, it highlights that username
with a solid red field. If it sees a UID that has already been paired with a
different username, then that username is highlighted with a red box. This
can help you find username/UID clashes across your system.</dd>

<?php
	echo $dh->colour_key($grid_key["user"]);
?>

<dt>authorized key</dt>
<dd>Lists users with authorized keys. The local username, that is the owner
of the <tt>authorized_keys</tt> which holds the key, is shown in
<strong>bold face</strong>, then the remote user and host are shown. If the
local user is root, an amber field is used.</dd>

<dd>If you have no SSH key exchanges set up, this field will not be
displayed.</dd>

<?php
	echo $dh->colour_key($grid_key["authorized key"]);
?>

<dt>SSH root</dt>
<dd>This field tells you whether <tt>sshd_config</tt> permits root
logins. Please refer to the <a href="../client/class_security.php">client
security class page</a> for limitations on this information.</dd>

<?php
	echo $dh->colour_key($grid_key["SSH root"]);
?>

<dt>user_attr</dt>
<dd>This field displays non-standard entries from <tt>/etc/user_attr</tt>.
These describe RBAC profiles. Note that the entries can span multiple lines.
The first line is not indented, and the name of the user who has the
privilges is in <strong>bold face</strong>.</dd>

<dd>On this system the omitted roles are:</dd>

<?php
    echo $dh->list_omitted("omit_attrs", "omitlisttt");
?>

<dt>dtlogin</dt>
<dd>Displays any dtlogin/XDMP type programs. If they are running, they are
highlighted by an amber field, if not, by a red box.</dd>

<?php
	echo $dh->colour_key($grid_key["dtlogin"]);
?>

<dt>cron job</dt>
<dd>Through not strictly security related, non-standard cron jobs are listed
on this page. On the first line the user who runs the job is displayed in
<strong>bold face</strong>, followed by the standard five fields from a cron
entry which describe the time(s) at which the job is run. On the following
line(s), indented, is the command.</dd>

<dd>The following jobs are considered standard, and are not displayed.</dd>

<?php
    echo $dh->list_omitted("omit_crons", "omitlisttt");
?>

<?php

$dh->doc_class_end();
$pg->close_page();

?>

