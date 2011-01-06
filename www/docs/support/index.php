<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "support files";
$pg = new docPage("support files");

?>

<h1>s-audit Support Files</h1>

<p>Though the <tt>s-audit.sh</tt> tool works with no dependencies, a number
of additional tools and helpers have grown up around it. Chief among these
is the PHP interface which presents audit results, but the following tools
also exist to help you get the most out of s-audit.</p>

<dl>
<dt><a href="smf.php">an SMF manifest</a></dt>
<dd>This provides a ready-made way to integrate s-audit with Solaris's SMF
framework. It is useful to have machines audit themselves on a reboot, or to
perform on-demand audits simply by refreshing the service.</dd>

</dl>

<?php

$pg->close_page();
?>


