<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "extra files";
$pg = new docPage("extra files");

?>

<h1>s-audit Support Files</h1>

<p>Though the <tt>s-audit.sh</tt> tool works with no dependencies, a number
of additional tools and helpers have grown up around it. Chief among these
is the PHP interface which presents audit results, but the following tools
also exist to help you get more information from s-audit.</p>

<dl>
	<dt><a href="smf.php">an SMF manifest</a></dt>
	<dd>This provides a ready-made way to integrate s-audit with Solaris's
	SMF framework. It is useful to have machines audit themselves on a
	reboot, or to perform on-demand audits simply by refreshing the
	service.</dd>

	<dt><a href="s-audit_pchdefs.php"><tt>s-audit_pchdefs.sh</tt></a></dt>
	<dd>A script which gathers information about Solaris patches, allowing
	the PHP interface to tell you what each installed patch is for. Requires
	an Oracle support contract.</dd>

	<dt><a href="s-audit_pkgdefs.php"><tt>s-audit_pkgdefs.sh</tt></a></dt>
	<dd>A shell script which queries Solaris install images to produce a
	text file which helps PHP interface give more detailed information on
	installed packages.</dd>

	<dt><a
	href="s-audit_dns_resolver.php"><tt>s-audit_dns_resolver.sh</tt></a></dt>
	<dd>A shell script which compares websites hosted on your web servers to
	the records in your external-facing DNS server, helping you track down
	obsolete sites. Requires BIND 9.4+.</dd>

	<dt><a href="s-audit_subnet.php"><tt>s-audit_subnet.sh</tt></a></dt>
	<dd>A script which augments the information presented in the interface's
	IP listing page by scanning your internal network and querying your
	internal-facing DNS server. This helps you track down obsolete DNS
	records, or servers with incomplete DNS data. Requires BIND 9.4+.</dd>

	<dt><a
	href="s-audit_subnet_wrapper.php"><tt>s-audit_subnet_wrapper.sh</tt></a></dt>
	<dd>A simple wrapper scipt useful if you need to run <a
	href="s-audit_subnet.php"><tt>s-audit_subnet.sh</tt></a> on a machine
	other than the one running s-audit's PHP interface.</dd>

</dl>

<?php

$pg->close_page();
?>
