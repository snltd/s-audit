<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "IP address map";
$pg = new docPage("IP address map");

?>

<h1>IP Address Map</h1>

<p>This page shows IP address maps for all subnets known to the auditor. It
is generated from three sources:</p>

<ol>
	<li>if it exists, the  <a
	href="../extras/ip_list_file.php"><tt>IP_LIST_FILE</tt></a>, created by
	<a
	href="../extras/s-audit_subnet_wrapper.php"><tt>s-audit_subnet_wrapper.sh</tt></a></li>
	
	<li>the machine audit files</li>

	<li>an optional <a
	href="../extras/ip_res_file.php"><tt>IP_RES_FILE</tt></a> file created
	by a user</li>
</ol>

<p>The following colour-coding is used. Note that cells may have coloured
fields AND coloured borders.</p>

<dl>

	<dt class="resolved">resolved addresses</dt>
	<dd>These addresses were pulled from the <tt>IP_LIST_FILE</tt>. Assuming
	that file is recent, they are the most authoritative records. At the
	time of the last <tt>s-audit_subnet.sh</tt> audit, these
	addresses were found to be live (i.e. pingable) and have valid DNS
	entries.</dd>

	<dd>You will only see addresses on this field if you have a populated <a
	href="../extras/ip_list_file.php"><tt>IP_LIST_FILE</tt></a>.</dd>

	<dt class="onlylive">audited addresses</dt>
	<dd>Addresses not resolved by an <tt>s-audit_subnet.sh</tt>  network
	scan, but which the live audit files say are used. May or may not be
	live, check the border colour. If you do not have an
	<tt>IP_LIST_FILE</tt>, these addresses should be considered
	authoritative.</dd>

	<dt class="onlyping">pingable addresses</dt>
	<dd>Addresses found to be live by the last  <tt>s-audit_subnet.sh</tt>
	scan, but which have no reverse DNS record, and which are not known to
	the auditor. They should probably be added to DNS.</dd>

	<dd>You will only see addresses on this field if you have a
	populated <a
	href="../extras/ip_list_file.php"><tt>IP_LIST_FILE</tt></a>.</dd>

	<dt class="reserved">reserved addresses</dt>
	<dd>Addresses taken from a hand-made and -maintained file, stored at
	<tt><?php echo IP_RES_FILE; ?></tt>, and used to list
	&quot;reserved&quot; IP addresses which, though they are unlikely to be
	found in a network scan, should not be used for anything. They may be
	used for a DHCP pool, a laptop, or a server which is often switched
	off.</dd>

	<dd>You will only see addresses on this field if you have a populated <a
	href="../extras/ip_res_file.php"><tt>IP_RES_FILE</tt></a>.</dd>

</dl>

<p>Additionally, if you have an <tt>IP_LIST_FILE</tt>, addresses will also
have border colours showing whether or not they were found on the last scan.</p>

<ul class="dockey">
	<li><div class="boxgreen">pingable on last subnet scan</div></li>
    <li><div class="boxred">not pingable on last subnet scan</div></li>
</ul>

<?php

$pg->close_page();

?>

