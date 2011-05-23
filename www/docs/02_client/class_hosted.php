<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "hosted services audits";
$pg = new docPage($menu_entry);

?>


<h1>Hosted Services Audits</h1>

<p>Invoked by</p>

<pre class="cmd">
# s-audit.sh hosted
</pre>

<p>this audit type looks for web sites and databases. Currently only Apache
and MySQL are fully supported, but this will change in the future.</p>

<h2>Checks</h2>

<dl>
	<dt>site_apache</dt>
	<dd>From left to right, reports the web server (in this case "apache"),
	the website name, the configuration file for that site, and the site's
	document root.</dd>

	<dt>site_iplanet</dt>
	<dd>From left to right, reports the web server type (iplanet), followed
	by the site name, the server instance name, and the site's document
	root. This only works fully with version 7.  Support for older versions
	may be added in the future.</dd>

	<dt>db_mysql</dt>
	<dd>Lists MySQL databases, along with the size they occupy on disk and,
	if the database has not been updated in the last 30 days, the time of
	last update.</dd>

</dl>

<p>All tests are run in global and local zones.</p>

<?php

$pg->close_page();

?>

