<?php

class session_handler
{
	function __construct($save_path = null)
	{
		if($save_path !== NULL) session_save_path($save_path);
		session_start();
		if($this->keep_logged_in)
			$this->keep_logged_in();
	}
	
	function keep_logged_in()
	{
		setcookie(session_name(),session_id(),time()+(60*60*24*30)); // Stay Logged in for 30 days
		$this->keep_logged_in = true;
	}
	
	function __set($var,$val)
	{
		$_SESSION[$var] = $val;
	}
	
	function &__get($var)
	{
		return $_SESSION[$var];
	}
	
	function __isset($var)
	{
		return isset($_SESSION[$var]);
	}
	
	function destroy()
	{
		$_SESSION = array();
		session_write_close();
		session_unlink();
		session_destroy();
	}
	
	function __destruct()
	{
		session_write_close();
	}
}
