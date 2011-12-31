<?php
	session_start();
	$INCDIR = dirname(__FILE__);

	// Requirements
		require_once($INCDIR."/settings.php");
		require_once($INCDIR."/obj_user.php");
		require_once($INCDIR."/obj_system.php");
		require_once($INCDIR."/obj_client.php");
		require_once($INCDIR."/obj_karma.php");
		require_once($INCDIR."/REST.class.php");
	
	// Database Connection
		$DBH = new PDO("mysql:host=".Settings::$MYSQL_HOST.";dbname=".Settings::$MYSQL_DB,Settings::$MYSQL_USER,Settings::$MYSQL_PASS);

	// Important Things
		$System = new System($DBH);