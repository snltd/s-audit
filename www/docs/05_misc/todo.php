<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "TODO";
$pg = new docPage($menu_entry);

?>

<h1>TODO List</h1>

<p>Things which will, all being well, be added to s-audit.</p>

<h2>Client</h2>

<dl>

	<dt>Cluster support</dt>
	<dd>Fully audit Sun and Veritas cluster systems. Possibly in a dedicated
	&quot;cluster&quot; audit class</dd>

	<dt>Solaris 2.5.1 support</dt>
	<dd>As soon as I can get my hands on a Solaris 2.5.1 media kit.</dd>

	<dt>Better LDOM recognition</dt>
	<dd>Currently only differentiates between &quot;primary&quot; and
	&quot;guest&quot;. No concept of control domains.</dd>

	<dt>NGinx support</dt>
	<dd>To the same level as Apache is currently supported.</dd>

	<dt>LDAP and NIS+</dt>
	<dd>Both client and server.</dd>

	<dt>

</dl>

<h2>Interface</h2>

<dl>

	<dt>Database backend</dt>
	<dd>Store audit data in a database rather than as flat files. This will
	make the interface faster. MySQL would be first, then possibly SQLite,
	Postgres or Oracle.</dd>

	<dt>Searches and filtering</dt>
	<dd>Selective viewing of servers.</dd>

	<dt>Output to CSV</dt>
	<dd>Convert audit views for use with external spreadsheets and
	databases.</dd>

</dl>

<?php

$pg->close_page();

?>

