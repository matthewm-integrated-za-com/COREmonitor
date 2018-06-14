<?php
/*var_dump(
	crypt(
		crypt("p","d").crypt("a","r").crypt("s","o").crypt("s","w").crypt("w","s").crypt("o","s").crypt("r","a").crypt("d","p")
		,"password")
	);
	*/
//var_dump(crypt("1","hello world"));
var_dump(supercrypt("Hello"));




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
?>