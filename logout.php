<?php
	var_dump(session_start());
	echo("<br>Destroying Session: ");
	var_dump(session_destroy());
	echo "<script>window.location.href=\"/\"</script>";
?>