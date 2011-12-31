<?php
/*
 * This file is in charge of setting up the environment
 * And Routing the Views to their respective controllers.
 */

	$cwd = dirname(__FILE__);
	$home = $cwd."/../";
	
	// Load the Core
		require_once($home."/include.php");
		
	// Load Utilitity Functions.. REST should be one though.. shouldn't it?
		require_once($home."/library/utilities.class.php");
		
	// Routing
	switch($core->REST->request[0])
	{
		case "test":
			require_once($home."/controllers/test.php");
			break;
			
		case "user": // User Display
			require_once($home."/controllers/profile.php");
			break;
			
		case "verify": // Identifier Verification
			require_once($home.'/controllers/verify.php');
			break;
		
		case "login": // Login
			require_once($home.'/controllers/login.php');
		
		case "":
			array_unshift($core->REST->request,"postcard");
		case "postcard":
			require_once($home."/controllers/postcard.php");
			break;
			
		default:
			$core->REST->status(404);
			$core->tpl->title = "Page Not Found (404)";
			$core->tpl->display("errors/404.html");
	}