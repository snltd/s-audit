<?php

//============================================================================
//
// class_tools.php
// ---------------
//
// Software tool audit page of s-audit web interface documentation. The main
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

$menu_entry = "tool audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry);
$dh->doc_class_start();

?>

<?php

$dh->doc_class_end();
$pg->close_page();

?>

