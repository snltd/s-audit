<?php
//============================================================================
//
// software_key.php
// ----------------
//
// A common key for the application and tool audits.
//
// R Fisher 10/2009
//
// v1.0 initial release
//
//============================================================================

$key = new auditKey();
echo $key->open_key(),
$key->key_global(),
$key->key_row("sw_latest", false, "The most recent installed version of
this piece of software."),
$key->key_row("sw_old", false, "Older installed versions of
software"),
$key->key_row("solidred", false, "Software whose version could not be
obtained"),
$key->key_row("boxred", false, "Software which should be running (as a
daemon), but was not"),
$key->key_time(),
$key->close_key();

?>
