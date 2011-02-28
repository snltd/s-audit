<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "internals";
$pg = new docPage("s-audit.sh internals");

?>

<h1>Design</h1>

<h2>Architecture</h2>

<p><tt>s-audit.sh</tt> is a big, monolithic shell script. Briefly, here's
why. It's written to work with Korn shell 88, because every Solaris machine
in history has one. It's definitely compatible with the ksh in Solaris 2.6,
and most likely with the one in 2.5.1, but I haven't yet been able to test
that. The script itself spends most of its time waiting for external
programs to return, so there would be little speed increase whatever
language it was written in.  Also, well-written shell scripts are quicker
than many people realize.</p>

<p>Though it has been tempting to break <tt>s-audit.sh</tt> into
&quot;modules&quot;, perhaps one per audit class, I have kept it whole. Part
of its usefulness is being able to simply copy one script anywhere on the
machine, run it, and get output. I don't want an installation procedure,
even something as simple as unpacking a tar file.</p>

<p>In the future I may fork a separate, modular, &quot;enterprise&quot;
version.</p>

<h2>Privileges</h2>

<p>Though it can produce useful information as a normal, non-privileged
user, <tt>s-audit.sh</tt> is really supposed to be run as root. It is
non-destructive, and well tested, so you should be safe. (Usual disclaimers
apply.) It is not currently RBAC-aware, though that may change.</p>

<h1>How it works</h1>

<p>For all its size, <tt>s-audit.sh</tt> is not a very complicated script.
The vast majority of it is functions which each perform a single test, say,
getting the version number of a piece of software. We will return to those
later, after we look at variables and the main loop</p>

<h2>Variables</h2>

<h3>PATH, and finding binaries</h3>

<p>The most important variable is the <tt>PATH</tt>. <tt>s-audit.sh</tt>
finds programs by having a huge <tt>PATH</tt>, which attempts to take in
every directory where the things it audits could possibly be. There are two
paths.  <tt>PATH</tt> is, of course, what it normally is, and <tt>SPATH</tt>
is a search path used by the <tt>find_bins()</tt> function.</p>

<p>Rather than running long, disk-thrashing <tt>find(1)</tt>s,
<tt>s-audit.sh</tt> breaks up the <tt>SPATH</tt>, temporarily setting the
<tt>PATH</tt> to each element of it, then running the <tt>whence</tt>
internal. This is fast and efficient.</p>

<h2>Special Functions</h2>

<dl>

	<dt><tt>disp()</tt></dt>
	<dd>This function is used to format the output of each check. Every
	<tt>get_</tt> function must produce its output by calling
	<tt>disp()</tt> with two arguments, a key, and a value. (Actually, there
	can be many arguments, the first is always the key, others are
	concatenated together into a single value by <tt>disp()</tt>, this
	circumvents a lot of difficult quoting in the <tt>get_</tt>
	functions.)</dd>

	<dd><tt>disp()</tt> formats output as human readable or machine-parseable,
	depending whether the <tt>PARSEABLE</tt> variable is set.</dd>

	<dt><tt>find_bins()</tt></dt>
	<dd>A requirement of <tt>s-audit.sh</tt> is that it reports multiple
	versions of the same binary. Having three versions of perl installed has
	the potential to cause problems, and on a system you have inherited, you
	may not know there are three versions of perl. To do this job,
	<tt>s-audit.sh</tt> uses the <tt>find_bins()</tt> function. It works
	through the <tt>SPATH</tt> search path, one directory at a time, looking
	to see if the binary being searched for is there. If it is, reported.
	Links are followed, and if multiple versions of a binary are found, the
	function compares their inode numbers and only reports unique ones. In
	that event, the shortest path name is chosen, which experience shows is
	usually the genuine path.</dd>

	<dt><tt>my_pgrep()</tt></dt>
	<dd><tt>pgrep</tt> is an invaluable tool, but it has not always been in
	Solaris, and not always been as capable as it is now. The
	<tt>my_pgrep</tt> function is used in place of <tt>pgrep</tt>, producing
	uniform output whatever version of Solaris is being used. On first
	invocation, the function works out what tools to use to get the desired
	ouput, and caches that decision in the <tt>USE_PGREP</tt> variable.
	Therefore, on subsequent calls, the value of the variable is used,
	speeding things up.</dd>

	<dt><tt>timeout_job()</tt></dt>
	<dd>Certain jobs can take a long time to complete, or never complete. I
	have had problems with <tt>prtdiag</tt> never returning on T2000
	servers, so it is now run through the <tt>timeout_job()</tt> function.
	If a job run via this wrapper function is still active after
	<tt>T_MAX</tt> seconds, then it, and all its children, are terminated
	and the function returns 1.</dd>

</dl>

<h2>Adding Checks</h2>

<p>Create a function which gets the version of, say, &quot;myprog&quot;. The
function should be called <tt>get_myprog()</tt>, and should call either the
<tt>disp()</tt> or <tt>is_run_ver()</tt> functions, depending on whether or
not you want to check for a running program.</p>

<p>To check for multiple installed copies of a program, wrap the body of
your function in a loop, using the find_bins function to get a list of
binaries to run. Finally add <tt>myprog</tt> to the appropriate test
list.</p>

<?php

$pg->close_page();

?>
