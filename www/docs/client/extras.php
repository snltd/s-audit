<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/site_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "the extras file";

$pg = new docPage("the extras file");

?>

<p><tt>s-audit.sh</tt> is able to retrieve information from an
&quot;extras&quot; file stored on the machine being audited. This can be
used for information which cannot be retrieved in any other way; for
instance a server's physical location, service contract number, or the host
of a virtualized environment.</p>

<h2>File Location</h2>

<p>By default <tt>s-audit.sh</tt> will look for an extras file at</p>

<pre>
/etc/s-audit_extras
</pre>

<p>but a user may specify a different file with the <tt>-e</tt> option.  A
user-specified file will override the default file location, even if it is
not found.</p>

<h2>File Format</h2>

<p>The file format is one record per line, with three tab-separated
fields:</p>

<pre>
audit_class    key    value
</pre>

<p><strong>Note:</strong> you must use tabs, not spaces.</p>

<p><tt>audit_class</tt> must be one of <tt>platform</tt>, <tt>os</tt>,
<tt>tool</tt>, <tt>app</tt>, <tt>security</tt>, <tt>hosted</tt>, or
<tt>plist</tt>. The key and value pair will be put into the audit output
before the &quot;time&quot; field, in the order they occur in the extras
file.</p>

<p>Lines not following this exact format (including those with leading
whitespace) will be ignored, so may be used for comments.</p>

<h2>Example extras file</h2>

<pre>
# s-audit "extras" file

platform     location      server room 1
platform     cabinet       A2
os           built by      generic jumpstart profile
</pre>

<?php

$pg->close_page();

?>

