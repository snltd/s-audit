<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "friends";
$pg = new docPage("extending the interface with the &quot;friends&quot;
file");
$dh = new docHelper();

?>

<h1>The &quot;Friends&quot; File</h1>

<p>The <a href="../03_interface/class_compare.php">server comparison
page</a> allows you to select hosts for comparison. You can also set up
&quot;quick links&quot; to servers you may wish to comapare frequently.
This functionality was created when I managed a lot of zones which were in
load-balanced pairs, and after upgrades or patching, I wanted to be sure
things were properly in-sync.</p>

<h2>File Format</h2>

<p>The friends file lists groups of machines, with an optional description.
Each group of friends goes on its own line, with hostnames separated by
commas. The host list is separated from the description by a colon
character.  Comments can be prefixed with a <tt>#</tt>, blank lines are
permitted..</p>

<h2>Example File</h2>

<p>
<p>The file is stored as <tt>/var/snltd/s-audit/default/friends.txt</tt>.</p>

<pre>
# infrastructure server pairs

cs-infra-01,cs-infra-02
cs-infra-01z-mail,cs-infra-02z-mail:mail servers

# web server pairs

cs-w-01,cs-w-02,cs-w-03:physical webserver hosts
cs-w-01z-live,cs-w-02z-live,cs-w-03-live:live webserver zones
cs-w-01z-uat,cs-w-02z-uat:test webserver zones

# Cluster nodes

finclusta,finclustb:'financials' Veritas Cluster


</pre>

<h2>Location</h2>

<p>The friends file must be named <tt>friends.txt</tt> and stored in the
top-level of a group directory.</p>

<?php

$pg->close_page();

?>

