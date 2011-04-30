<?php # runaway_details.php - Runaway Script

if (!isset($_GET["host"])) {
	exit();
} else {
	$host = $_GET["host"];
}

$result = false;

$process_list = '.1.3.6.1.2.1.25.4.2.1.2';
$process_time = '.1.3.6.1.2.1.25.5.1.1.1';
$process_mem = '.1.3.6.1.2.1.25.5.1.1.2';
$process_param = '.1.3.6.1.2.1.25.4.2.1.5';

$curResultPL = @snmp2_real_walk($host, "public", $process_list, 20000, 5);
$curResultPT = @snmp2_real_walk($host, "public", $process_time, 20000, 5);
$curResultPM = @snmp2_real_walk($host, "public", $process_mem, 20000, 5);
$curResultPA = @snmp2_real_walk($host, "public", $process_param, 20000, 5);
$process = array();

if (!empty($curResultPL)) {
	
	foreach ($curResultPL AS $key => $processInfo) {
		preg_match('/HOST\-RESOURCES\-MIB\:\:hrSWRunName\.(.*)/', $key, $name);
		preg_match('/STRING: (.*)/', $processInfo, $matches);
		
		$process[$name[1]]['name'] = $matches[1];
	}
		
	$result = true;
}

if (!empty($curResultPT)) {
	foreach ($curResultPT AS $key => $processInfo) {
		preg_match('/HOST\-RESOURCES\-MIB\:\:hrSWRunPerfCPU\.(.*)/', $key, $name);
		preg_match('/INTEGER: (.*)/', $processInfo, $matches);
		
		$process[$name[1]]['time'] = $matches[1];
	}
	
	$result = true;
}

if (!empty($curResultPM)) {
	
	foreach ($curResultPM AS $key => $processInfo) {
		preg_match('/HOST\-RESOURCES\-MIB\:\:hrSWRunPerfMem\.(.*)/', $key, $name);
		preg_match('/INTEGER: (.*)/', $processInfo, $matches);
		
		$process[$name[1]]['mem'] = $matches[1];
	}
		
	$result = true;
}

if (!empty($curResultPA)) {
	
	foreach ($curResultPA AS $key => $processInfo) {
		preg_match('/HOST\-RESOURCES\-MIB\:\:hrSWRunParameters\.(.*)/', $key, $name);
		
		if (preg_match('/STRING: (.*)/', $processInfo, $matches) === 0)
			$matches[1] = NULL;
		
		$process[$name[1]]['param'] = $matches[1];
	}
		
	$result = true;
}

printf('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-us">
			<head>
				<script type="text/javascript" src="../includes/jquery.tablesorter/jquery-1.5.2.min.js"></script>
				<script type="text/javascript" src="../includes/jquery.tablesorter/jquery.tablesorter.min.js"></script>
				<script type="text/javascript" src="../includes/jquery.tablesorter/jquery.tablesorter.pager.js"></script>
				<link rel="stylesheet" href="../includes/jquery.tablesorter/themes/blue/style.css" type="text/css" />
				<script type="text/javascript">
					$(document).ready(function() {
						// add parser through the tablesorter addParser method
						$.tablesorter.addParser({
							// set a unique id
							id: "uptime",
							is: function(s) {
								// return false so this parser is not auto detected
								return false;
							},
							format: function(s) {
								// format your data for normalization
								var dateSplit = s.split(" ");
								return dateSplit[0];
							},
							// set type, either numeric or text
							type: "numeric"
						});

						$(".tablesorter").tablesorter({
							headers: {
									2: { sorter: "uptime" }
								},
							widgets: ["zebra"],
							sortList: [[3,1]] 
						});
					});
				</script>
			</head>
			<body>
		 ');

if ($result) {
	printf('<table class="tablesorter"><thead><tr><th>PID</th><th>Process</th><th>Memory</th><th>CPU Time</th></tr></thead><tbody>');
	
	
	$processList = array();
	foreach($process AS $num => $data) {
		$processList[]  = array('num' => $num, 'time' => (isset($data['time']) ? $data['time'] : NULL),
														'mem' => (isset($data['mem']) ? $data['mem'] : NULL),
														'name' => str_replace('"', '', (isset($data['name']) ? $data['name'] : NULL) . " " . (isset($data['param']) ? $data['param'] : NULL))
													);
	}
	
	usort($processList, "cmp");
	$count = 0;
	
	foreach ($processList AS $data) {
		$count++;
		if ($count < 20)
			printf("<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>", $data['num'], $data['name'], $data['mem'], $data['time']);
	}
	printf("</tbody></table>");
}

printf("</body></html");

function cmp($a, $b) {
	if ($a["time"] == $b["time"]) {
					return 0;
	}
	return ($a["time"] > $b["time"]) ? -1 : 1;
}
?>