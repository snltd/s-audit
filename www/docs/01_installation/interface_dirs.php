<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "directories";
$pg = new docPage("directories used by s-audit");

?>

<h1>Directory Structure</h1>

<h1><tt>/var</tt> filesystem structure</tt></h1>

<p>By default the interface expects to find audit data in
<tt>/var/snltd/s-audit</tt>. This is defined in <tt>s-audit_config.php</tt>
as the <tt>AUDIT_DIR</tt>.</p>

<p>Inside the <tt>AUDIT_DIR</tt> you must have at least one group directory.
Each of these contain audit data for a group of servers. This allows you to
have separate views of, for instance, live and development environments.</p>

<p>Each group directory must contain a <tt>hosts/</tt> subdirectory, where
the <tt>s-audit.sh</tt>'s machine-parseable audit files are kept. It may
also contain a file called <tt>about.txt</tt>, which can hold a short
description of the environment the directory's contents describe. If
<tt>about.txt</tt> exists, its contents are displayed on the main interface
page.</p>

<p>All transient data is kept in <tt>/var</tt>. This includes all machine
audit data and extra files.</p>

<dl>

	<dt><tt>/var/snltd/s-audit</tt></dt>
	<dd>Top-level directory for s-audit. This can be changed by altering the
	<tt>AUDIT_DIR</tt> definition in <tt>s-audit_config.php</tt>.</dd>

	<dl>

		<dt><tt>default/</tt></dt>
		<dd>You can have as many groups of machines as you wish. Each
		group has its own data. The default group is called
		&quot;default&quot;.</dd>

		<dl>
			<dt><tt>hosts/</tt></dt>
			<dd>This directory contains audit files produced by
			<tt>s-audit.sh</tt>. Each machine has its own directory. If this
			directory is not suitably populated, the interface will not
			produce useful data.</dd>

			<dt><tt>extra/</tt></dt>
			<dd>Contains <a href="../extras/static_data.php">static data
			files</a>. This directory does not have to exist.</dd>

			<dt><tt>network/</tt></dt>
			<dd>If you are using any of the <a
			href="../extras/ip_list_file.php">IP list file</a>, <a
			href="../extras/ip_res_file.php">reserved IP list file</a>, or
			<a href="../extras/uri_map_file.php">URI map file</a>, put
			them in here. This directory does not have to exist.</dd>

		</dl>
	</dl>

</dl>

<h2>Interface</h2>

<p>The interface itself uses the following directory structure. Please don't
change it.</p>

<dl>

	<dt><tt>www/</tt></dt>
	<dd>The top-level directory for the interface.</dd>

		<dl>
		<dt><tt>_conf/</tt></dt>
		<dd>Configuration files are stored here. You will need to edit
		<tt>site_config.php</tt>.

		<dt><tt>_css/</tt></dt>
		<dd>Static cascading style sheets, and PHP code for dynamically
		generated ones.</dd>

		<dt><tt>_lib/</tt></dt>
		<dd>PHP class libraries.</dd>

		<dl>
			<dt><tt>keys/</tt></dt>
			<dd>Keys which help explain the colour-coding used in the audit
			grids. They are also presented as part of the
			documentation.</dd>

			<dt><tt>pkg_defs/</tt></dt>
			<dd>Files which help the interface describe Solaris
			packages.</dd>

			<dt><tt>pch_defs/</tt></dt>
			<dd>Files which help the interface describe Solaris
			patches.</dd>

		</dl>

		<dt><tt>docs/</tt></dt>
		<dl>
			<dd>Documenation.</dd>

			<dt><tt>interface/</tt></dt>
			<dd>Documentation for the PHP interface.</dd>

			<dt><tt>misc/</tt></dt>
			<dd>ChangeLog, licensing information and so-on.</dd>

			<dt><tt>_files/</tt></dt>
			<dd>Syntax coloured scripts and support files. These are
			presented as part of the s-audit documentation.</dd>

			<dt><tt>client/</tt></dt>
			<dd>Documentation for the <tt>s-audit.sh</tt> client.</dd>

			<dt><tt>extras/</tt></dt>
			<dd>Documentation for optional files which can be used to
			augment the information provided by the interface.</dd>
		</dl>

		<dt><tt>s-audit/</tt></dt>
		<dd>Base files which generate the audit grids. They require the
		class library files.</dd>

</dl>

<?php

$pg->close_page();

?>

