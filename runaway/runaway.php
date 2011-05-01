<?php # runaway.php - Runaway Script
//require_once("PEAR.php");
//require_once("Net/Ident.php");

$oid_simple = array(
						  'One Minute Load' => '.1.3.6.1.4.1.2021.10.1.3.1',
							'Five Minute Load' => '.1.3.6.1.4.1.2021.10.1.3.2',
							'Fifteen Minute Load' => '.1.3.6.1.4.1.2021.10.1.3.3',
							"Uptime" => '.1.3.6.1.2.1.25.1.1.0'
							);

$oid_complex = array(
											'Busy CPU Percentage' => '.1.3.6.1.4.1.564283.1.1.1.0.1',
											'Process List Size' => '.1.3.6.1.4.1.564283.1.1.4.5',
											'Uptime' => '.1.3.6.1.4.1.564283.1.1.6.1'
											);

$netgrouplist = array();
$hostInfo = array();

$cpuOID = '.1.3.6.1.2.1.25.3.2.1.2';

netGroupList("linux-login-sys");

foreach($netgrouplist AS $host) {
	$result = false;
	$cpuCount = 0;
	$cpuInfo = @snmp2_real_walk($host, "public", $cpuOID, 200000, 5);
	
	foreach ($cpuInfo AS $key => $value) {
		if (strpos($value, 'hrDeviceProcessor') !== false)
			$cpuCount++;
	}
	
	/*
	$ident = new Net_Ident($host);
	printf('Query: %s', $ident->query());
	printf('Error: %s', $ident->identError());
	printf('User: %s<br />', $ident->getUser());
	*/
	$users = @snmp2_real_walk($host, "public", '.1.3.6.1.2.1.25.1.5', 20000, 5);
	$userCount = $users["HOST-RESOURCES-MIB::hrSystemNumUsers.0"];
	if (!empty($userCount)) {
		preg_match('/Gauge[0-9]*\: (.*)/', $userCount, $matches);
		$hostInfo[$host]['User Count'] = $matches[1];
	}
	
	$procs = @snmp2_real_walk($host, "public", '.1.3.6.1.2.1.25.1.6', 20000, 5);
	$procCount = $procs["HOST-RESOURCES-MIB::hrSystemProcesses.0"];
	if (!empty($procCount)) {
		preg_match('/Gauge[0-9]*\: (.*)/', $procCount, $matches);
		$hostInfo[$host]['Process Count'] = $matches[1];
	}
	
	foreach($oid_simple AS $name => $value) {
		$curResult = @snmp2_get($host, "public", $value, 20000, 5);
		
		if (!empty($curResult)) {
			if (strpos($curResult, "STRING") !== false) {
				preg_match('/STRING: (.*)/', $curResult, $matches);
			} else {
				if (preg_match('/Timeticks\: \([0-9]*\) ([0-9]* day[s]?, .*)/', $curResult, $matches) === 0)
					$matches[1] = $curResult;
			}
			
			$hostInfo[$host][$name] = $matches[1];
		}
	}
	
	$hostInfo[$host]['CPU Count'] = $cpuCount;
}

$keys = array_keys($hostInfo);
$headers = array_keys($hostInfo[$keys[0]]);

printf('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-us">
				<head>
					<script type="text/javascript" src="../includes/jquery.tablesorter/jquery-1.5.2.min.js"></script>
					<script type="text/javascript" src="../includes/jquery.tablesorter/jquery.tablesorter.min.js"></script>
					<script type="text/javascript" src="../includes/jquery.tablesorter/jquery.tablesorter.pager.js"></script>
					<script type="text/javascript" src="../includes/jquery.fancybox-1.3.4/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
					<script type="text/javascript" src="../includes/jquery.fancybox-1.3.4/fancybox/jquery.easing-1.4.pack.js"></script>
					<link rel="stylesheet" href="../includes/jquery.fancybox-1.3.4/fancybox/jquery.fancybox-1.3.4.css" type="text/css" />
					<link rel="stylesheet" href="../includes/jquery.tablesorter/themes/blue/style.css" type="text/css" />
					<style type="text/css">
						table.tablesorter tbody tr.high_load td {
							background-color: red !important;
						}
					</style>
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
									6: { sorter: "uptime" }
								},
								widgets: ["zebra"],
								sortList: [[3,1]] 
							});
							
							$(".inline").fancybox({
								"hideOnContentClick": true
							});
						});
					</script>
					<title>Runaway Check</title>
				</head>
				<body>
			 ');

printf('<table class="tablesorter"><thead>' . "\n" . '<tr><th>Host Name</th>' . "\n");

foreach($headers AS $header) {
	printf("<th>%s</th>\n", $header);
}

printf("</tr>\n</thead>\n<tbody>\n");

foreach($hostInfo AS $host => $data) {
	$loaded = false;
	
	if ($data["CPU Count"] < max($data["One Minute Load"], $data["Five Minute Load"], $data["Fifteen Minute Load"]))
		$loaded = true;
	
	printf('<tr %s id="%s"><td>%s (<a class="inline" href="runaway_detail.php?host=%s">Detail</a>)</td>' . "\n", ($loaded ? 'class="high_load"' : NULL), $host, $host, $host);
	foreach ($data AS $datum) {
		printf("<td>%s</td>\n", $datum);
	}
	printf("</tr>\n");
}
printf("</tbody>\n</table>\n</body></html>");

function netGroupList($search) {
	global $netgrouplist;
	
	$ldapconfig['host'] = 'ldap.cat.pdx.edu';
	$ldapconfig['port'] = 389;
	$ldapconfig['basedn'] = 'ou=Netgroup,dc=catnip';
	
	$ds = @ldap_connect($ldapconfig['host'], $ldapconfig['port']);
	
	$r = @ldap_search( $ds, $ldapconfig['basedn'], "cn=$search");
	
	if ($r) {
			$result = @ldap_get_entries( $ds, $r);
			if ($result) {
				if (is_array($result[0])) {
					if (array_key_exists("membernisnetgroup", $result[0])) {
						$newR = $result[0]["membernisnetgroup"];
						foreach ($newR AS $key => $entry) {
							if (is_numeric($key)) {
								netGroupList($entry);
							}
						}
					} elseif (array_key_exists("nisnetgrouptriple", $result[0])) {
						$newR = $result[0]["nisnetgrouptriple"];
						foreach ($newR AS $key => $entry) {
							if (is_numeric($key) && preg_match("/\((.*),\-,\)/", $entry, $matches) !== 0) {
								$netgrouplist[] = $matches[1];
							}
						}
					}
					
				}
			} else {
				return false;
			}
	} else {
			return false;
	}
}

?>