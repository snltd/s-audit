<?php

//============================================================================
//
// os_key.php
// ----------
//
// Key for O/S audits
//
// R Fisher 10/2009
//
// v1.0 initial release
//
//============================================================================

$key = new auditKey();

echo $key->open_key(),
$key->key_global(),
$key->key_row("solidgreen", false, "in <strong>local zone</strong>
column, indicates a &quot;running&quot; zone"),
$key->key_row("solidamber", false, "in <strong>local zone</strong>
column, indicates an &quot;installed&quot; zone"),
$key->key_row("solidred", false, "in <strong>local zone</strong> column,
indicates a &quot;configured&quot; zone"),
$key->key_row("solidgreen", false, "in <strong>LDOM</strong> column,
indicates an &quot;active&quot; domain"),
$key->key_row("solidamber", false, "in <strong>LDOM</strong> column,
indicates a &quot;bound&quot; domain"),
$key->key_row("solidred", false, "in <strong>LDOM</strong> column, indicates
a domain in a state given in brackets"),
$key->key_row("solidamber", false, "in <strong>packages</strong> column,
partially installed packages"),
$key->key_row("solidamber", false, "in <strong>patches</strong> column,
indicates a local zone with fewer packages than its parent global zone.
Local zones which have more patches (because they are whole root and have
more packages) are not coloured"),
$key->close_key();

?>
