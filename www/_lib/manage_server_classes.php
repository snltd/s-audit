<?php

//============================================================================
//
// manage_server_classes.php
// -------------------------
//
// Classes which allow the user to move servers between "live" and
// "obsolete", and remove them from the auditor altogether.
//
// R Fisher
//
// v1.0
// Please record changes below.
//
//============================================================================

class ManageServers {

	// This class groups together the functions that do the server
	// management.

	function prt_err($msg)
	{
		// Print errors

		echo "\n\n<p class=\"error\">$msg</p>\n";
		return 1;
	}

	function check_manage_dirs($dirlist)
	{
		// Check that all the directories we need to manage can be managed
		// $dirlist is an array of directories to check

		$err = 0;

		foreach($dirlist as $dir) {
		
			if (!is_dir($dir))  {
				$err += prt_err("$dir does not exist or is not a directory");
				continue;
			}

			if (!is_writeable($dir))
				$err += prt_err("$dir is not writeable");
		}

		return $err;
	}

	function get_state_level($dir)
	{
		// To decide how files can be moved, each state is given a priority
		// level. The higher the number, the higher the priority.

		// * Servers can always be moved down in priority. If data for that
		// server already exists in the destination level, it is removed

		// * Servers can only be moved up in proirity if they do not exist
		// at the destination level.

		// * Zones can always be moved down in priority. If the parent zone
		// is not in the destination level, an empty directory is created to
		// hold it

		// * Zones can only be moved up in priority if their parent global
		// zone is already in the destination level.

		$levels = array( LIVE_DIR => 3, OBSOLETE_DIR => 2, TRASH_DIR => 1);
		
		return $levels[$dir];

	}

	function move_server($server, $src, $dest)
	{
		// Move servers between states. 

		// src directory is relative to the AUDIT_DIR, dest directory is
		// fully qualified as this function is called with a constant

		$s_fq_dir = AUDIT_DIR . "/$src";
		$d_fq_dir = $dest;
		$s_pri = get_state_level($s_fq_dir);
		$d_pri = get_state_level($dest);
		$s_state = $src;
		$d_state = basename($dest);

		$s_map = new ZoneFileMap($s_fq_dir, true);
		$d_map = new ZoneFileMap($d_fq_dir, true);
		$src_dir = $s_map->get_dir($server);
		$err = 0;

		// We checked earlier that all the managed directories were present
		// and writable, so we don't need to do that again.

		// For global zones and physical servers we move a single directory.
		// For local zones, we move a bunch of files. What do we have?

		if ($s_map->is_global($server)) {
			$dest_dir = "${dest}/$server";

			// Does the server really exist in the given directory? (Should
			// only happen if someone's messing with the query string.)

			if (is_dir($src_dir)) {

				// Is there already a server of this name in the target
				// directory?

				if (file_exists($dest_dir)) {

					// What we do now depends on the priority levels. If
					// we're moving down in priority, merge everything in
					// with the old directory. Files may be overwritten.

					if ($s_pri > $d_pri) {

						// Move everything, then remove the directory

						if (filesystem::move_files(filesystem::get_files($src_dir,
							"f", "audit.*"), $dest_dir))
							filesystem::rm_empty_dir($dest_dir);
						else
							$err++;

					}
					else {

						// we're moving up in priority, that's a problem

						$err += prt_err("information relating to $server
						already exists in $d_state directory");
					}

				}
				else {

					// Nothing already there, so just move the directory.

					if (!rename($src_dir, "${dest}/" . basename($src_dir)))
						$err += prt_err("unable to move $src_dir");
						
				}

			}
			else {
				$err += prt_err("$server is not in $src [$src_dir]");
			}

		}
		else {

			// Zones are more complicated.  Zone files need a server
			// directory in the target dir. Work out what that directory
			// should be called

			$parent = $s_map->get_parent_zone($server);
			$dest_dir = "${dest}/$parent";

			// If we're moving down in priority, and the destination
			// directory isn't there, create it

			if ($s_pri > $d_pri) {

				if (!is_dir($dest_dir))
					mkdir($dest_dir);

				$move = true;
			}
			else {
				
				// We're going up in priority, so we only do the move if
				// there's a directory set up to receive the files

				if (is_dir($dest_dir) && $d_map->has_data($parent))
					$move = true;
				else
					$err += prt_err("can't put $server in $d_state as its parent
					global zone is not in that state.");
			}


			// Have we been given the go-ahead to move files?

			if (isset($move)) {

				// If we've moved the last file out of a directory, remove
				// the directory

				if (filesystem::move_files(filesystem::get_files($src_dir,
				"f", $s_map->get_fbase($server)), $dest_dir))
					filesystem::rm_empty_dir($src_dir);
				else
					$err++;

			}

		}

		return ($err == 0)
			? true
			: false;

	}

}

class ManageLiveList extends CompareList
{
	protected $f3 = "obsolete";
	protected $f4 = "remove";
	protected $fields;
	protected $zone_options = true;
	protected $map;
	protected $src = LIVE_DIR;
	protected $can_be_empty = false;
	protected $title = "live servers";

	public function __construct() {
		$this->map = new ZoneFileMap($this->src, $this->can_be_empty);

		if (is_object($this->map)) {
			$this->fields = array("hostname", "last audit", $this->f3,
			$this->f4);
			$this->src = basename($this->src);
		}
		else
			return false;
	}

	private function make_mv_link($server, $field)
	{
		// Shorthand function to create clickable action links

		return "<a href=\"" . $_SERVER["PHP_SELF"] .
		"?c=${field}&amp;d=$this->src&amp;s=$server\">$field</a>";
	}

	public function show_grid()
	{
		if (sizeof($this->map->list_all()) == 0)
			return "<p>There are currently no servers in this state.</p>";
		else
			return parent::show_grid();
	}
	
	public function show_title()
	{
		// Just print a title for the list

		return "\n\n<h3>$this->title</h3>\n\n";
	}

	public function grid_body()
	{
		$ret_str = "";

		// We link back to the main page with a context and a server/zone to
		// work on

		foreach ($this->map->globals as $server) {
			
			$serv_dat = GetServers::parse_file($this->map->get_base($server)
			. ".platform");

			$completed = 
			HostGrid::show_audit_completed($serv_dat["audit completed"]);

			// Only make global zones "moveable" if they actually contain
			// the audit data for the global zone. We create "pretend"
			// globals to move locals, and they shouldn't be moveable.

			if ($this->map->get_base($server)) {
				$f3_link = $this->make_mv_link($server, $this->f3);
				$f4_link = $this->make_mv_link($server, $this->f4);
			}
			else 
				$f3_link = $f4_link =false;

			$ret_str .= "\n<tr class=\"server\">" . new Cell($server,
			"server") . $completed . new Cell($f3_link) .  new
			Cell($f4_link) . "</tr>";

			$zones = $this->map->list_server_zones($server);

			if (is_array($zones)) {

				foreach($zones as $zone) {
					$zone_dat =
					GetServers::parse_file($this->map->get_base($zone) .
					".platform");

					$completed =
					HostGrid::show_audit_completed($zone_dat["audit completed"]);

					$f3_link = $this->make_mv_link($zone, $this->f3);
					$f4_link = $this->make_mv_link($zone, $this->f4);
					$ret_str .= "\n<tr class=\"zone\">" . new Cell($zone,
					"zone") . $completed . new Cell($f3_link). new
					Cell($f4_link) .  "</tr>";
				}

			}
		
		}

		return $ret_str;
	}

}

class ManageObsoleteList extends ManageLiveList {
	protected $f4 = "reinstate";
	protected $src = OBSOLETE_DIR;
	protected $can_be_empty = true;
	protected $title = "obsolete servers";
}

class ManageTrashList extends ManageObsoleteList {
	protected $f4 = "obsolete";
	protected $src = TRASH_DIR;
	protected $can_be_empty = true;
	protected $title = "trashed servers";
}

?>

