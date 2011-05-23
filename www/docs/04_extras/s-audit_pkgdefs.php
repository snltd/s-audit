<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "s-audit_pkgdefs.sh";
$pg = new docPage("The s-audit_pkgdefs.sh script");

?>

<h1>The <tt>s-audit_pkgdefs.sh</tt> script</h1>

<p>In the single-server or server comparison pages of the PHP interface,
hovering the cursor over a patch or package name will pop up a box giving a
short description of that patch or package. For this to work, the interface
requires &quot;hover maps&quot;. These are stored in <tt>_lib/pkg_defs</tt>
and <tt>_lib/pch_defs</tt> for packages and patches respectively.</p>

<p>The <tt>s-audit_pkgdefs.sh</tt> script generates package hover maps by
looking at the <tt>NAME</tt> field of the <tt>pkginfo</tt> files in a
Solaris Jumpstart install image.  It puts all the descriptions in a big
array, and puts that array in a file which can be processed by the
interface.</p>

<p>Hover maps are provided for all currently supported Solaris releases (at
the time of writing, 2.6 to 10 update 9), so it is unlikely that a user
would ever need to run this script and generate them. It is provided for the
sake of completeness.</p>

<p>Note that the script only works with SVR4 packages.</p>

<h2>Usage</h2>

<p><tt>s-audit_pkgdefs.sh</tt> accepts a single argument, the path to a
directory containg a Solaris install image.</p>

<pre class="cmd">
$ s-audit_pkgdefs.sh &lt;directory&gt;
</pre>

<p>It writes files in the current working directory.</p>

<h2>Source</h2>

<?php

$scr = new codeBlock("s-audit_pkgdefs.sh");
echo $scr->show_script();

$pg->close_page();
?>


