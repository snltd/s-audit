<?php

//============================================================================
//
// netbackup_classes.php
// --------------------
// 
// Classes which collect, interpret and display NetBackup audit data.
// Requires the general_classes.php file
//
// R Fisher 2009
//
// v1.0
// Please record changes below.
//
//============================================================================

//----------------------------------------------------------------------------
// DATA COLLECTION

class GetNbData {

	private $raw;
	private $processed;

	public function __construct($datafile, $pid)
	{
		$this->raw = file($datafile);
		
		$this->processed = (sizeof($this->raw) > 0) 
			? $this->collect_data($this->raw, $pid, $datafile) 
			: false;
	}

	public function get_array()
	{
		return $this->processed;
	}

	protected function collect_data($data, $pid, $datafile)
	{
		$job_codes = array();

		// Make an array of all the data for jobs with the requested parent
		// ID (pid). This will get either:
		//   - all the parent and single jobs
		//   - all the jobs belonging to one unique parent

		// Loop through all the jobs, creating a new array job_codes[id],
		// where each element is an array of exit codes for the children of
		// job "id".  We don't care what a non-zero code is, just that it's
		// non-zero.

		foreach ($data as $rec) {
			$job = explode(",", $rec);
			$lpid = $job[7];
			$lid = $job[0];

			//	Changed how we identify composite job children 
			//	if ($lpid > 0);

			if ($lpid != $lid) {
				$c = ($job[1] == 0) ? 0 : 1;
				$job_codes[$lpid][] = $c;

				// We also want to work out the real length of composite
				// jobs.  The NetBackup data on these seems inconsistent.

				$ca =& $comp[$lpid]; // shorthand

				if (!isset($ca))
					$ca = array("finish" => 0, "size" => 0, "files" => 0);

				if (!isset($ca["start"]) || $ca["start"] > $job[5])
					$ca["start"] = $job[5];

				if ($ca["finish"] < $job[6])
					$ca["finish"] = $job[6];

				// And keep a running total of the size of this job

				$ca["size"] = $ca["size"] + $job[8];
				$ca["files"] = $ca["files"] + $job[9];
			}

		}

		// Now go through the data again, looking for jobs we're interested in
		// and putting their data into a useable form.

		foreach ($data as $rec) {
			unset($score);
			$job = explode(",", $rec);

			if (sizeof($job) < 11) // throw away bad data
				continue;

			// Examine the parent ID, and if it's equal to the $pid we were
			// called with, put the job info into an array. If it's not, put
			// the return code into an array called other[pid]. We'll use
			// this to calculate the success of composite jobs
			

			// Setting the IDs to INTs handily discards any trailing
			// whitespace which could (and does, if given the chance)
			// confuse things

			$myid = (int) $job[0];
			$mypid = (int) $job[7];

			// Make an associative array of the data.

			// This assumption made for NBU5 is no longer true for NBU6, so
			// logic changed.
			//if ($mypid == $pid) {

			if (($pid == 0 && $myid == $mypid) || $mypid == $pid) {

				// is this a composite job? If it is, then its ID will be in
				// the job_codes array

				if (isset($job_codes[$myid])) {

					// If we're in history mode, we don't know which
					// datafile this job will be in, so we'll have to work
					// it out. 

					$dfile = ($datafile == false) ? "bpdbjobs-"
					. date("Ymd", $job[5]) . ".csv" : basename($datafile);

					// We can also update the start and finish times with
					// the most extreme starts and finishes of the children 

					$job[5] = $comp[$myid]["start"];
					$job[6] = $comp[$myid]["finish"];
					$job[8] = $comp[$myid]["size"];
					$job[9] = $comp[$myid]["files"];

					// We also work out the "score" of the job, for
					// colouring the left-hand column. Do this by summing
					// all the return codes and dividing by the number of
					// elements. A score of 0 is perfect, a score of 1 is a
					// complete failure.

					$score = (array_sum($job_codes[$myid]) /
					sizeof($job_codes[$myid]));

					$myid = "<a href=\"$_SERVER[PHP_SELF]?c=breakdown"
					. "&amp;datafile=${dfile}&amp;pid=$myid\">$myid</a>";
					
				}

				$err = ($job[1] == 0) 
					? "0"
					: "<a href=\"http://tcc.technion.ac.il/" 
					. "backup/VNBCodes.html#Status%20Code:%20${job[1]}"
					. "\">$job[1]</a>";

				// For simple (non-composite) jobs, score is 0 for pass, 1
				// for fail.

				if (!isset($score)) 
					$score = ($job[1] == 0) ? 0 : 1;

				// Now we have the average score, we can work out what
				// colour to make the table cell. 

				switch($score) {

					case 0: 
						$class = "solidgreen";
						break;

					case 1:
						$class = "solidred";
						break;

					default:
						$class = "solidamber";
				}

				if ($job[6] > 0) {
					$finish = date(DATE_STR, $job[6]);
					$duration = units::h_m_s($job[6] - $job[5]);
				}
				else
					$finish = $duration = "-";

				$host_colm = array(
					"value",
					"value" => "<a href=\""
								. "$_SERVER[PHP_SELF]?host=$job[4]"
								. "&amp;c=history&amp;host="
								. "$job[4]\">$job[4]</a>",
					"bg" => $class
				);

				// Convert numeric job types to meaningful strings

				if (isset($job[11])) {

					switch ($job[11]) {
	
						case 0:
							$job[11]="Backup";
							break;

						case 6:
							$job[11]="Catalog Backup";
							break;

						case 17:
							$job[11]="Image Cleanup";
							break;
					}    

				}

				$data_array = array(
					"hostname" => $host_colm,
					"id" => $myid,
					"policy" => $job[2],
					"schedule" => $job[3],
					"start" => date(DATE_STR, $job[5]),
					"finish" => $finish,
					"duration" => $duration,
					"files" => $job[9],
					"size" => units::from_b($job[8]),
					"media" => $job[10],
					"exit" => $err);

				if (isset($job[11]))
					$data_array["type"] = $job[11];

				$ret_arr[] = $data_array;
			}

		}

		return $ret_arr;
	}

}

class GetNbDataHistory extends GetNbData {

	private $data;
	private $processed;

	public function __construct($host)
	{
		// This function goes through every log file in NB_DIR and pulls
		// out records for the given $host. I do an external exec() of awk
		// for this, because it's so much quicker and easier than writing
		// PHP to  crunch through every single file. Why re-invent the
		// wheel?  exec()  puts each line of output into an array, which we
		// then pass back to the calling function.

		exec("awk -F, ' ($5 == \"$host\") { print } ' " . NB_DIR .
		"/bpdbjobs-*.csv | sort -nr -k5 -t,", $ret_arr, $ret_code);

		$this->raw = $ret_arr;

        $this->processed = (sizeof($this->raw) > 0)
			? $this->collect_data($this->raw, 0, false)
			: false;

	}

	public function get_array()
	{
		return $this->processed;
	}
}

//----------------------------------------------------------------------------
// DATA DISPLAY

class nbReportGrid extends HostGrid {

	public $show_count = false;
		// Server count doesn't make sense in this context

	private $data;
	protected $fields;

	public function __construct($data)
	{
		$this->data = $data;
		$this->fields = $this->get_fields($data[0]);
	}

	public function get_fields($data)
	{
		foreach(array_keys($data) as $field)
			$arr[] = $field;

		return $arr;
	}

	public function grid_body()
	{
		$ret_str = "";

		foreach($this->data as $job) {
			$ret_str .= "\n  <tr class=\"server\">";

			foreach($job as $key=>$val) {

				$ret_str .= (is_array($val))
					? new Cell($val["value"], $val["bg"])
					: new Cell($val);
			}

			$ret_str .="</tr>";
		}

		return $ret_str;
					
	}

	public function display_report($heading)
    {
		echo  "\n<h3>$heading</h3>";
		echo $this->show_grid();
		echo "<p>Job IDs in blue are composite jobs.  Their
		statistics are calculated by combining all their child jobs. A more
		detailed breakdown may be obtained by clicking on the ID.</p>
		<p>Clicking on a hostname will display backup history for that
		host.</p>";
	}

}

class nbCalendarGrid extends HostGrid {

	protected $fields = array( "Monday", "Tuesday", "Wednesday", "Thursday",
	"Friday", "Saturday", "Sunday");

	private $datafiles;
	private $viewing;

	public function __construct($viewing = false) 
	{
    	$this->datafiles = filesystem::get_files(NB_DIR, "f");
		$this->viewing = $viewing;

	}

	public function show_grid($width = "60%")
	{
		if (sizeof($this->datafiles) < 2)
			return "<p class=\"error\">No history.</p>";

		return $this->grid_head($width) . $this->grid_body() .
		$this->grid_foot();
	}

	public function grid_body()
	{
		// Get date of the last datafile

	    sort ($this->datafiles); 
		$oldest = $this->datafiles[0];

		$tstamp = $this->filename_to_mktime($oldest);

		// Work out times based on mid-day, otherwise BST can screw things
		// up. 

		$now = mktime(12, 0, 0);

		// How many days of history we should have, based on the oldest file

		$elapsed = ceil (($now - $tstamp) / 86400);

		// How many days into the grid do we have to start? Cell 1 is a
		// Monday. 

		$first_day = date("l", $now);

		$t_arr = array_flip($this->fields);

		$ago = $t_arr[$first_day];

		// For each day, work out what the file should be called, and see if
		// we have it

		$ret_str = "";

		for ($w = 0; $w < $elapsed / 7; $w++) {

			$ret_str .= "\n<tr>";

			// We have to do chunks of seven (a week)

			$start = $now - $ago * 86400;

			for ($d = 0; $d < 7; $d++) {
				$try_time = $start + (86400 * $d);

				$try_file = NB_DIR . "/bpdbjobs-" . date("Ymd", $try_time)
				. ".csv";

				$prt = date("D d M y", $this->filename_to_mktime($try_file));

				if ($try_time > $now || $try_time <
				$this->filename_to_mktime($oldest))
					$cell = new Cell();
				elseif (file_exists($try_file) && filesize($try_file) > 0) {

					if ($this->viewing == $try_file)
						$cell = new Cell($prt, "boxorange");
					else
						$cell = new Cell("<a href=\"$_SERVER[PHP_SELF]?"
						.  "datafile=" . basename($try_file)
						.  "\">$prt</a>");
				}
				else
					$cell = new Cell($prt, "solidred");

				$ret_str .= $cell;
			}

			$ago = $ago + 7;
			$ret_str .= "</tr>";
		}

		return $ret_str;

	}

	public function filename_to_mktime($filename)
	{
		// return a PHP mktime() style string from a bpdbjobs- filename

		$filename = basename($filename);

		return mktime(0, 0, 1, substr($filename, 13, 2), substr($filename,
		15, 2), substr($filename, 9, 4));

	}


}

?>
