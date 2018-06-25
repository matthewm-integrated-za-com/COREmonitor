<?php
	$stream = stream_socket_client("tcp://192.168.0.35:10002", $errno, $errstr);
	if (!$stream) {
	    echo "{$errno}: {$errstr}\n";
	    var_dump(shell_exec('python /var/www/html/signalListener.py'));
	    die();
	}
	
	fwrite($stream, "2SYST18099901000:");
	
	stream_socket_shutdown($stream, STREAM_SHUT_WR); /* This is the important line */
	
	$contents = stream_get_contents($stream);
	
	fclose($stream);
	
	var_dump( $contents);
?>