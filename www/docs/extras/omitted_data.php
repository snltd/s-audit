<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "OMITTED_DATA_FILE";
$pg = new docPage($menu_entry);
$dh = new docHelper();
require_once(OMITTED_DATA_FILE);
$omit = new omitData();
?>

<h1>OMITTED_DATA_FILE</h1>

<p>This file holds a PHP class which itself contains public variables. These
variables tell the interface about information which is considered
&quot;unimportant&quot;.</p>

<p>For instance, certain users, such as root and lp, exist on every Solaris
system. So, there is little point in reporting them in an audit - the user
knows they are there. By omitting them we help keep the view clean and
legible, whilst hopefully not losing any important information.</p>

<h2>Omitted data</h2>

<p>The following data is currently omitted on this system.</p>

<h3>Users</h3>

<p>On the <a href="class_security.php">security audit page</a> the following
usernames are not displayed.</p>

<p>The UID is on the left, the username in parentheses on the right.</p>

<?php
    echo $dh->list_omitted("omit_users");
?>

<h3>user_attrs</h3>

<p>On the <a href="class_security.php">security audit page</a> the following
RBAC roles  are not displayed.</p>

<?php
    echo $dh->list_omitted("omit_attrs", "omitlisttt");
?>

<h3>Cron Jubs</h3>

<p>On the <a href="class_security.php">security audit page</a> the following
cron jobs are not displayed.</p>

<?php
    echo $dh->list_omitted("omit_crons", "omitlisttt");
?>

<h3>Expected Ports</h3>

<p>On the <a href="class_net.php">networking audit page</a> the following
open ports may be displayed, but will not be highlighted.</p>

<h2>Location</h2>

<?php

$dh->file_on_sys("OMITTED_DATA_FILE", "OMITTED_DATA_FILE");
$pg->close_page();
?>


