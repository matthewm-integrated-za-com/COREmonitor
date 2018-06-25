<?php
error_reporting(!E_ALL);
	use PHPOnCouch\CouchClient;//php couch DB library as found at https://github.com/dready92/PHP-on-Couch
	//Include libraries
	{
		include "libraries/CouchLink/vendor/autoload.php";//needed to load phponcouch
		require 'libraries/phpMailer/PHPMailerAutoload.php';
	}
	//declare important Vars
	{
		$time = explode(" ",microtime());
		$now = $time[1]+$time[0];
		$couchDsn = "http://127.0.0.1:5984";//coucgh db settings, if neede these must be manually changed as per documentation provided at https://github.com/dready92/PHP-on-Couch
		$signalsDB = "coremonitor_signals";
		$configDB = "coremonitor_config"; 
		$clientsDB = "coremonitor_clients";
		$usersDB = "coremonitor_users";
		$signalsClient = new CouchClient($couchDsn,$signalsDB);
		$configClient = new CouchClient($couchDsn,$configDB);
		$usersClient = new CouchClient($couchDsn,$usersDB);
		$clientsClient = new CouchClient($couchDsn,$clientsDB);
		$clickatellURL = "https://platform.clickatell.com/messages/http/send?apiKey=XGbb0jE3SD2rHwa4uh5OCQ%3D%3D";
		$return = new stdClass();
		$minTimeBuffer = 120 ; //Time in minutes to pull signals for, Improves speed performance of this script
		$minTime = $now - ($minTimeBuffer * 60);
	}
	//get action plans and response plans
	{
		$actionPlans = $configClient -> getDoc("actionPlans");
		$responsePlans = $configClient -> getDoc("responsePlans");
	}
	if(!is_null($_POST["token"]))
	{
		try
		{
			$user = $usersClient -> getDoc($_POST["token"]);
			retrieveData();
		}
		catch (Exception $e)
		{
			$return -> error = "ERROR: Invalid / No user token provided";
		}
	}
	else
	{
		error_reporting(!E_ALL);
		$return -> error = "ERROR: Invalid / No user token provided";
	}
	$return -> raw = $_POST;
	echo json_encode($return);
	//functions
	{
		function retrieveData()
		{
			global $return;
			if(!is_null($_POST["require"]))
			{
				if($_POST["require"] == "all")
				{
					getClients();
					getSignals();
					getUsers();
					getConfig();
				}
				elseif($_POST["require"] == "ping")
				{
					pingTest($_POST["address"]);
				}
				elseif($_POST["require"] == "clientUpdater")
				{
					global $clientsClient;
					try
					{
						$client = $clientsClient -> getDoc($_POST["clientID"]);
					}
					catch (Exception $e)
					{
						$return -> error = $e;
					}
					$return -> retrieved -> client = $client;
					getSignals();
					getUsers();
					getConfig();
				}
				else
				{
					$return -> error = "ERROR: Invalid Request";
				}
			}
			else
			{
				$return -> error = "ERROR: Invalid Request";
			}
		}
		function getClients()
		{
			global $return, $clientsClient;
			$allClients = $clientsClient -> getAllDocs();
			foreach($allClients -> rows as $currentRow)
			{
				$clientID = $currentRow -> id;
				$currentClient = $clientsClient -> getDoc($clientID);
				$return -> retrieved -> clients[$clientID] = $currentClient;
			}
		}
		function getSignals()
		{
			global $return, $signalsClient, $minTime;
			$allSignals = $signalsClient -> getAllDocs();
			foreach($allSignals -> rows as $currentRow)
			{
				$signalID = $currentRow -> id;
				if(floatval($signalID) >= $minTime)
				{
					$currentSignal = $signalsClient -> getDoc($signalID);
					$return -> retrieved -> signals[$signalID] = $currentSignal;
				}
			}
		}
		function getUsers()
		{
			global $return, $usersClient;
			$allUsers = $usersClient -> getAllDocs();
			foreach($allUsers -> rows as $currentRow)
			{
				$userID = $currentRow -> id;
				$currentUser = $usersClient -> getDoc($userID);
				$return -> retrieved -> users[$userID] = $currentUser;
			}
		}
		function getConfig()
		{
			global $return, $configClient;
			$actionPlans = $configClient -> getDoc("actionPlans");
			$responsePlans = $configClient -> getDoc("responsePlans");
			$return -> retrieved -> config -> actionPlans = $actionPlans -> plans;
			$return -> retrieved -> config -> responsePlans = $responsePlans -> plans;
		}
		function pingTest($address)
		{
			$return -> data = "HELLO";
		}
	}
	function store($print)
	{
	    fopen('print.txt', 'w');
	    file_put_contents('print.txt',print_r($print, true));
	}
?>