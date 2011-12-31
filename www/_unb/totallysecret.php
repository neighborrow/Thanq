<?php
	require_once("../_inc/include.php");
	$json = $_REQUEST["data_json"];
	$json = json_decode($json,true);

	$page_id = $_REQUEST["page_id"];

	if($page_id == "89b7d1e4-02a1-11e0-b29f-12313e003591")
	{
		// Verify Email
			$email = $json["email_address_of_the_person_you_are_complimenting"];
			$email_parts = explode("@",$email);
			if(count($email_parts) != 2) { die("bad email"); } // Not a valid email.
			if(!getmxrr($email_parts[1],$array)) { die("bad email"); } // Domain doesn't accept email.
			// email verified, for all intents and purposes.

		$hash = sha1("mailto:{$email}");
		$user = $System->UserHandler->getUserByHash($hash);
		if($user->id == -1)
		{
			$user = $System->UserHandler->create($hash);
		}
		$ka = $user->addKarma(NULL,4,$json["your_compliment"],$json["short_description_of_what_they_did/why_you_are_complimenting"]);
		if(!$ka)
		{
			die("failed");
		}
	}

	ob_start();
	var_dump($_REQUEST);
	$c = ob_get_contents();
	ob_end_clean();
	$f = fopen("debug.txt","a");
	fwrite($f,$c);
	fclose($f);