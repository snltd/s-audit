<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "s-audit_dns_resolver.sh";
$pg = new docPage("The s-audit_dns_resolver.sh script");

?>

<h1>The <tt>s-audit_dns_resolver.sh</tt> script</h1>

<p>The s-audit web interface's hosted services view has the ability to
colour website names green or red depending on whether or not they resolve
on a particular DNS server. This functionality was added to root out
obsolete websites. If you wish to use it, you must use
<tt>s-audit_dns_resolver.sh</tt>.</p>

<h2>Usage</h2>

<p>Modify the script to suit your environment. You will have to change the
<tt>DNS_SRV</tt> variable to point the the name of your external-facing DNS
server, and you may have to change <tt>DIG</tt>, which defines the path to
the dig binary.</p>

<h2>Automated Usage</h2>

<p>It is recommended to run <tt>s-audit_dns_resolver.sh</tt> as often as you
run <tt>s-audit.sh</tt>, but to wait until all <tt>s-audit.sh</tt>'s results
are in. Here is an example cron entry which processes audit files at 07:30
and 13:30. No special privileges are required to
run the script, but the user which runs it must have write access to
<tt>/var/s-audit/dns</tt>.</p>

<pre>
30 7,13 * * * /usr/local/bin/s-audit_dns_resolver.sh
</pre>

<p>The script does not write anything to standard out.</p>

<h2>Requirements</h2>

<p>You need a working copy of dig, and the DNS server it accesses must
support batch queries. This limits you to BIND 9.4 or later. The user the
script runs as must have write access to <tt>/var/s-audit/dns</tt>.</p>

<h2>Source</h2>

<?php

$scr = new codeBlock("s-audit_dns_resolver.sh");
echo $scr->show_script();

$pg->close_page();

?>
