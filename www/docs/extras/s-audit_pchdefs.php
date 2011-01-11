<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "s-audit_pchdefs.sh";
$pg = new docPage("The s-audit_pchdefs.sh script");

?>

<h1>The <tt>s-audit_pchdefs.sh</tt> script</h1>

<p>This script is used to generate information which enhances the output of
the PHP interface. On the single-server view or comparison pages, the
interface lists all SYSV patches installed on a box. If the user hovers his
cursor over a patch number, a box will pop up giving a brief description of
that patch's function. For this to work, a suitable &quot;hover map&quot;
must exist in <tt>_lib/pch_defs</tt>. <tt>s-audit_pchdefs.sh</tt> creates
those hover maps from a <tt>patchdiag.xref</tt> file.</p>

<h2>Requirements</h2>

<p>If you wish to have <tt>s-audit_pchdefs.sh</tt> download
<tt>patchdiag.xref</tt> itself, then an SSL-enabled version of either <a
href="http://curl.haxx.se">cURL</a> or <a
href="http://www.gnu.org/software/wget/">wget</a> must be available.
<tt>s-audit_pchdefs.sh</tt> has no other dependencies.</p>

<p>By default the script expects to find cURL at
<tt>/usr/local/bin/curl</tt> and wget at <tt>/usr/sfw/bin/wget</tt>. These
paths are hard-coded into the script, so you may need to change them.</p>

<h2>Configuration</h2>

<p>Since Oracle no longer make <tt>patchdiag.xref</tt> publicly available,
you must supply a valid Oracle support username and password. These are
hard-coded into the script as clear text, and will be visible in the process
table when cURL or wget are running. If this is unacceptable for you, obtain
<tt>patchdiag.xref</tt> in another way and run <tt>s-audit_pchdefs.sh</tt>
with the path to the already downloaded file as an argument.</p>

<h2>Usage</h2>

<p><tt>s-audit_pchdefs.sh</tt> is invoked by:</p>

<pre class="cmd">
$ s-audit_pchdefs.sh [-k] [-d dir] <-x|patchdiag>
</pre>

<p>The options are:</p>

<dl>
	<dt><tt>-x</tt></dt>
	<dd>Download a fresh <tt>patchdiag.xref</tt> file from Oracle (the exact
	URI is given by running the script without arguments.) The file will be
	downloaded into <tt>/var/tmp</tt>, and hover files will
	be generated from it.</dd>

	<dt><tt>-k</tt></dt>
	<dd>If <tt>-x</tt> is used to download a new <tt>patchdiag.xref</tt>
	file, do not remove said file after processing.</dd>

	<dt><tt>-d directory</tt></dt>
	<dd>Write hover files to the given directory. If this option is not
	supplied, files will be written to the current working directory.</dd>
</dl>

<p>If <tt>-x</tt> is not supplied, then the path to an existing
<tt>patchdiag.xref</tt> file must be given as an argument.</p>

<h2>Examples</h2>

<p>In the following examples, we will assume the interface looks for patch
hover files in <tt>/www/s-audit/_lib/pch_defs</tt>. This path is defined in
the <tt>_conf/site_config.php</tt> file.</p>

<p>To generate a new batch of hover files from an existing
<tt>patchdiag.xref</tt> file:</p>

<pre class="cmd">
$ s-audit_pchdefs.sh -d /www/s-audit/_lib/pch_defs /var/tmp/patchdiag.xref
</pre>

<p>To download a fresh <tt>patchdiag.xref</tt>, generate hover files from
it, then remove it:</p>

<pre class="cmd">
$ s-audit_pchdefs.sh -d /www/s-audit/_lib/pch_defs -x
</pre>

<p>To automatically generate new patch lists every Sunday night, add the
following to the crontab of a user who has permission to write to the
<tt>pch_defs</tt> directory.</p>

<pre>
0 1 * * 0 /usr/local/bin/s-audit_pchdefs.sh -d /www/s-audit/_lib/pch_defs -x
>/dev/null 2>&1
</pre>


<h2>Source</h2>

<?php

$manifest = new codeBlock("s-audit_pchdefs.sh");
echo $manifest->show_script();

$pg->close_page();

?>
