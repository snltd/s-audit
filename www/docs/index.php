<?php

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new docPage("about s-audit");

?>

<p><tt>s-audit</tt> is a piece of software written to audit Solaris systems.
In this case &quot;audit&quot; is not used in the sense of account or
filesystem auditing - <tt>s-audit</tt> is not like <tt>audit(1m)</tt> or
<tt>bart(1m)</tt> - rather, it reports on what a Solaris system is made of,
and can do. The nearest tool to it is probably Sun Explorer, but s-audit
collects higher-level information, and presents it in a much friendlier
way.</p>

<p>I wrote the first version of <tt>s-audit</tt> when I began working on a
large virtualization project on an essentially undocumented system, and I
needed to know exactly what I was dealing with. It grew from a small script
reporting on patch-levels, versions of installed software and what sites
were on which web server, into a much more powerful tool performing a number
of different kinds of audit. The information it produced soon became
somewhat overwhelming as plain text, so I wrote a PHP front-end, which has
grown hand-in-hand with the client script.</p>

<p>s-audit is split into three parts. The <a href="02_client">client</a> is
a shell script which is run on the machine you wish to audit. This is the
only essential, and may prove useful on its own. It is written to be
compatible with the version of ksh88 shipped with Solaris 2.6, and has no
other dependencies. Therefore it will run on any Solaris system running
today, however minimal. The client performs different &quot;classes&quot; of
audit, looking at different aspects of the machine on which it is run.</p>

<p>The client writes human-readable or machine-parseable information to
standard out or to files. The files are understood, and turned into pretty,
coloured grids, by <a href="03_interface">the interface</a>; a PHP
application which runs on a web server.</p>

<p>The information the interface produces can be enhanced and supplemented
by the output of a number of <a href="04_extras">support programs</a>, some,
all, or none of which may be useful in any given environment.</p>

<?php

$pg->close_page();

?>

