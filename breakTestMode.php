<?php
	use PHPOnCouch\CouchClient;//php couch DB library as found at https://github.com/dready92/PHP-on-Couch
	//Include libraries
	{
		output("Including libraries");
		include "libraries/CouchLink/vendor/autoload.php";//needed to load phponcouch
		require 'libraries/phpMailer/PHPMailerAutoload.php';
	}
	//declare important Vars
	{
		output("Declaring important variables");
		$couchDsn = "http://127.0.0.1:5984";//coucgh db settings, if neede these must be manually changed as per documentation provided at https://github.com/dready92/PHP-on-Couch
		$signalsDB = "coremonitor_signals";
		$configDB = "coremonitor_config"; 
		$clientsDB = "coremonitor_clients";
		$usersDB = "coremonitor_users";
		$signalsClient = new CouchClient($couchDsn,$signalsDB);
		$configClient = new CouchClient($couchDsn,$configDB);
		$usersClient = new CouchClient($couchDsn,$usersDB);
		$clientsClient = new CouchClient($couchDsn,$clientsDB);
		$clickatellURL = "https://platform.clickatell.com/messages/http/send?apiKey=qyM2kcqjTjGvHL8MwnXI1Q%3D%3D";
	}
	$allClients = $clientsClient -> getAllDocs() -> rows;
	foreach($allClients as $currentRow)
	{
		$time = explode(" ",microtime());
		$now = $time[1]+$time[0];
		$clientID = $currentRow -> id;
		output("fetching Client $clientID");
		$client = $clientsClient -> getDoc($clientID);
		output("Testing if client has activations");
		if($client -> testModeActive && ($client -> testModeExpiry <= $now))
		{
			output("removing Test MOde");
			$testMode = "Test Mode";
			foreach($client -> activations -> $testMode as $currentTest)
			{
				$activationID = $currentTest -> activationID;
				var_dump($activationID);
				$currentTest -> isActive = false;
				$client -> activations -> $testMode -> $activationID = $currentTest;
				$client -> testModeActive = false;
				unset($client -> testExpiary);
			}
			$clientsClient -> storeDoc($client);
		}
	}
	//functions
	{
		function output($string)
		{
			$time = explode(" ",microtime());
			$now = $time[1]+$time[0];
			echo "[$now MEM:" . (memory_get_usage()/1024/1024) . "mb] -> $string<br>";
			ob_flush(); # http://php.net/ob_flush
			flush(); # http://php.net/flush
		}
		function store($print)
		{
		    fopen('print.txt', 'w');
		    file_put_contents('print.txt',print_r($print, true));
		}
	}
?>