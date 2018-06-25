<?php
	var_dump(shell_exec("curl -X POST   http://127.0.0.1:5984/_session   -H 'accept: application/json'   -H 'cache-control: no-cache'   -H 'content-type: application/x-www-form-urlencoded'   -H 'origin: http://127.0.0.1:5984'   -H 'postman-token: 7b8c0dbf-5a41-6d36-0b4d-09a08d94e26d' -d 'name=ICG_Admin&password=!Nt3gr@t3dC0r3Gr0up2015' --cookie-jar ./somefile"));
	$databases = array("users","clients","config","signals");
	foreach($databases as $database)
	{
		compactDB($database);
	}
	function compactDB($database)
	{
		var_dump(shell_exec("curl --cookie ./somefile -X POST 'http://127.0.0.1:5984/coremonitor_$database/_compact' -H 'accept: application/json'   -H 'cache-control: no-cache'   -H 'content-type: application/json'   -H 'origin: http://127.0.0.1:5984'"));
	}
	$stream = stream_socket_client("tcp://192.168.0.35:10002", $errno, $errstr);
	fwrite($stream, "2SYST18199901000:");
	
	stream_socket_shutdown($stream, STREAM_SHUT_WR); /* This is the important line */
	
	$contents = stream_get_contents($stream);
	
	fclose($stream);
	
	var_dump( $contents);
?>