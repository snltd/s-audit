<?php

//============================================================================
//
// filesystem_classes.php
// ----------------------
//
// A class to read and write flat files.
//
// Part of s-audit. (c) 2011 SearchNet Ltd
//  see http://snltd.co.uk/s-audit for licensing and documentation
//
//============================================================================

class Filesystem {

	static function get_files($base, $type = false, $pattern = false) {

		// Returns an array of files in the directory given by the first
		// argument used to call it. If the second argument is "d" then it
		// returns a list only of directories; if it is "a" then it returns
		// files and directories. Hidden files/directories are not returned.
		// Needed by menu generator

		if (!is_dir($base))
			return false;

		$dh = opendir($base);

		$file_list = $dir_list = array();

		while ($candidate = readdir($dh)) {

			// If we're using a pattern, discard things which don't match it

			if ($pattern) {

				if (!preg_match("/$pattern/", basename($candidate)))
					continue;

			}
			// drop all the hidden files 
	
			if (!preg_match("/^\./", $candidate)) {
	
				$candidate = "${base}/${candidate}";
	
				if (!file_exists($candidate))
					continue;
		
				if (is_dir($candidate))
					$dir_list[] = $candidate;
				else
					$file_list[] = $candidate;
						
			}
	
		}

		closedir($dh);

		sort($dir_list);
		sort($file_list);

		if ($type == "d")
			$ret_arr = $dir_list;
		elseif($type == "f") 
			$ret_arr = $file_list;
		else
			$ret_arr = array_merge($dir_list, $file_list);

		return $ret_arr;
	}

	public function all_fnames($file) {
		
		// Returns all the information about a link 

		$links["file"] = $file;
		$links["url"] = str_replace(ROOT, ROOT_URL, $file);
		$links["link"] = basename($file);

		return $links;
	}

	public function get_lines($file, $string, $count = -1) {

		// return an array of the first $count lines in a given file
		// containing the given string

		if (!file_exists($file))
			return false;

		$ret_arr = array();
		$file_pointer = fopen("$file", "r");
		$c = 0;

		for ($i = 0; !feof($file_pointer); $i++) {
			$line = fgets($file_pointer, 1024);

			if (preg_match("/$string/", $line)) {
				$ret_arr[] = $line;
				$c++;

				if ($c == $count)
					break;
			}

		}

		fclose($file_pointer);
		return ($c > 0) ? $ret_arr : false;
	}

	public function getline($file, $string) {

		// return the first line in a given file containing the given string 

		if (!file_exists($file))
			return false;

		$file_pointer = fopen("$file", "r");

		for ($i = 0; !feof($file_pointer); $i++) {
			$line = fgets($file_pointer, 1024);

			if (preg_match("/$string/", $line))
				break;

		}

		fclose($file_pointer);
		return (isset($line)) ? $line : false;
	}

	public function get_dirs($d)
	{
		// return an array of all the zones in the site under $dir to which
		// the user is allowed access

		$ret_arr = array();

		// get a list of zones from the filesystem. We could cache this if
		// we were smart

		$zones = $this->get_files($d, "d");

		// If this site has zones defined, look at each zone, see if we have
		// the privs to see it, and if so, add it to the string we're
		// returning


		if ($zones) {

			foreach ($zones as $zone)
				$ret_arr[] = $zone;
		}

		return $ret_arr;
	}

	public function move_files($files, $dest)
	{
		// Move a bunch of files to a directory. Syntax just like mv(1).
		// First argument can be an array

		$ret = true;
		
		if (!is_array($files))
			$files = array($files);

		if (!is_dir($dest)) {
			echo "\n\n<p class=\"error\">$dest is not a directory.</p>";
			$ret = false;
		}
		elseif (!is_writeable($dest)) {
			echo "\n\n<p class=\"error\">$dest is not writeable.</p>";
			$ret = false;
		}
		else {

			foreach($files as $file) {

				if (!rename($file, $dest . "/" . basename($file)))
					$ret = false;

			}

		}

		return $ret;
	}

	public function rm_empty_dir($dir)
	{
		// Remove a directory if it's empty

		$files = $this->get_files($dir);

		if ($files == 0)
			rmdir($dir);
	}

}

?>
