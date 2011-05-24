<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "web interface";
$pg = new docPage("the s-audit web interface");

?>

<h1>Web Interface Overview</h1>

<p>When <a href="../02_client"><tt>s-audit.sh</tt></a> is run with the
<tt>-p</tt> and <tt>-f</tt> options, it produces output of a form which is
parseable by a specially written web interface.</p>

<p>This interface produces a page for each class of audit, with each server
or zone having its own row. Those pages are:</p>

<ul>
	<li><a href="class_platform.php">platform audits</a> - the virtual or
	physical environment</li>

	<li><a href="class_os.php">O/S audits</a> - the configuration of
	Solaris</li>

	<li><a href="class_net.php">networking audits</a> - network
	configuration</li>

	<li><a href="class_fs.php">filesystem audits</a> - mounted and exported
	filesystems, and disk usage</li>

	<li><a href="class_application.php">software application audits</a> -
	versions and states of various software applications</li>

	<li><a href="class_tools.php">software tool audits</a> - versions of
	various software tools</li>

	<li><a href="class_hosted.php">hosted services audits</a> - databases
	and websites</li>

	<li><a href="class_platform.php">security audits</a> - issues in some
	way related to host security</li>
</ul>

<p>The interface tabulates the data in an easy-to-read form, and colours
information which it believes may be useful. This may be to draw the user's
attention to obsolete software versions, missing patches, or unused
databases.</p>

<p>Each environment has its own row, the background colour being determined
by the type of environment. &quot;Global&quot; hosts, be they physical
servers, logical domains or VirtualBoxes are sorted alphabetically, with
their child local zones underneath them. By default the interface presents
20 global zones per page, though this value can be changed by editing the
<tt>PER_PAGE</tt> definition in <tt>_conf/s-audit_config.php</tt>.</p>

<p>If you have more hosts than can be shown on a single page, you will see
&quot;previous&quot; and &quot;next&quot; links at the top of the page. The
top segment of the page also has a link which allows you to toggle the
display of local zones.</p>

<p>The interface also provides <a href="ip_listing.php">an IP listing
page</a>, which helps to display the IP address allocation on your
network.</p>

<p>When audit files are read, the interface verifies that they look complete
and correct. If it finds errors, warnings are displayed at the top of the
screen and the broken data is ignored.</p>

<?php

$pg->close_page();

?>

