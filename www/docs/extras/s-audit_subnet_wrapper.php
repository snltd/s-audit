<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "s-audit_subnet_wrapper.sh";
$pg = new docPage("s-audit_subnet_wrapper.sh");

?>

<h1>The <tt>s-audit_subnet_wrapper.sh</tt> script</h1>

<p>This script is a simple wrapper to <a
href="s-audit_subnet.php"><tt>s-audit_subnet.sh</tt></a>. It was written to
automate subnet auditing through cron and copy the information it gathered
onto a remote server. If you have subnets with different levels of
privilege, then you may have to do the same. It may or may not be useful in
your environment.</p>

<h2>Usage</h2>

<pre class="cmd">
$ s-audit_subnet_wrapper.sh user@host:/path/to/file
</pre>

<p>The argument is an SSH connection string defining the remote user to
connect as, and the full pathname to which you wish to copy the output of
<tt>s-audit_subnet.sh</tt>. You will most likely have to perform SSH key
exchange for this to work.</p>

<p>The script does not need any special privileges.</p>

<h2>Example cron entry</h2>

<p>To audit subnets at 06:45 and 12:45 every day, and copy the audit file to
<tt>/var/s-audit/ip_list.txt</tt> on the server <tt>saud-host</tt>,
connecting as the <tt>audit</tt> user.</p>

<pre>
45 6,12 * * * /usr/local/bin/s-audit_subnet_wrapper.sh
audit@saud-host:/var/s-audit/p_list.txt
</pre>


<h2>Source</h2>

<?php

$scr = new codeBlock("s-audit_subnet_wrapper.sh");
echo $scr->show_script();

$pg->close_page();
?>
