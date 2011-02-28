<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "miscellany";
$pg = new docPage($menu_entry);

?>

<p>Here we group together documents which don't fit in any of the other
sections. Nothing in here is vital to using s-audit.</p>

<?php

$pg->close_page();

?>

