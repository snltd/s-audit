<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "patch/package audits";
$pg = new docPage($menu_entry);

?>

<h1>Patch and Package Audits</h1>

<p>Invoked by</p>

<pre class="cmd">
# s-audit.sh patch
</pre>

<p>this audit type generates lists of patches and packages in a zone, be it
global or local.</p>

<h2>Checks</h2>

<dl>
	<dt>patch_list</dt>
	<dd>On SYSV systems, produces a list of all installed patches.</dd>

	<dt>package_list</dt>
	<dd>Produces a list of all installed packages.</dd>
</dl>

<?php

$pg->close_page();

?>

