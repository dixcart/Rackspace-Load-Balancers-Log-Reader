<?php
/**
 * Rackspace Cloud Load Balancer Log Parser
 * Parses a Load Balancer log file and runs the strings through filters to find what you're looking for.
 * @author Eric Lamb (original Apache version)
 * @author Richard Benson (modified to suit Rackspace Load Balancer log format)
 *
 */
class lb_log_parser
{
	/**
	 * The path to the log file
	 * @var string
	 */
	public $file = FALSE;
 
	/**
	 * What filters to apply. Should be in the format of array('KEY_TO_SEARCH' => array('regex' => 'YOUR_REGEX'))
	 * @var array
	 */
	public $filters = FALSE;
 
	/**
	 * Duh.
	 * @param string $file
	 * @return void
	 */
	public function __construct($file)
	{
		if(!is_readable($file))
		{
			return 	FALSE;
		}
 
		$this->file = $file;
	}
 
	/**
	 * Executes the supplied filter to the string
	 * @param $filer
	 * @param $status
	 * @return string
	 */
	private function applyFilters($str)
	{
		if(!$this->filters || !is_array($this->filters))
		{
			return $str;
		}
 
		foreach($this->filters AS $area => $filter)
		{
			if(preg_match($filter['regex'], $str[$area], $matches, PREG_OFFSET_CAPTURE))
			{
				return $str;
			}
		}
	}
 
	/**
	 * Returns an array of all the filtered lines 
	 * @param $limit
	 * @return array
	 */
	public function getData($limit = FALSE)
	{
		$handle = fopen($this->file, 'rb');
		if ($handle) {
			$count = 1;
			$lines = array();
		    while (!feof($handle)) {
		        $buffer = fgets($handle);
		        $data = $this->applyFilters($this->format_line($buffer));
		        if($data)
		        {
		        	$lines[] = $data;
		        }
 
		        if($limit && $count == $limit)
		        {
		        	break;
		        }
		        $count++;
		    }
		    fclose($handle);
		    return $lines;
                }
	}
 
	/**
	 * Regex to parse the log file line
	 * @param string $line
	 * @return array
	 */
	function format_log_line($line)
	{
		preg_match("/^(\S+) (\S+) (\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/", $line, $matches); // pattern to format the line
		return $matches;
	}
 
	/**
	 * Takes the format_log_line array and makes it usable to us stupid humans
	 * @param $line
	 * @return array
	 */
	function format_line($line)
	{
		$logs = $this->format_log_line($line); // format the line
 
		if (isset($logs[0])) // check that it formated OK
		{
			$formated_log = array(); // make an array to store the lin info in
			$formated_log['balancerid'] = $logs[1];
                        $formated_log['host'] = $logs[2];
                        $formated_log['ip'] = $logs[3];
			$formated_log['identity'] = $logs[4];
			$formated_log['user'] = $logs[5];
			$formated_log['date'] = $logs[6];
			$formated_log['time'] = $logs[7];
			$formated_log['timezone'] = $logs[8];
			$formated_log['method'] = $logs[9];
			$formated_log['path'] = $logs[10];
			$formated_log['protocal'] = $logs[11];
			$formated_log['status'] = $logs[12];
			$formated_log['bytes'] = $logs[13];
			$formated_log['referer'] = $logs[14];
			$formated_log['agent'] = $logs[15];
			return $formated_log; // return the array of info
		}
		else
		{
			$this->badRows++; // if the row is not in the right format add it to the bad rows
			return false;
		}
	}
}
?>