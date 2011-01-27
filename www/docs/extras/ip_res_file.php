<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "IP_RES_FILE";
$pg = new docPage("The IP_RES_FILE");
$dh = new docHelper();

?>

<h1>The IP_RES_FILE</h1>
<p>This flat text file lets you add addresses to the <a
href="../interface/ip_listing.php">IP listing page</a>. It can be used for
things s-audit cannot audit, for instance routers or non-Solaris machines,
or for addresses reserver for DHCP pools, or transient machines like
laptops.</p>

<p>The auditor must know the path to the file. It is defined as
<tt>IP_RES_FILE</tt> in <tt>_conf/s-audit_config.php</tt>. The default path
is <tt>/var/s-audit/ip_list/ip_list_reserved.txt</tt>.</p>

<p>The file does not have to be present for s-audit to function
correctly.</p>

<h2>File Format</h2>
<p>One IP address/hostname pair per line, whitespace separated. Comments may
be prefixed with a <tt>#</tt>.</p>

<h2>Example File</h2>

<pre>
# This is a list of reserved IP addresses. Put in anything that's up and
# down but that you want to always show up in the auditor's IP listing page.
# Format is:
#   address hostname
# whitespace separated. No spaces in "hostname".

10.10.8.7   rob-laptop
10.10.4.123 reserved
10.10.6.123 reserved
10.10.7.123 reserved
10.10.8.123 reserved
</pre>

<h2>Location</h2>

<?php
$dh->file_on_sys("URI map file", "URI_MAP_FILE");
$pg->close_page();
?>


