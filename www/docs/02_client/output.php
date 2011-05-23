<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "client output";
$pg = new docPage($menu_entry);

?>

<h1>Output</h1>

<h2>Writing to a Terminal</h2>

<p>In normal interactive usage <tt>s-audit.sh</tt> writes its output to
standard out, producing two columns pairing test names with their outputs.
For example:</p>

<pre>
<strong># s-audit.sh platform</strong>

<strong>'platform' audit on cs-db-01</strong>

       hostname : cs-db-01
       hardware : Sun Fire V210 (64-bit SPARC)
 virtualization : none (global zone)
            CPU : 2 @ 1336MHz
         memory : 2048Mb physical
         memory : 2.0Gb swap
  serial number : TM51920257
            OBP : 4.30.4.a
       ALOM f/w : v1.6.10
        ALOM IP : 10.10.8.203
        storage : disk: 2 x 73GB SCSI
        storage : CD/DVD: 1 x IDE (empty)
audit completed : 13:12:49 28/02/11

------------------------------------------------------------------------------
</pre>

<p>The audit class and hostname are displayed in bold face (or may appear in
a different colour, depending on your terminal.</p>

<p>The example above was rum as the root user. Running <tt>s-audit.sh</tt>
as a normal user on the same host produces the following output.</p>

<pre>
<strong>$ s-audit.sh platform</strong>

------------------------------------------------------------------------------

           WARNING: running this script as an unprivileged user may
             not produce a full audit. Many tests, including ALOM,
                 FC enclosure, virtualization will not be run.

------------------------------------------------------------------------------

<strong>'platform' audit on cs-db-01</strong>

       hostname : cs-db-01
       hardware : Sun Fire V210 (64-bit SPARC)
            CPU : 2 @ 1336MHz
         memory : 2048Mb physical
         memory : 2.0Gb swap
  serial number : TM51920257
            OBP : 4.30.4.a
        storage : disk: 2 x 73GB SCSI
        storage : CD/DVD: 1 x IDE (empty)
audit completed : 13:11:54 28/02/11

------------------------------------------------------------------------------
</pre>

<p>Note that, as the warning message said, certain tests were not run. If a
test is not run, or if it runs but finds no information, for instance trying
to find the DNS server on a machine which does not use DNS, then no
information is displayed. You will not see &quot;none&quot;, or &quot;not
installed&quot; as output.</p>

<h2>Writing to Files</h2>

<p>When <tt>s-audit.sh</tt> is run with the <tt>-f dirname</tt> option, it
writes its output to one or more files. These files will be written in a
subdirectory of <tt>dirname</tt> whose name is the name of the zone in which
<tt>s-audit.sh</tt> is run.</p>

<h3>Human-Readable Output</h3>

<p>Supplying <tt>-f dirname</tt> will make <tt>s-audit.sh</tt> 
write a separate file containing the output of each audit it performs. That
is an individual file for each audit class performed in each zone. Filenames
are of the form:</p>

<pre>hostname.class.saud</pre>

<p>where <tt>hostname</tt> is the name of the zone where the audit is run,
<tt>class</tt> is the name of the audit class, and <tt>saud</tt> is a fixed
filename suffix.</p>

<p>As an example:</p>

<pre>
<strong># id</strong>
uid=0(root) gid=0(root)
<strong># uname -n</strong>
myserver
<strong># zoneadm list</strong>
global
myzone1
myzone2
myzone3
<strong># s-audit.sh -p /tmp/out -z myzone1,myzone3 os net</strong>
Writing audit files to /tmp/out/myserver
<strong># ls -1 /tmp/out/myserver</strong>
myzone1.net.saud
myzone1.os.saud
myzone2.net.saud
myzone2.os.saud
</pre>

<p>If output is sent to files by a non-root user, the non-root warning shown
above will still be written to the user's terminal.</p>

<h3>Machine-parseable Output</h3>

<p>When machine-parseable output is produced, by supplying the <tt>-p</tt>
option, it is written to standard out, or, if <tt>-f</tt> is also used, to a
single file called <tt>hostname.machine.saud</tt>, where <tt>hostname</tt>
is the name of the server or its global zone.</p>

<p>Machine parseable output begins with a header of the format</p>

<pre>@@BEGIN_s-audit v-x.y YYYY MM DD hh mm</pre>

<p>Where x and y are the major and minor version numbers of
<tt>s-audit.sh</tt>. The file must end with</p>

<pre>@@END_s-audit</pre>

<p>If either of these lines are missing or corrupt, the PHP interface will
not parse the file.</p>

<p>Within the file, audits are delimited by lines of the form:</p>

<pre>BEGIN class@hostname</pre>

<p>and</p>

<pre>END class@hostname</pre>

<p>again, if these lines are not present, the interface will not attempt to
parse the audit data.</p>

<p>Tests and their outputs are presented as a <tt>key=value</tt> pair, one
per line. Some tests have multiple results, and present each result as its
own pair on its own line.</p>

<?php

$pg->close_page();

?>

