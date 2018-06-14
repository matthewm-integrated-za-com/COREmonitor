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
	}
	if(!is_null($_POST["token"]))
	{
		try
		{
			$user = $usersClient -> getDoc($_POST["token"]);
			updateData();
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
		function updateData()
		{
			global $return;
			if(!is_null($_POST["update"]))
			{
				if(!is_null($_POST["data"]))
				{
					if($_POST["update"] == "client")
					{
						if($_POST["what"] == "activations")
						{
							updateActivations();
						}
						elseif($_POST["what"] == "users")
						{
							updateClientUsers();
						}
						else
						{
							$return -> error = "ERROR: invalid Request";
						}
					}
					elseif($_POST["update"] == "user")
					{
						updateUser();
					}
					else
					{
						$return -> error = "ERROR: invalid Request";
					}
				}
				else
				{
					$return -> error = "ERROR: invalid Request";
				}
			}
			else
			{
				$return -> error = "ERROR: Invalid Request";
			}
		}
		function updateActivations()
		{
			global $return, $clientsClient, $configClient;
			try
			{
				$client = $clientsClient -> getDoc($_POST["data"]["clientID"]);
				$actionPlans = $configClient -> getDoc('actionPlans') -> plans;
				$clientActivations = $client -> activations;
				foreach($actionPlans as $currentActionPlan)
				{
					$actionPlan = $currentActionPlan -> name;
					if(!is_null($clientActivations -> $actionPlan))
					{
						$currentClientActivation_byActionPlan = $clientActivations -> $actionPlan;
						foreach($_POST["data"]["activations"] as $currentReceivedActivation)
						{
							$activationID = $currentReceivedActivation["activationID"];
							$currentActivation = $currentClientActivation_byActionPlan -> $activationID;
							if(!is_null($currentActivation))
							{
								$responseObject = (object) $currentReceivedActivation["responses"];
								//loop through responses to test if the activation should be closed
								{
									foreach($responseObject as $currentResponse)
									{
										if($currentResponse['content'] == "Activation Completed")
										{
											$client -> activations -> $actionPlan -> $activationID -> isActive = false;
											$return -> retrieved -> activationCompleted = $activationID;
										}
									}
								}
								//store Responses to database
								$client -> activations -> $actionPlan -> $activationID -> responses = $responseObject;
							}
						}
					}
				}
				$clientsClient -> storeDoc($client);
			}
			catch (Exception $e)
			{
				$return -> error = $e->getMessage();
			}
		}
		function updateUser()
		{
			global $usersClient, $return;
			$userData = $_POST["data"];
			$userID = $userData["userID"];
			$user = $usersClient -> getDoc($userID);
			if($userData["details"]["contact"]["email"]["respond"] == "true"){$userData["details"]["contact"]["email"]["respond"] = true;}else{$userData["details"]["contact"]["email"]["respond"] = false;}
			if($userData["details"]["contact"]["mobile"]["respond"] == "true"){$userData["details"]["contact"]["mobile"]["respond"] = true;}else{$userData["details"]["contact"]["mobile"]["respond"] = false;}
			$user -> details = $userData["details"];
			if($_POST["editPlatform"] == "true")
			{
				$return -> data[] = $userData["editPlatform"];
				if($userData["platform"]["allow"] == "true"){$userData["platform"]["allow"] = true;}else{$userData["platform"]["allow"] = false;}
				if($userData["platform"]["security"]["dashboard"]["enable"] == "true"){$userData["platform"]["security"]["dashboard"]["enable"] = true;}else{$userData["platform"]["security"]["dashboard"]["enable"] = false;}
				if($userData["platform"]["security"]["dashboard"]["activations"] == "true"){$userData["platform"]["security"]["dashboard"]["activations"] = true;}else{$userData["platform"]["security"]["dashboard"]["activations"] = false;}
				if($userData["platform"]["security"]["dashboard"]["onlineDevices"] == "true"){$userData["platform"]["security"]["dashboard"]["onlineDevices"] = true;}else{$userData["platform"]["security"]["dashboard"]["onlineDevices"] = false;}
				if($userData["platform"]["security"]["dashboard"]["openClients"] == "true"){$userData["platform"]["security"]["dashboard"]["openClients"] = true;}else{$userData["platform"]["security"]["dashboard"]["openClients"] = false;}
				if($userData["platform"]["security"]["monitoring"]["enable"] == "true"){$userData["platform"]["security"]["monitoring"]["enable"] = true;}else{$userData["platform"]["security"]["monitoring"]["enable"] = false;}
				if($userData["platform"]["security"]["monitoring"]["signals"] == "true"){$userData["platform"]["security"]["monitoring"]["signals"] = true;}else{$userData["platform"]["security"]["monitoring"]["signals"] = false;}
				if($userData["platform"]["security"]["monitoring"]["activations"] == "true"){$userData["platform"]["security"]["monitoring"]["activations"] = true;}else{$userData["platform"]["security"]["monitoring"]["activations"] = false;}
				if($userData["platform"]["security"]["users"]["enable"] == "true"){$userData["platform"]["security"]["users"]["enable"] = true;}else{$userData["platform"]["security"]["users"]["enable"] = false;}
				if($userData["platform"]["security"]["users"]["edit"] == "true"){$userData["platform"]["security"]["users"]["edit"] = true;}else{$userData["platform"]["security"]["users"]["edit"] = false;}
				if($userData["platform"]["security"]["users"]["editPlatform"] == "true"){$userData["platform"]["security"]["users"]["editPlatform"] = true;}else{$userData["platform"]["security"]["users"]["editPlatform"] = false;}
				if($userData["platform"]["security"]["clients"]["enable"] == "true"){$userData["platform"]["security"]["clients"]["enable"] = true;}else{$userData["platform"]["security"]["clients"]["enable"] = false;}
				if($userData["platform"]["security"]["clients"]["edit"] == "true"){$userData["platform"]["security"]["clients"]["edit"] = true;}else{$userData["platform"]["security"]["clients"]["edit"] = false;}
				if($userData["platform"]["security"]["clients"]["activations"]["enable"] == "true"){$userData["platform"]["security"]["clients"]["activations"]["enable"] = true;}else{$userData["platform"]["security"]["clients"]["activations"]["enable"] = false;}
				if($userData["platform"]["security"]["clients"]["activations"]["edit"] == "true"){$userData["platform"]["security"]["clients"]["activations"]["edit"] = true;}else{$userData["platform"]["security"]["clients"]["activations"]["edit"] = false;}
				if($userData["platform"]["security"]["clients"]["activations"]["addResponseNotes"] == "true"){$userData["platform"]["security"]["clients"]["activations"]["addResponseNotes"] = true;}else{$userData["platform"]["security"]["clients"]["activations"]["addResponseNotes"] = false;}
				if($userData["platform"]["security"]["clients"]["activations"]["editResponseNotes"] == "true"){$userData["platform"]["security"]["clients"]["activations"]["editResponseNotes"] = true;}else{$userData["platform"]["security"]["clients"]["activations"]["editResponseNotes"] = false;}
				if($userData["platform"]["security"]["clients"]["activations"]["removeResponseNotes"] == "true"){$userData["platform"]["security"]["clients"]["activations"]["removeResponseNotes"] = true;}else{$userData["platform"]["security"]["clients"]["activations"]["removeResponseNotes"] = false;}
				if($userData["platform"]["security"]["reports"]["enable"] == "true"){$userData["platform"]["security"]["reports"]["enable"] = true;}else{$userData["platform"]["security"]["reports"]["enable"] = false;}
				if($userData["platform"]["passwordChange"] == "true")
				{
					$userData["platform"]["password"] = supercrypt($userData["platform"]["password"]);
					$user -> platform -> password = $userData["platform"]["password"];
				}
				$user -> platform -> allow = $userData["platform"]["allow"];
				$user -> platform -> security = $userData["platform"]["security"];
			}
			$usersClient -> storeDoc($user);
			
store($user);
			
		}
		function supercrypt($string)
		{
			$forward = str_split($string);
			$inverse = strrev($string);
			$first = crypt($string,"!Nt3gr@t3dC0r3Gr0up20155102pu0rG3r0Cd3t@rg3tN!");
			$second = crypt($inverse,"!Nt3gr@t3dC0r3Gr0up20155102pu0rG3r0Cd3t@rg3tN!");
			$third = crypt($first,$second);
			$fourth = crypt($second,$first);
			$fifth = crypt($first,$inverse);
			$sixth = crypt($second,$string);
			return($first.$second.$third.$fourth.$fifth.$sixth);
		}
		function updateClientUsers()
		{
			global $return, $clientsClient;
			$clientData = $_POST["data"];
			$clientID = $clientData["clientID"];
			$client = $clientsClient -> getDoc($clientID);
			if(is_null($clientData["assignedUsers"]))
			{
				unset($client -> users);
			}
			else
			{
				$client -> users = $clientData["assignedUsers"];
			}
store($client);
			$clientsClient -> storeDoc($client);
		}
	}
	function store($print)
	{
	    fopen('print.txt', 'w');
	    file_put_contents('print.txt',print_r($print, true));
	}