<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "s-audit_group.sh";
$pg = new docPage("s-audit_group.sh");

?>

<h1>The <tt>s-audit_group.sh</tt> script</h1>

<p>This script creates, removes, and lists audit groups. It does little
more than create and remove directories in <tt>/var</tt>.</p>


<h2>Usage</h2>

<pre class="cmd">
$ s-audit_group.sh create [-d description] [-u user] [-g group] [-R dir] 
group_name
</pre>

<pre class="cmd">
$ s-audit_group.sh remove group_name
</pre>

<pre class="cmd">
$ s-audit_group.sh list
</pre>

<p>When using the <tt>create</tt> command, the following options apply.</p>

<dl>
	<dt>-d description</dt>
	<dd>Lets you add a brief description of the audit group. Must be
	quoted.</dd>

	<dt>-u user</dt>
	<dd>If you are running the script as root, <tt>chown</tt> the group
	directory and its contents to the given user.</dd>

	<dt>-g group</dt>
	<dd>If you are running the script as root, <tt>chgrp</tt> the group
	directory and its contents to the given group.</dd>

	<dt>-R directory</dt>
	<dd>By default, group directories are created under
	<tt>/var/snltd/s-audit</tt>. This option lets you change that base
	directory.</dd>

</dl>

<h2>Source</h2>

<?php

$scr = new codeBlock("s-audit_group.sh");
echo $scr->show_script();

$pg->close_page();
?>
