<?php

//============================================================================
//
// platform_key.php
// ----------------
//
// A common key for the platform audit grid.
//
// R Fisher 10/2009
//
// v1.0 initial release
//
//============================================================================

$key = new auditKey();

echo $key->open_key(),
$key->key_global(),
$key->key_row("boxblue", false, "in the <strong>virtualization</strong>
column, indicates a global zone"),
$key->key_row("boxred", false, "in the <strong>virtualization</strong>
column, indicates whole-root zones"),
$key->key_row("solidamber", false, "in the <strong>virtualization</strong>
column, indicates non-native zones"),
$key->key_row("solidorange", false, "indicates <strong>ALOM IP</strong>
address reported by the server it is on"),
$key->key_row("boxorange", false, "indicates <strong>ALOM IP</strong>
address &quot;guessed&quot; by querying DNS"),
$key->key_row(false, inline_col::solid($grid->get_nic_col("vlan")), "in
<strong>NIC</strong> column, indicates an interface on an unknown network,
or VLAN"),
$key->key_row(false, inline_col::solid($grid->get_nic_col("vswitch")), "in
<strong>NIC</strong> column, indicates a virtual switch"),
$key->key_time(),
$key->close_key(),

$key->key_extra_info("Note on ALOMs", "The absence of ALOM information
does not necessarily mean that server has no ALOM configuration. It is not
possible to query the LOMs on T200 platform machines from Solaris. The
firmware version currently has to remain unknown, but the interface tries to
guess missing ALOM IP addresses.  A &quot;guessed&quot; IP address in the
ALOM IP field is denoted by an orange border, and is acquired by doing a DNS
lookup on hostname-lom. It may not be correct."),

$key->key_extra_info("Note on NIC cables",
"Solid colours in the NIC  and ALOM columns identify the colour of the
cable that is (or should be) plugged into the corresponding port. A grey
background means the interface was not able to work out what colour the
cable should be. (It's probably brown.)");

?>
