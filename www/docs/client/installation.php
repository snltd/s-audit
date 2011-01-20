<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "installation";
$pg = new docPage("s-audit.sh installation");

?>

<h1>Interactive Usage</h1>

<h2>One-shot usage</h2>

<p>The simplest way to use <tt>s-audit.sh</tt> is copy the script to the
machine you want to interrogate, and <a href="usage.php">run it</a>. There
are no dependencies or special requirements, and the output will be easy to
understand.</p>

<h2>Gathering data for the interface</h2>

<p>If you want to audit a machine and view the results via the <a
href="../interface">PHP interface</a>, you need to supply the <tt>-p</tt>
option to produce machine-parseable audit files, then copy those files to
the host running the interface.  You can use <tt>s-audit.sh</tt>'s built-in
SCP support (<tt>-R</tt> flag) if you do suitable key exchange, or use the
<tt>-f</tt> flag to write the files directly to an NFS directory.
(Automounter is useful for this.)</p>

<h1>Automated Usage</h1>

<h2>Running at set times</h2>

<p>s-audit is useful not only for auditing a new system, but great at
keeping an eye on one you know well. I run it twice a day so I always have
an up-to-date overview of all my machines. Originally I ran it through cron
like so:</p>

<pre>
0 7,13 * * * /usr/local/bin/s-audit.sh -p -R audit@tap-audit:/var/s-audit/live
</pre>

<p>(tap-audit is the zone running the PHP interface.) But since the advent
of the SMF method, I changed my cron job to use that.</p>

<pre>
0 7,13 * * * svcadm s-audit refresh
</pre>


<h2>Running after a reboot</h2>

<p>If you have Solaris 10 or later, install the <a href="smf.php">SMF
manifest</a> and set the properties as described. For older versions, you'll
have to write a simple rc script.</p>

<p>Running s-audit at boot-time is especially useful after patching or
upgrading.</p>

<?php

$pg->close_page();

?>

