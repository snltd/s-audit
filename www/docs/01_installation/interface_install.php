<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

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

<h1><tt>/var</tt> filesystem structure</tt></h1>

<p>By default the interface expects to find audit data in
<tt>/var/s-audit</tt>. This is defined in <tt>s-audit_config.php</tt> as the
<tt>BASE_DIR</tt>.</p>

<p>Inside the <tt>BASE_DIR</tt> you must have at least one group directory.
Each of these contain audit data for a group of servers. This allows you to
have separate views of, for instance, live and development environments.</p>

<p>Each group directory must contain a <tt>hosts/</tt> subdirectory, where
the <tt>s-audit.sh</tt>'s machine-parseable audit files are kept. It may
also contain a file called <tt>about.txt</tt>, which can hold a short
description of the environment the directory's contents describe. If
<tt>about.txt</tt> exists, its contents are displayed on the main interface
page.</p>

<p>
</pre>

<?php

$pg->close_page();

?>

