<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "static data";
$pg = new docPage("extending the interface with static Data");
$dh = new docHelper();

?>

<h1>Static Data</h1>

<p><tt>s-audit.sh</tt> can only audit single machines, and can only retrieve
data that the machine itself can provide. You may wish to enhance the view
of your systems by feeding static data to the interface.</p>

<p>This static data can be added on a per-machine, per-audit-class basis,
and is useful for storing information such as server location, asset number,
owner, or serial number.</p>

<p>Static data can be used to add new fields to an audit, for instance
adding to each server the name of the department which owns them, or to fill
in blanks in an existing field. For instance, <tt>s-audit.sh</tt> could be
gathering serial numbers for your SPARC machines, but you would have to use
static data to display the serial numbers for your x86 boxes. If data is
defined in an audit and as static data, the audit data is displayed. Data
from static files is shown on a pink background.</p>

<h2>Adding Static Data</h2>

<p>It is possible to add a single file of static data for each class of
audit for each audit group. These should be created in the <tt>extras/</tt>
subdirectory of the audit group's main directory, and named
<tt>class.audex</tt>, where <tt>class</tt> is the audit class to which you
are adding, and they should follow the &quot;ini&quot; file format.</p>

<h2>File Format</h2>

<p>The file must contain at least one section, which equates to a field.
Fields are defined in <tt>[</tt>square brackets<tt>]</tt>.  After the field
definition, add information for host in <tt>hostname=value</tt> format,
one host per line. If &quot;value&quot; contains whitespace or special
characters, it must be quoted.</p>

<p>The special keyword <tt>AFTER</tt> can be used to request that the
interface inserts your static field immediately after a given field in the
audit grid. If that field does not exist, or the <tt>AFTER</tt> keyword is
not used, static data is appended to the audit files, so appears at the
right of the table.</p>

<h2>Example File</h2>

<p>The following file is being used to show asset tag numbers for a group of
servers, and to fill in a couple of ALOM firmware versions which cannot be
gathered by <tt>s-audit.sh</tt>. The information is added to the platform
audit view, and the &quot;asset tag&quot; field is to the right of the
existing &quot;serial number&quot; field.</p>

<p>The file is stored as <tt>platform.audex</tt> in the audit group's
<tt>extra</tt> subdirectory.</p>

<pre>
[asset no]
AFTER="serial number"
s-ws-01=489232
s-ws-02=489233
s-ws-03=489234
s-ws-04=489235
s-ws-05=489236
s-db-01=484821
s-db-01=481956
s-infra-02=42389

[ALOM F/W]
s-infra-02=6.7.11
s-infra-01=6.7.11

[location]
s-infra-01="data centre"
s-infra-02="data centre"
s-ws-01="comms room"
s-ws-02="comms room"
</pre>

<h2>Location</h2>

<?php

$pg->close_page();

?>

