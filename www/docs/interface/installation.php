<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "installation";
$pg = new docPage($menu_entry);

?>
<h1>Requirements</h1>

<p>The interface requires <a href="http://php.net">PHP</a> verson 5.1 or
greater. Following the design methodology behind the s-audit client, the
interface is written simply, and requires no non-core PHP functionality.</p>

<p>Theoretically the s-audit interface can run on any web server with a
suitable installation of PHP, but it is only officially tested with <a
href="http://httpd.apache.org">Apache</a> 2.2.</p>


<?php

$pg->close_page();

?>

