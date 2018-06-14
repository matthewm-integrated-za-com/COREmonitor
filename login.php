<?php
	session_start();
	unset($_SESSION["loginError"]);
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
		$usersDB = "coremonitor_users";
		$usersClient = new CouchClient($couchDsn,$usersDB);
	}
	//collect all users
	{
		$allUsers = $usersClient -> getAllDocs();
		$success = false;
		//loop through rows
		foreach($allUsers -> rows as $currentRow)
		{
			$userID = $currentRow -> id;
			if(testCredentials($userID,$_POST["email"]))
			{
				$success = true;
			}
		}
		if(!$success)
		{
			$_SESSION["loginError"] = "Unable to Validate your Credentials, Please try again";
		}
		echo "<script>window.location.href=\"../" . $_POST["returnTo"] . "\"</script>";
	}
	//functions
	{
		function testCredentials($userID,$email)
		{
			global $usersClient;
			//get User Doc
			$currentUser = $usersClient -> getDoc($userID);
			if($currentUser -> details -> contact -> email -> detail == $email)
			{
				if(($currentUser -> platform -> allow) && ($currentUser -> platform -> password == supercrypt($_POST["password"])))
				{
					$_SESSION["signedInUser"] = $userID;
					$time = explode(" ",microtime());
					$now = $time[1]+$time[0];
					$_SESSION["lastActivity"] = $now;
					return(true);
				}
			}
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
	}
	function store($print)
	{
	    fopen('print.txt', 'w');
	    file_put_contents('print.txt',print_r($print, true));
	}
?>