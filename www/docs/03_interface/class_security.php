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

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "security audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry);
$dh->doc_class_start();

?>

<dt>user</dt>

<dd>This column pairs users with UIDs. If <tt>OMIT_STANDARD_USERS</tt> is
defined in <tt>site_config.php</tt>, then users which exist in a default
Solaris installation will not be shown. If the constant is undefined, a
simple list of usernames will be shown.</dd>

<dd>If a username is found on multiple systems using multiple UIDs, it is
highlighted. Similarly, UIDs which are found assigned to more than one
username are shown.</dd>

<dt>empty password</dt>
<dd>If any empty passwords were found on the host, they are displayed here.
Normal users are on an amber field, root on red. Note that this only audits
the local <tt>/etc/shadow</tt> file, not external password maps such as NIS
or LDAP. If you have no empty passwords, this field is not displayed.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["empty password"]);
?>

<dd>As the interface produces this column, it keeps track of usernames and
UIDs it has seen before. If it comes across a username which it has
previously seen paired with a different UID, it highlights that username
with a solid red field. If it sees a UID that has already been paired with a
different username, then that username is highlighted with a red box. This
can help you find username/UID clashes across your system.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["user"]);
?>

<dt>authorized key</dt>
<dd>Lists users with authorized keys. The local username, that is the owner
of the <tt>authorized_keys</tt> which holds the key, is shown in
<strong>bold face</strong>, then the remote user and host are shown. If the
local user is root, a red field is used.</dd>

<dd>If you have no SSH key exchanges set up, this field will not be
displayed.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["authorized key"]);
?>

<dt>SSH root</dt>
<dd>This field tells you whether <tt>sshd_config</tt> permits root
logins. Please refer to the <a href="../client/class_security.php">client
security class page</a> for limitations on this information.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["SSH root"]);
?>

<dt>user_attr</dt>
<dd>This field displays non-standard entries from <tt>/etc/user_attr</tt>.
These describe RBAC profiles. Note that the entries can span multiple lines.
The first line is not indented, and the name of the user who has the
privilges is in <strong>bold face</strong>.</dd>

<dd>If only standard roles are found, the auditor will display
&quot;standard roles&quot;.</dd>


<dt>dtlogin</dt>
<dd>Displays any dtlogin/XDMP type programs. If they are running, they are
highlighted by an amber field, if not, by a red box.</dd>

<?php
	echo $dh->colour_key($dh->grid_key["dtlogin"]);
?>

<dt>cron job</dt>
<dd>Through not strictly security related, cron jobs are shown
on this page. If <tt>OMIT_STANDARD_CRON</tt> is
defined in <tt>site_config.php</tt>, then cron jobs which exist in a default
Solaris installation will not be shown.</dd>

<dd>Each job is shown by first displaying the user which the job runs as in
<strong>bold face</strong>. On the next line, headed &quot;time&quot; the standard five fields from a cron
entry which describe the time(s) at which the job is run are shown. Spacing
between each field has been increased to improve legibility. On the following
line(s) marked &quot;job&quot;, is the command. Note that the command is
likely to have been folded, with newlines escaped with backslashes.</dd>

<dd>If <tt>OMIT_STANDARD_CRON</tt> is
defined and only standard cron jobs are found, the auditor will display
&quot;standard jobs&quot;. Non-standard jobs will be highlighted by a green
box, and jobs which are on a standard system, but not on the audited one,
will be displayed and highlighted.</dd>

<?php
	if (!defined("OMIT_STANDARD_CRON"))
		define("OMIT_STANDARD_CRON", true);

	echo $dh->colour_key($dh->grid_key["cron job"]);
?>

<?php

$dh->doc_class_end();
$pg->close_page();

?>

