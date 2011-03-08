<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "s-audit_subnet.sh";
$pg = new docPage("s-audit_subnet.sh");

?>

<h1>The <tt>s-audit_subnet.sh</tt> script</h1>

<p><a href="../interface/ip_listing.php">The interface's IP address listing
page</a>  is put together by looking at audited machines, but it is unlikely
that all the machines on your network will be Solaris hosts. At the very
least there are probably routers and firewalls. For that reason, the
interface is able to incorporate other information into the IP listing, and
this script generates that information.</p>

<p><tt>s-audit_subnet.sh</tt> performs a rudimentary network sweep, pinging
all addresses (1-254) on any given subnet(s). It also queries a DNS server
(named in the script, or through the <tt>-s</tt> option), to get names and
addresses of everything in DNS on the same subnet(s). Then, it combines the
information from both those sources into a file with the following
format:</p>

<pre>
pinged_address  dns_hostname  dns_ip_address
</pre>

<p>So, if the first field exists, but the others don't, we have a live
address that's not in DNS. If the first field is blank but two and three
aren't, field 2 is a DNS name which resolves to field 3, but doesn't
respond to a ping. Both these are potential problems, so the IP listing page
will highlight them.</p>

<p>If you wish, you can create the file using a proper network scanner, like
NMAP. <tt>s-audit_subnet.sh</tt> was written to work on a network which NMAP
took an unreasonably long time to scan, and also to be very portable.
Probably this script will not be useful to most people, and it could
certainly be improved and made more general, but it does work.</p>

<p>s-audit does not require the file generated by
<tt>s-audit_subnet.sh</tt>, so you don't have to use it if you don't want
to, or are unable to.</p>

<h2>Usage</h2>

<pre class="cmd">
$ s-audit_subnet.sh [-R user@host:/path] [-s dns_server] [-D path] 
                    [-o file] subnet...
</pre>

<dl>
	<dt>-o</dt>
	<dd>write to a file, using this path. Without this option, the script
	writes to standard out.</dd>

	<dt>-D</dt>
	<dd>path to dig binary. Default is <tt>/usr/local/bin/dig</tt>.</dd>

	<dt>-R</dt>
	<dd>information for <tt>scp</tt> to copy audit files to a remote host.
	Of form <tt>user@host:directory</tt>. Key exchange may be required,
	please consult your SSH documentation.</dd>

	<dt>-s</dt>
	<dd>DNS server on which to do lookups.</dd>
</dl>

<p>You can use as many subnets as you wish. They should be of the form
10.10.8.0, in which case 10.10.8.1 to 10.10.8.255 would be scanned.</p>

<p>The script defines a DNS server in the <tt>DNS_SRV</tt> variable, though
this value can be overriden with the <tt>-s</tt> option. Set it to the
hostname of your internal DNS server, i.e. the one that describes the
subnet(s) given as the script's arguments.</p>

<h2>Requirements</h2>

<p>The DNS server must accept batch requests. I believe this limits you to
BIND 9. Hosts which do not respond to IMCP pings wil be omitted. The script
also requires access to a dig(1) executable. The path to it is defined in
by the <tt>DIG</tt> variable.</p>

<h2>Source</h2>

<?php

$scr = new codeBlock("s-audit_subnet.sh");
echo $scr->show_script();

$pg->close_page();
?>
