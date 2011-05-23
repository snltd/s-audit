<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

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

<p>The script is invoked in the following way:</p>

<pre>s-audit_dns_resolver.sh [-s dns_server] [-d dir] [-D path] [-o file]</pre>

<dl>

	<dt>-o</dt>
	<dd>path to output file. Defaults to
	<tt>/var/s-audit/dns/uri_list.txt</tt>.</dd>

	<dt>-D</dt>
	<dd>path to dig binary. The default is <tt>/usr/local/bin/dig</tt>.</dd>

	<dt>-d</dt>
	<dd>directory containing the audit files the script will parse. By
	default this is <tt>/var/s-audit/audit</tt>. The files may be in a
	subdirectory below the given directory.</dd>

	<dt>-s</dt>
	<dd>DNS server on which to do lookups.</dd>
</dl>



<p>Either modify the script to suit your environment or use the options
above. Everything which can be changed by an option is defined at the
beginning of the script.</p>

<p>Also be sure that the path of the output file is identical to the path
defined as <tt>URI_MAP_FILE</tt> in <tt>_conf/s-audit_config.php</tt>.  The
default values match.</p>

<h2>Automated Usage</h2>

<p>It is recommended to run <tt>s-audit_dns_resolver.sh</tt> as often as you
run <tt>s-audit.sh</tt>, but to wait until all <tt>s-audit.sh</tt>'s results
are in. Here is an example cron entry which processes audit files at 07:30
and 13:30. No special privileges are required to run the script, but the
user which runs it must have write access to <tt>/var/s-audit/dns</tt>.</p>

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
