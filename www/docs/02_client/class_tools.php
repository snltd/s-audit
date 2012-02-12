<?php

include("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$menu_entry = "tool audits";
$pg = new docPage("software tool audits");

?>

<h1>Tool Audits</h1>

<p>Invoked by</p>

<pre class="cmd">
# s-audit.sh tool
</pre>

<h2>Checks</h2>

<dl>

	<dt>openssl</dt>
	<dd>Gets the version of <a href="http://www.openssl.org">OpenSSL</a>.
	Requires the <tt>openssl</tt> binary - libraries are ignored.</dd>

	<dt>rsync</dt>
	<dd>Reports the version of the <a
	href="http://www.samba.org/rsync/">rsync</a> client.</dd>

	<dt>mysql_c</dt>
	<dd>Reports the version of the <a href="http://www.mysql.com">MySQL</a>
	client tool, <tt>mysql</tt>.</dd>

	<dt>sqlplus</dt>
	<dd>Reports the version of Oracle SQL*Plus. </dd>

	<dt>svn_c</dt>
	<dd>Reports the version of the <a
	href="http://subversion.tigris.org/">Subversion</a> client,
	<tt>svn</tt>.</dd>

	<dt>java</dt>
	<dd>Reports the version of the <tt>java</tt> binary. If <tt>javac</tt>
	exists in the same directory, report this as a JDK installation, if not,
	report it as a JRE.</dd>

	<dt>perl</dt>
	<dd>Reports the version of <a href="http://www.perl.org">perl</a>.</dd>

	<dt>php_cmd</dt>
	<dd>Reports the version of a <a href="http://www.php.net">PHP</a>
	command-line binary.</dd>

	<dt>python</dt>
	<dd>Report the version of <a
	href="http://www.python.org">python</a>.</dd>

	<dt>ruby</dt>
	<dd>Report the version of <a href="http://ruby-lang.org">Ruby</a>.</dd>

	<dt>node</dt>
	<dd>Report the version of the node.js <tt>node</tt> executable.</dd>

	<dt>cc</dt>
	<dd>Report the version of what is now called <a
	href="http://www.oracle.com/technetwork/server-storage/solarisstudio/overview/index.html">Oracle
	Solaris
	Studio compiler software</a>, (previously known as Sun Studio, SunONE,
	Forte and Sun Workshop), along with a list of supported languages. (From
	C, C++ and Fortran.) The version number may not make a lot of sense
	(it's probably 5.x), but the <a href="../03_interface">PHP interface</a>
	will convert it to a marketing number.</dd>

	<dt>gcc</dt>
	<dd>Report the version of <a href="http://gcc.gnu.org">GCC</a>, along
	with a list of supported languages. (From C, C++, Fortran, Java,
	Objective C and Objective C++.)</dd>

	<dt>pca</dt>
	<dd>Report the version of <a
	href="http://www.par.univie.ac.at/solaris/pca/">PCA</a>, or Patch Check
	Advanced, a Solaris patch management tool.</dd>

	<dt>nettracker</dt>
	<dd>Report the version of Nettracker. Only tested with version 7.</dd>

	<dt>saudit</dt>
	<dd>Report the version of s-audit itself.</dd>

	<dt>scat</dt>
	<dd>Report the version of <a
	href="http://www.sun.com/download/products.xml?id=3fce7df0">Sun Crash
	Analysis Tool</a>.</dd>

	<dt>explorer</dt>
	<dd>Report the version of <a href="http://www.sun.com/service/stb/">Sun
	Explorer</a>. Also reports on whether or not
	Explorer is configured.</dd>

	<dt>sccli</dt>
	<dd>Report the version of Sun <tt>sccli</tt>. Requires root
	privileges.</dd>

	<dt>sneep</dt>
	<dd>Report the version of <a href="http://www.sun.com/service/stb/">Sun
	Sneep</a>. Omitted in local zones.</dd>

	<dt>vts</dt>
	<dd>Report the version of <a
	href="http://www.sun.com/oem/products/vts/">Sun VTS</a>. Omitted in
	local zones.</dd>

</dl>

<?php

$pg->close_page();

?>

