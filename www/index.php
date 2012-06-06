<?php


require_once("$_SERVER[DOCUMENT_ROOT]/_conf/s-audit_config.php");

//------------------------------------------------------------------------------
// CLASSES

class auditGroupDesc {

	// Describe audit groups

	public function desc_group($fs, $dir)
	{
		$ret2 = false;
		$hd = $dir . "/hosts";
		$info = $dir . "/info.txt";

		if (!file_exists($hd) || !is_dir($hd))
			return false;

		$gn = basename($dir);
		$hosts = $fs->get_files($hd, "d");
		$nd = count($hosts);

		$ret = ($nd > 0)
			? "\n\n<dt><a href=\"s-audit/index.php?g=$gn\">$gn</a></dt>"
			: "\n\n<dt>$gn</dt>";

		if (file_exists($info))
			$ret .= "\n  <dd><em>" . file_get_contents($info) . "</em></dd>";

		// Most recent update?

		$latest = 0;

		foreach($hosts as $host_dir) {

			foreach($fs->get_files($host_dir, "f") as $hf) {
				$upd_t = filemtime($hf);
				if ($upd_t > $latest) $latest = $upd_t;
			}

		}

		$ret .= "\n  <dd><strong>$nd</strong> host";

		if ($nd != 1) $ret .= "s";
		
		$ret .= ".";
		
		if ($nd > 0) {
			$last_upd = round((time() - $latest) / 86401);

			if ($last_upd == 0)
				$last_txt = "today.";
			elseif ($last_upd == 1)
				$last_txt = "yesterday.";
			else
				$last_txt = date("jS M Y", $latest)
				. ". ($last_upd days ago.)";

			$ret .= " Most recent audit added $last_txt</dd>";
		}

		// Use a sub-list to report on other files

		if (file_exists("${dir}/friends.txt"))
			$ret2 .= "\n  <li>Friends file exists.</li>";

		$zm = new ZoneMapBase();
		$k = $zm->set_extra_paths($dir);
		unset($k["extra_dir"]);

		foreach($k as $f=>$p) {
			
			if (file_exists($p))
				$ret2 .= "<li>Network <tt>$f</tt> file exists.</li>";

		}

		foreach (array("platform", "os", "net", "fs", "application",
		"tools", "security") as $cl) {

			if (file_exists("${dir}/extra/${cl}.audex"))
				$ret2 .= "<li>static data for $cl audits</li>";
		}

		if ($ret2)
			$ret .= "\n<dd><ul>$ret2\n</ul></dd>";

		return $ret;

	}

}

//------------------------------------------------------------------------------
// SCRIPT STARTS HERE

$pg = new indexPage(SITE_NAME . " s-audit interface");

$err_str = "<tt>" . AUDIT_DIR .  "</tt>. Please refer to <a
href=\"http://snltd.co.uk/s-audit/demonstrator/docs/01_installation/\">the
s-audit documentation</a>.";

if (!is_dir(AUDIT_DIR))
	$pg->f_error("No audit directory found. Expecting $err_str", 2);

$addi = new DirectoryIterator(AUDIT_DIR);

$fs = new filesystem();
$groups = $fs->get_files(AUDIT_DIR, "d");

if (count($groups) == 0)
	$pg->f_error("No audit data found in $err_str");
else {
	$agd = new auditGroupDesc();

	echo "<p>This is s-audit version " . MY_VER . ", running on " .
	php_uname("n") . ". The following audit groups are
	available:</p>\n\n<dl id=\"group\">";

	foreach($groups as $g)
		echo $agd->desc_group($fs, $g);

	echo "</dl>\n\n<p>Note that &quot;hosts&quot; refers to unique,
	autonomous installations of Solaris. A host may be a physical server, a
	logical domain, or a VirtualBox or VMWare virtual machine. Global zones
	are not counted.</p>";
}

$pg->close_page();

?>

