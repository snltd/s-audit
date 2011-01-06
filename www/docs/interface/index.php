<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "web interface";
$pg = new docPage("the s-audit web interface");

?>

<h1>Web Interface Overview</h1>

<p>When <a href="../client"><tt>s-audit.sh</tt></a> is run with the
<tt>-p</tt> and <tt>-f</tt> options, it produces output of a form which is
parseable by a specially written web interface.</p>

<p>This interface produces a page for each class of audit, with each server
or zone having its own row.</p>

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

