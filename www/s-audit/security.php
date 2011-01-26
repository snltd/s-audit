<?php

//============================================================================
//
// security.php
// ------------
//
// Show "security audit" data.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
// v1.1  Changed to work with new class layout. RDF 16/02/10
//
//============================================================================

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");
require_once(LIB . "/display_classes.php");
require_once(ROOT . "/_conf/omitted_data.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$map = new ZoneMap(LIVE_DIR);
$s = new GetServersSecurity($map);
$grid = new SecurityGrid($map, $s->get_array());

$pg = new audPage("Security audit", $grid->server_count(),
$grid->zone_toggle());

//-- SECURITY AUDITS ---------------------------------------------------------

// Create list of objects of the servers we're interested in

echo $grid->show_grid();

$key = new auditKey();

echo $key->open_key(),
$key->key_global(),

$key->key_row("solidred", false, "in in the <strong>users</strong> column,
solid highlighted usernames use UIDs which have been used by a different
username on another machine. The colliding UID is box highlighted"),

$key->key_row("boxred", false, "in the <strong>users</strong> column, box
highlighted usernames belong to users which have different UIDs on different
machines."),

$key->key_row("solidamber", false, "in the <strong>authorized key</strong>
column, denotes a root key"),

$key->key_row("solidamber", false, "in the <strong>ports</strong> column,
denotes ports which we don't necessarily expect to be open"),

$key->key_row("boxred", false, "in in the <strong>ports</strong> column,
shows services running via <tt>inetd</tt>. In this column <strong>bold
face</strong> denotes the <tt>/etc/services</tt> name of the service holding
the open port. The text in brackets is the name of the process which owns
the port"),

$key->key_time(), $key->close_key(),

$key->key_extra_info("Notes on authorized keys", "The <strong>authorized
keys</strong> column pairs local users (on the left) with remote users who
have the ability to run commands as that user without being challenged for a
password. Remote root accesses are highlighted in red."),

$key->key_extra_info("Notes on open ports", "The <strong>port</strong>
column shows open ports on servers/zones. Where possible, the audit script
has identified the processes using those ports.  Values in (round brackets)
were worked out by <tt>pfiles</tt> examining running processes, and are
sometimes truncated. Values in [square brackets] come from querying
<tt>/etc/services</tt>."),

$pg->close_page();

?>
