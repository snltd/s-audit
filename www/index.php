<?php

require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// CLASSES

class auditGroupDesc {

	// Describe audit groups

	public function desc_group($dir)
	{
		$hd =$dir . "/hosts";
		$di = new DirectoryIterator($hd);
		$nd = 0;

		$info = $dir . "/info.txt";
		$gn = basename($dir);

		$ret = "\n\n<dt><a href=\"s-audit/index.php?g=$gn\">$gn</a></dt>";

		if (file_exists($info))
			$ret .= "\n  <dd>" . file_get_contents($info) . "</dd>";

		foreach($di as $d) $nd++;

		$hdmt = filemtime($hd);

		$ret .= "\n  <dd><strong>$nd</strong> hosts.  Most recent audit
		added " .
		date("jS M Y", $hdmt) . ". (" . round((mktime() - $hdmt) / 86400) .
		" days ago.)</dd>";

		return $ret;

	}

}

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new indexPage(SITE_NAME . " s-audit interface");

$addi = new DirectoryIterator(AUDIT_DIR);

if (count($addi) == 0)
	$pg->error("No audit data found");
else {
	$agd = new auditGroupDesc();

	echo "<p>This is s-audit version " . MY_VER . ", running on " .
	php_uname("n") . ". The following audit groups are
	available:</p>\n\n<dl id=\"group\">";

	foreach($addi as $d) {
		
		if ($d->isDir() && ! $d->isDot()) 
			echo $agd->desc_group($d->getPathName());
	}

	echo "</dl>\n\n<p>Note that &quot;hosts&quot; refers to unique,
	autonomous installations of Solaris. A host may be a physical server, a
	logical domain, or a VirtualBox or VMWare virtual machine. Global zones
	are not counted.</p>";
}

$pg->close_page();

?>
