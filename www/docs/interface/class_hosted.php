<?php

//============================================================================
//
// class_hosted.php
// ----------------
//
// Hosted services audit page of s-audit web interface documentation. The
// main docPage() class is in display_classes.php.
//
// Note that the first part of the documentation for all class pages is
// printed by the docHelper::doc_class_start() function, and the end by
// docHelper::doc_class_end().
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//   see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

// Include the key file for this page to help us document the colour-coding.
// This help keep things consistent.

include(KEY_DIR . "/" . preg_replace("/class/", "key",
basename($_SERVER["PHP_SELF"])));
include(KEY_DIR . "/key_generic.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "hosted services audits";
$pg = new docPage($menu_entry);
$dh = new docHelper($menu_entry, $generic_key);
$dh->doc_class_start();

?>

<dt>website</dt>
<dd>Lists sites hosted on various web servers. Currently Apache (all
versions) and Sun Webserver (version 7 only) are supported.</dd>

<dd>

	<dl>
	<dt>Apache</dt>
	<dd>Each zone has its Apache websites broken down by configuration file.
	So, if you bundle all your virtual hosts into a single config, the
	output may not be entirely clear.</dd>

	<dd>First the server names and aliases are listed in
	<strong>bold</strong> face. The first line also gives the server type in
	parentheses (apache).</dd>
                   
	<dd>If you have used <a
	href="../extras/s-audit_dns_resolver.php"><tt>s-audit_dns_resolver.sh</tt></a>
	to create a <a
	href="../extras/uri_map_file.php"><tt>URI_MAP_FILE</tt></a>, then the
	server names will be in either <span class="strongr">red</span> or <span
	class="strongg">green</span> text.  Green text is used for sites whose
	names were resolved by <tt>s-audit_dns_resolver.sh</tt>, red for those
	which were not. If you choose not have to have a <tt>URI_MAP_FILE</tt>
	this text will always be black.</dd>

	<dd>The first indented line gives the document root of the vhost. If
	this is an NFS mount, it is on a red field. If the document root itself
	is on a  local filesystem, but there are NFS mounts underneath it, the
	field will be amber. The NFS mounting is worked out by examining the
	same information that produces the &quot;exports&quot; field on the <a
	href="class_fs.php">filesystem audit page</a>.</dd>

	<dd>The second indented line gives the path to the vhost's configuration
	file. If the name of that file does not end with <tt>.conf</tt>, it is
	shown on an amber field. This can be useful for finding redundant
	vhosts.</dd>

	<dt>Sun Webserver/iPlanet</dt>
	<dd>The name of the server is given in <strong>bold face</strong> with
	the server type (iplanet) after in parentheses. The server name may be
	coloured using the same scheme as the Apache vhost names described
	above.</dd>

	<dd>The first indented line gives the document root, again using the
	same highlighting as for Apache.</dd>

	<dd>The final indented line gives the server instance name.</dd>

	</dl>
	</dd>

	<dd>To summarise, the following colour coding is used:</dd>

<?php
	echo $dh->colour_key($grid_key["website"]);
?>


<dt>database</dt>
<dd>This field lists databases. This means the databases themselves, not the
server software which is hosting them. Currently only MySQL databases are
supported.</dd>

<dd>The first row of the entry gives the name of the database in
<strong>bold</strong> face, followed by the server type in parentheses, then
the size of the database on disk. The second, indented row tells you when
the database was last updated. If that update was more than a month ago, the
database is shown on an amber field.</dd>

<dd>The following colour coding is used.</dd>

<?php
	echo $dh->colour_key($grid_key["database"]);

$dh->doc_class_end();

$pg->close_page();


?>

