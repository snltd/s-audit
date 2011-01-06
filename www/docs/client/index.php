<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "s-audit client";
$pg = new docPage("the s-audit.sh client");

?>

<p>The client part of <tt>s-audit.sh</tt> is a Korn shell script which
gathers information on a wide range of system features and configurations.
It is intended to show what hardware the system is running on, how the O/S
is installed and patched, what software is available, what services are
running, and so-on.</p>

<p>There are different types, or <em>classes</em> of audit, with each
class being made up of a number of <em>tests</em>.  A list of the available
audit classes, along with full information on how to invoke the client, can
be found on the <a href="usage.php">usage</a> page.</p>

<p>All audits, of every class, start with the &quot;hostname&quot; test, and
end with &quot;time&quot;</p>

<dl>

	<dt>hostname</dt>
	<dd>Reports the hostname of the zone or machine in which s-audit is
	running. No qualifying is done, it's simply the output of the
	<tt>uname -n</tt> command.</dd>

	<dt>time</dt>
	<dd>Prints the local time at which the audit type completed.</dd>

</dl>

<?php

$pg->close_page();

?>

