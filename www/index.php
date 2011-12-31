<?php
	require_once("_inc/include.php");
	if($System->REST->request[0] == "api")
	{
		require_once("backend/apihandler.php");
		die();
	}

	switch($System->REST->request[0])
	{
		case "api":
		{
			require_once("backend/apihandler.php");
			break;
		}
		case "user":
		{
			require_once("backend/getkarma.php");
			break;
		}
		case "give":
		{
			require_once("backend/addkarma.php");
			break;
		}
		case "leaders":
		case "leaderboard":
		{
			require_once("backend/leaderboard.php");
			break;
		}
		case "verify":
		{
			require_once("backend/verify.php");
			break;
		}
		case "postcard":
		{
			require_once("backend/cardkarma.php");
			break;
		}
		case "":
		{
			require_once("backend/cardkarma.php");
			break;
		}
	}