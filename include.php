<?php
	session_start();
	$INCDIR = dirname(__FILE__);
	
	// Requirements
		require_once($INCDIR."/settings.php");
		require_once($INCDIR."/library/core.class.php");
		
	// Setup Core
		$core = new core;
		
	// Handle Login
		// Secret: Login is Automatically Handled by User Handler =p