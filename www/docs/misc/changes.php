<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "CHANGES";
$pg = new docPage($menu_entry);

?>

<h1>CHANGES List</h1>

<p>Following on from the <a href="history.php">history</a> page, everything
from v3.0, the first official release, onwards.</p>

<h2>Client</h2>

<dl>

	<dt>3.0</dt>
	<dd>First public release. All site-specific tests were removed, a new
	&quot;net&quot; audit class was created, and a lot more tests added. A
	complete rewrite of the main loop made audits of machines with a lot of
	zones ~40% faster.</dd>

</dl>

<h2>Interface</h2>

<dl>

	<dt>3.0</dt>
	<dd>First public release. Major rewrites, as the way it had grown had
	led to some very ineffecient and over-complicated ways of doing things.
	The new table rendering classes produce less than half the HTML the old
	ones did, with visually identical results.</dd>

</dl>

<h2>Documentation</h2>

<dl>

	<dt>3.0</dt>
	<dd>First public release. Up to now, s-audit had very little
	documentation, as no one but me used it. Everything written from
	scratch.</dd>

</dl>

<?php

$pg->close_page();

?>

