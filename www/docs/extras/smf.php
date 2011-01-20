<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "SMF integration";
$pg = new docPage("Running s-audit.sh from SMF");

?>

<h1>Running s-audit.sh through SMF</h1>

<p>The SMF manifest at the foot of this page can be used on any Solaris
system with SMF.  To enable it, save it to <tt>s-audit.xml</tt>, then import
it with</p>

<pre class="cmd">
# svccfg import s-audit.xml
</pre>

<h2>Properties</h2>

<p>The following properties are available.</p>

<dl>
	<dt>options/delay </dt>
	<dd>Type <tt>astring</tt>. Is the argument to the <tt>-D</tt> option.
	i.e. it sets the number of seconds <tt>s-audit.sh</tt> will wait to do
	an audit after the service is first enabled. Default value is 180</dd>
	
	<dt>options/dest</dt>
	<dd>Type <tt>astring</tt>. Is used to tell <tt>s-audit.sh</tt> where to
	put audit data. To have the data copied with SCP, supply a full
	<tt>-R</tt> option, for instance

	<pre>"-R audit_user@audit_server:/var/s-audit/live"</pre>

	To copy the data to a directory, use the form

	<pre>"-f /var/s-audit/live"</pre></dd>

	<dt>options/pth</dt>
	<dd>Type <tt>astring</tt>. The path to the <tt>s-audit.sh</tt>
	executable.  Normally <tt>/usr/local/bin/s-audit.sh</tt>.</dd>
</dl>

<p>To change those properties, issue a command of the form:</p>

<pre class="cmd">
# svccfg -s s-audit setprop options/dest="\"-f /var/s-audit/live\""
</pre>

<h2>Performing an audit at boot time</h2>

<p>On system boot <tt>s-audit.sh</tt> will wait for the time defined in the
<tt>options/delay</tt> property. This gives the server time to start all its
zones and services so an accurate audit can be performed.</p>

<h2>Performing an audit at other times</h2>

<p>To perform an audit on-demand, refresh the service.</p>

<pre class="cmd">
# svcadm refresh s-audit
</pre>

<p>If you wish to perform regular scheduled audits, put the <tt>svcadm</tt>
command into root's crontab.</p>

<h2>The manifest</h2>

<?php

$manifest = new codeBlock("s-audit.xml");
echo $manifest->show_script();

$pg->close_page();
?>


