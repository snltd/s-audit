<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "URI map file";
$pg = new docPage("The URI Map File");
$dh = new docHelper();

?>

<h1>The URI Map File</h1>
<p>This flat text file is created by the <a
href="s-audit_dns_resolver.php"><tt>s-audit_dns_resolver.sh</tt></a> script,
and is used by the <a href="../interface/class_hosted.php">hosted
services</a> page. It is not essential for the correct operation of s-audit,
but can add extra information if you desire.</p>

<p><tt>s-audit_dns_resolver.sh</tt> tries to do DNS lookups on all the site
names found in files created by <tt>s-audit.sh</tt>, pairing each resolved
hostname with its IP address.</p>

<p>The interface uses this file to draw the user's attention to any sites
they are hosting which do not have external DNS records.</p>

<h2>File Format</h2>
<p>One entry per line, all entries of the form:</p>

<pre>
hostname=ip_addr
</pre>

<h2>Example File</h2>

<pre>
www.example.com=93.83.51.104
dev.example.com=93.83.51.106
lists.example.com=93.83.51.152
</pre>

<h2>Location</h2>
<p>The URI map file is named <tt>uri_list.txt</tt> and should be stored in
the <tt>network/</tt> subdirectory of the relevant audit group's
directory.</p>

<p>For instance, if you have an audit group called &quot;live&quot;, then
the file would normally be saved as</p>

<pre>
/var/snltd/s-audit/live/network/uri_list.txt
</pre>


<?php

$dh->file_on_sys("URI map file", "URI_MAP_FILE");
$pg->close_page();
?>


