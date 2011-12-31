<?php

/**

class template {

	// Properties //
	
	private core $core;
	public string $_dir = ''; // Directory where the Themes are stored.
	public string $_theme = null; // The Theme we are using.
	public string $_content; // Content to be Output on Display (inside Wrapper)
	public string $_wrapper = "wrapper.tpl.php"; // Wrapping File
	
	// Methods //
	
	public void __construct( core &$core );
	public string make_dir([ string $theme = null ]); // Make a Directory
	public void set_theme([ string $theme = null ]); // Set the Current Theme
	public string fetch( string $filename ); // Filename to Render (without Output)
	public void display( string $filename [, bool $use_wrapper = true ] );

**/

class template
{
	private $core;
	public $_dir = "";
	public $_theme = null;
	public $_content;
	public $_wrapper = "wrapper.html";
	
	function __construct(&$core)
	{
		$this->core = &$core;
		$this->_dir = "{$core->fs_root}/templates/html/";
	}
	
	public function make_dir($theme = null)
	{
		if($theme === null) $theme = $this->_theme;
		if($theme === null) return $this->_dir;
		return $this->_dir.strtolower(str_replace(" ","_",$theme))."/";
	}
	
	public function set_theme($theme = null)
	{
		$this->_theme = $theme;
	}
	
	public function fetch($file)
	{
		$core = &$this->core;
		$tpl = &$this;
		ob_start();
		include($this->make_dir().$file);
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
	
	public function display($file,$wrap = true,$die = true)
	{
		$this->_content = $this->fetch($file);
		if(!$wrap)
			echo $this->_content;
		else
			echo $this->fetch($this->_wrapper);
		
		if($die)
			die();
	}
}
