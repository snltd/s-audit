<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "credits";
$pg = new docPage($menu_entry);

?>

<p>The <tt>s-audit.sh</tt> client its web interface are written entirely by
Robert Fisher. No code has been taken from any other source.</p>

<p>Thanks to Ian and Nick for suggestions, bug-spotting and support.</p>

<?php

$pg->close_page();

?>

