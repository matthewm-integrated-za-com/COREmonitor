<?php
	$signalID = $_POST["signalID"];
//$signalID = $_GET["signalID"];
	//error_reporting(E_ALL);
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
	}
	//get action plans and response plans
	{
		$actionPlans = $configClient -> getDoc("actionPlans");
		$responsePlans = $configClient -> getDoc("responsePlans");
	}
	//Open Signals
	{
		output("collectiong signals from signalsDB");
		$allSignals = $signalsClient -> getAllDocs();
		output("Collecting data for signal $signalID");
		//get signal data
		$currentSignal = $signalsClient -> getDoc($signalID);
		//retrieve Client from DB
		$clientID = $currentSignal -> signal -> clientID;
		output("Retrieving Client Activations for $clientID");
		try
		{
			$client = $clientsClient -> getDoc($clientID);
		}
		catch (Exception $e)
		{
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			$newClient = new stdClass();
			$newClient -> _id = $clientID;
			$newClient -> clientName = "***GHOST ALARM***";
			$clientsClient -> storeDoc($newClient);
			$client = $clientsClient -> getDoc($clientID);
		}
		//error_reporting(!E_NOTICE);// EXPECTED ERROR: Notice: Undefined property: stdClass::$testModeActive
		if(!$client -> testModeActive)
		{
			//error_reporting(E_ALL);
			if((!$currentSignal -> convertedToActivation) && ($currentSignal -> signal -> createActivation))
			{
				output("Converting signal $signalID to an Activation");
				//test if client has an open activation in the current action plan
				{
					$actionPlan = $currentSignal -> signal -> assignedActionPlan;
					if(!is_null($actionPlan))
					{
						//error_reporting(!E_NOTICE);//EXPECTED ERROR: Notice: Undefined property: stdClass
						$relevantActivations = $client -> activations -> $actionPlan;
						//error_reporting(E_ALL);
						if(!is_null($relevantActivations))
						{
							//loop through $relevantActivations
							foreach($relevantActivations as $currentActivation)
							{
								//test if activation is active
								if($currentActivation -> isActive)
								{
									$signalAddedToActivation = true;
									$assignedSignals = $currentActivation -> assignedSignals;
									$activationID = $currentActivation -> activationID;
									$client -> activations -> $actionPlan -> $activationID -> assignedSignals[] = $signalID;
									//store Client
									{
										output("Updating Client");
										if($actionPlan == "Closing")
										{
											$client -> currentStatus = "closed";
										}
										elseif($actionPlan == "Opening")
										{
											$client -> currentStatus = "open";
											$users = $client -> users;
											foreach($users as $currentUser)
											{
												if($currentUser -> userNumber == $currentSignal -> signal -> zone_user)
												{
													$userID = $currentUser -> userID;
													$client -> openBy = $userID;
												}
											}
										}
										$clientsClient -> storeDoc($client);
									}
									//Send Notifications
									{
										output("Sending Notifications for signal");
										//configure content
										{
											//$content = urlencode($actionPlans -> plans -> $actionPlan -> symbol);
											$content = " ";
											$content .= $actionPlans -> plans -> $actionPlan -> message;
											$content = str_replace("\$clientName", $client -> clientName,$content);
											$content = str_replace("\$time",date("d M Y H:i:s",$currentSignal -> timestamp),$content);
											$content = str_replace("\$eventName",$currentSignal -> signal -> eventName,$content);
											if($actionPlans -> plans -> $actionPlan -> signalType == "zone info")
											{
												$content = str_replace("\$zone_user","Zone: " . $currentSignal -> signal -> zone_user,$content);
											}
											elseif($actionPlans -> plans -> $actionPlan -> signalType == "user info")
											{
												$users = $client -> users;
												foreach($users as $clientUser)
												{
													if($clientUser -> userNumber == $currentSignal -> signal -> zone_user)
													{
														$currentUser = $usersClient -> getDoc($clientUser -> userID);
														$userName = $currentUser -> details -> name;
														$userMatchFound = true;
													}
												}
												if($userMatchFound)
												{
													$content = str_replace("\$zone_user","$userName",$content);
												}
												else
												{
													$content = str_replace("\$zone_user","User " . $currentSignal -> signal -> zone_user,$content);
												}
											}
										}
										//get client users
										{
											$users = $client -> users;
											//get user type
											foreach($users as $clientUser)
											{
												$userID = $clientUser -> userID;
												$currentUser = $usersClient -> getDoc($userID);
												$responsePlan = $actionPlans -> plans -> $actionPlan -> responsePlan;
												$responseUserTypes = $responsePlans -> plans -> $responsePlan -> userTypes;
												//loop through response user types
												foreach($responseUserTypes as $currentResponseUserType)
												{
													if($currentUser -> details -> userType == $currentResponseUserType)
													{
														if($currentUser -> details -> contact -> mobile -> respond)
														{
															/*
															output("Sending SMS");
															$smsContent = str_replace(" ","%20",$content);
															$smsContent = urlencode($actionPlans -> plans -> $actionPlan -> symbol) . $smsContent;
															$messageRequest = "$clickatellURL&to=" . $currentUser -> details -> contact -> mobile -> detail . "&content=$smsContent";
															curlGet($messageRequest);
															*/
														}
														if($currentUser -> details -> contact -> email -> respond)
														{
															output("Sending email");
															$to = array($currentUser -> details -> contact -> email -> detail);
															sendEmail($to,null,null,null,$content);
														}
													}
												}
											}
										}
									}
								}
							}
							//create New Activation if none was found
							//error_reporting(!E_NOTICE);//EXPECTED ERROR: Notice: Undefined variable: signalAddedToActivation
							if(!$signalAddedToActivation)
							{
								$now = $time[1]+$time[0];
								//error_reporting(!E_WARNING);//EXPECTED ERROR: Warning: Creating default object from empty value in /var/www/html/createActivations.php on line 70
								$client -> activations -> $actionPlan -> $now -> activationID = $now;
								$client -> activations -> $actionPlan -> $now -> isActive = true;
								$client -> activations -> $actionPlan -> $now -> assignedSignals[] = $signalID;
								//error_reporting(E_ALL);
								//store Client
								{
									output("Updating Client");
									if($actionPlan == "Closing")
									{
										$client -> currentStatus = "closed";
									}
									elseif($actionPlan == "Opening")
									{
										$client -> currentStatus = "open";
										$users = $client -> users;
										foreach($users as $currentUser)
										{
											if($currentUser -> userNumber == $currentSignal -> signal -> zone_user)
											{
												$userID = $currentUser -> userID;
												$client -> openBy = $userID;
											}
										}
									}
									$clientsClient -> storeDoc($client);
								}
								//Send Notifications
								{
									output("Sending Notifications for signal");
									//configure content
									{
										//$content = urlencode($actionPlans -> plans -> $actionPlan -> symbol);
										$content = " ";
										$content .= $actionPlans -> plans -> $actionPlan -> message;
										$content = str_replace("\$clientName", $client -> clientName,$content);
										$content = str_replace("\$time",date("d M Y H:i:s",$currentSignal -> timestamp),$content);
										$content = str_replace("\$eventName",$currentSignal -> signal -> eventName,$content);
										if($actionPlans -> plans -> $actionPlan -> signalType == "zone info")
										{
											$content = str_replace("\$zone_user","Zone: " . $currentSignal -> signal -> zone_user,$content);
										}
										elseif($actionPlans -> plans -> $actionPlan -> signalType == "user info")
										{
											$users = $client -> users;
											foreach($users as $clientUser)
											{
												if($clientUser -> userNumber == $currentSignal -> signal -> zone_user)
												{
													$currentUser = $usersClient -> getDoc($clientUser -> userID);
													$userName = $currentUser -> details -> name;
													$userMatchFound = true;
												}
											}
											if($userMatchFound)
											{
												$content = str_replace("\$zone_user","$userName",$content);
											}
											else
											{
												$content = str_replace("\$zone_user","User " . $currentSignal -> signal -> zone_user,$content);
											}
										}
									}
									//get client users
									{
										$users = $client -> users;
										//get user type
										foreach($users as $clientUser)
										{
											$userID = $clientUser -> userID;
											$currentUser = $usersClient -> getDoc($userID);
											$responsePlan = $actionPlans -> plans -> $actionPlan -> responsePlan;
											$responseUserTypes = $responsePlans -> plans -> $responsePlan -> userTypes;
											//loop through response user types
											foreach($responseUserTypes as $currentResponseUserType)
											{
												if($currentUser -> details -> userType == $currentResponseUserType)
												{
													if($currentUser -> details -> contact -> mobile -> respond)
													{
														output("Sending SMS");
														$smsContent = str_replace(" ","%20",$content);
														$smsContent = urlencode($actionPlans -> plans -> $actionPlan -> symbol) . $smsContent;
														$messageRequest = "$clickatellURL&to=" . $currentUser -> details -> contact -> mobile -> detail . "&content=$smsContent";
														curlGet($messageRequest);
													}
													if($currentUser -> details -> contact -> email -> respond)
													{
														output("Sending email");
														$to = array($currentUser -> details -> contact -> email -> detail);
														sendEmail($to,null,null,null,$content);
													}
												}
											}
										}
									}
								}
							}
							//error_reporting(E_ALL);
						}
						else
						{
							$now = $time[1]+$time[0];
							//error_reporting(!E_WARNING);//EXPECTED ERROR: Warning: Creating default object from empty value in /var/www/html/createActivations.php on line 70
							$client -> activations -> $actionPlan -> $now -> activationID = $now;
							$client -> activations -> $actionPlan -> $now -> isActive = true;
							$client -> activations -> $actionPlan -> $now -> assignedSignals[] = $signalID;
							$client -> activations -> $actionPlan -> $now -> dateTime = date('d/m/Y @ H:i:s',$now);
							//error_reporting(E_ALL);
							//store Client
								{
									output("Updating Client");
									if($actionPlan == "Closing")
									{
										$client -> currentStatus = "closed";
									}
									elseif($actionPlan == "Opening")
									{
										$client -> currentStatus = "open";
										$users = $client -> users;
										foreach($users as $currentUser)
										{
											if($currentUser -> userNumber == $currentSignal -> signal -> zone_user)
											{
												$userID = $currentUser -> userID;
												$client -> openBy = $userID;
											}
										}
									}
									$clientsClient -> storeDoc($client);
								}
							//Send Notifications
							{
								output("Sending Notifications for signal");
								//configure content
								{
									//$content = urlencode($actionPlans -> plans -> $actionPlan -> symbol);
									$content = " ";
									$content .= $actionPlans -> plans -> $actionPlan -> message;
									$content = str_replace("\$clientName", $client -> clientName,$content);
									$content = str_replace("\$time",date("d M Y H:i:s",$currentSignal -> timestamp),$content);
									$content = str_replace("\$eventName",$currentSignal -> signal -> eventName,$content);
									if($actionPlans -> plans -> $actionPlan -> signalType == "zone info")
									{
										$content = str_replace("\$zone_user","Zone: " . $currentSignal -> signal -> zone_user,$content);
									}
									elseif($actionPlans -> plans -> $actionPlan -> signalType == "user info")
									{
										$users = $client -> users;
										foreach($users as $clientUser)
										{
											if($clientUser -> userNumber == $currentSignal -> signal -> zone_user)
											{
												$currentUser = $usersClient -> getDoc($clientUser -> userID);
												$userName = $currentUser -> details -> name;
												$userMatchFound = true;
											}
										}
										if($userMatchFound)
										{
											$content = str_replace("\$zone_user","$userName",$content);
										}
										else
										{
											$content = str_replace("\$zone_user","User " . $currentSignal -> signal -> zone_user,$content);
										}
									}
								}
								//get client users
								{
									$users = $client -> users;
									//get user type
									foreach($users as $clientUser)
									{
										$userID = $clientUser -> userID;
										$currentUser = $usersClient -> getDoc($userID);
										$responsePlan = $actionPlans -> plans -> $actionPlan -> responsePlan;
										$responseUserTypes = $responsePlans -> plans -> $responsePlan -> userTypes;
										//loop through response user types
										foreach($responseUserTypes as $currentResponseUserType)
										{
											if($currentUser -> details -> userType == $currentResponseUserType)
											{
												if($currentUser -> details -> userType == $currentResponseUserType)
												{
													if($currentUser -> details -> contact -> mobile -> respond)
													{
														output("Sending SMS");
														$smsContent = str_replace(" ","%20",$content);
														$smsContent = urlencode($actionPlans -> plans -> $actionPlan -> symbol) . $smsContent;
														$messageRequest = "$clickatellURL&to=" . $currentUser -> details -> contact -> mobile -> detail . "&content=$smsContent";
														curlGet($messageRequest);
													}
													if($currentUser -> details -> contact -> email -> respond)
													{
														output("Sending email");
														$to = array($currentUser -> details -> contact -> email -> detail);
														sendEmail($to,null,null,null,$content);
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		else
		{
			//error_reporting(E_ALL);
			if((!$currentSignal -> convertedToActivation))
			{
				//error_reporting(!E_NOTICE);//EXPECTED ERROR: Notice: Undefined property: stdClass
				$actionPlan = "Test Mode";
				$relevantActivations = $client -> activations -> $actionPlan;
				//error_reporting(E_ALL);
				if(!is_null($relevantActivations))
				{
					//loop through $relevantActivations
					foreach($relevantActivations as $currentActivation)
					{
						//test if activation is active
						if($currentActivation -> isActive)
						{
							$signalAddedToActivation = true;
							$assignedSignals = $currentActivation -> assignedSignals;
							$activationID = $currentActivation -> activationID;
							$client -> activations -> $actionPlan -> $activationID -> assignedSignals[] = $signalID;
							//store Client
							{
								output("Updating Client");
								if($actionPlan == "Closing")
								{
									$client -> currentStatus = "closed";
								}
								elseif($actionPlan == "Opening")
								{
									$client -> currentStatus = "open";
									$users = $client -> users;
									foreach($users as $currentUser)
									{
										if($currentUser -> userNumber == $currentSignal -> signal -> zone_user)
										{
											$userID = $currentUser -> userID;
											$client -> openBy = $userID;
										}
									}
								}
								$clientsClient -> storeDoc($client);
							}
						}
					}
				}
				else
				{
					$now = $time[1]+$time[0];
					//error_reporting(!E_WARNING);//EXPECTED ERROR: Warning: Creating default object from empty value in /var/www/html/createActivations.php on line 70
					$client -> activations -> $actionPlan -> $now -> activationID = $now;
					$client -> activations -> $actionPlan -> $now -> isActive = true;
					$client -> activations -> $actionPlan -> $now -> assignedSignals[] = $signalID;
					$client -> activations -> $actionPlan -> $now -> dateTime = date('d/m/Y @ H:i:s',$now);
					//error_reporting(E_ALL);
					//store Client
					{
						output("Updating Client");
						if($actionPlan == "Closing")
						{
							$client -> currentStatus = "closed";
						}
						elseif($actionPlan == "Opening")
						{
							$client -> currentStatus = "open";
							$users = $client -> users;
							foreach($users as $currentUser)
							{
								if($currentUser -> userNumber == $currentSignal -> signal -> zone_user)
								{
									$userID = $currentUser -> userID;
									$client -> openBy = $userID;
								}
							}
						}
						$clientsClient -> storeDoc($client);
					}
				}
			}
		}
		//Update signal
		{
			output("Updating signal as converted to Activation");
			$currentSignal -> convertedToActivation = true;
			$signalsClient -> storeDoc($currentSignal);
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
		function curl($host,$data)//Data to be put in the format $data = "Hello=World&John=Travolta&...=..."
		{
			// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $host);
			if(!is_null($data))
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			
			$result = curl_exec($ch);
			if (curl_errno($ch)) {
				echo 'Error:' . curl_error($ch);
			}
			curl_close ($ch);
		}
		function curlGet($url)
		{
			// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
			
			
			$headers = array();
			$headers[] = "Cache-Control: no-cache";
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			
			$result = curl_exec($ch);
			if (curl_errno($ch)) {
			    echo 'Error:' . curl_error($ch);
			}
			curl_close ($ch);
		}
		function sendEmail($to,$cc,$bcc,$subject,$content)
		{
			if(is_null($subject))
			{
				$subject = "ICG Alarm Monitor";
			}
			$mail = new PHPMailer;
//            $mail -> SMTPDebug = 3;
            $mail -> isSMTP();  
            $mail -> Host = 'tls://smtp.gmail.com:587';
            $mail -> SMTPAuth = true;
            $mail -> Username = 'support@integrated-za.com';  	//This needs to be a valid gmail account. ie; matthewm@integrated-za.com (although I'd suggest creating another account for this purpose for secuirty)
            $mail -> Password = '!Nt3gr@t3dC0r3Gr0up2015';					//This needs to be a vaild password for the account above
            $mail -> setFrom('NoReply@integrated-za.com');
            if(!is_null($to))
            {
	            foreach($to as $currentRecipient)
	            {
	                $mail -> addAddress($currentRecipient);
	            }
            }
            if(!is_null($cc))
            {
	            foreach($cc as $currentRecipient)
	            {
	                $mail -> addCC($currentRecipient);
	            }
            }
            if(!is_null($bcc))
            {
	            foreach($BCC as $currentRecipient)
	            {
	                $mail -> addBCC($currentRecipient);
	            }
            }
            $mail -> Subject = $subject;
            $mail -> ContentType = 'text/plain';
            $mail -> isHTML(true);
            $mail -> AltBody = $content;
            $mail -> Body    = "<h1>$content</h1>";
	        //send Email
	        {
	            if(!$mail -> send())
	            {
	                echo 'Message could not be sent.';
	                echo 'Mailer Error: ' . $mail -> ErrorInfo;
	            }
	            else
	            {
	                echo 'Message has been sent.';
	                echo "\r\n<br>";
	            }
	        }
		}
	}
?>