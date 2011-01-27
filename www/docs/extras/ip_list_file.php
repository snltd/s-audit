<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "IP_LIST_FILE";
$pg = new docPage("The IP_LIST_FILE");

?>

<h1>The IP_LIST_FILE</h1>
<p>This flat text is created by the <a
href="s-audit_subnet.php"><tt>s-audit.subnet.sh</tt></a> script, and is used
to enhance the <a href="../interface/ip_listing.txt">IP listing page</a>. It
contains information combining a network ping sweep with a DNS query on the
local domain and is used to help track down DNS discrepencies and pingable,
hosts which are not audited by s-audit.</p>

<p>The auditor must know the path to this file. It is defined as
<tt>IP_LIST_FILE</tt> in <tt>_conf/s-audit_config.php</tt>. The default path
is <tt>/var/s-audit/ip_list/ip_list.txt</tt>.</p>

<p>The file does not have to be present for s-audit to function
correctly.</p>

<p>It is not recommended that you generate this file by hand.</p>

<h2>File Format</h2>
<p>The first line is of the form</p>

<pre>
@@ hostname datestamp
</pre>

<p>Where <tt>hostname</tt> is the hostname of the machine which produced the
file, and <tt>datestamp</tt> is the output of the Unix command</p>

<pre class="cmd">
$ date "+%H:%M %d/%m/%Y"
</pre>

<p>Following that are three fields</p>

<pre>
IP_address_1 hostname IP_address_2
</pre>

<p><tt>IP_address_1</tt> is a valid IP address, <tt>hostname</tt> is a
hostname found by performing a lookup on <tt>IP_address_1</tt>, and
<tt>IP_address_2</tt> is an IP address found by doing a reverse lookup on
<tt>hostname</tt>. If any fields are not required, they should contain a
<tt>-</tt> character. Fields are whitespace separated.</p>

<h2>Example File</h2>

<pre>
@@ admin-01 12:45 26/01/2011
10.10.4.1 css-host1.localnet 10.10.4.1
10.10.4.10 ws-1.localnet 10.10.4.10
10.10.4.104 - -
10.10.4.106 build-01.localnet 10.10.4.106
- xhost.localnet 10.10.4.130
10.10.4.255 - -
</pre>

<?php
$pg->close_page();
?>


