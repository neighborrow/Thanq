<?php
	// System File
	
class core
{
	public $_cwd;
	public $fs_root;
	
	// The obligatory construction
	public function __construct()
	{
		$this->_cwd = dirname(__FILE__); // Get the Library directory for including classes.  "I'm a class, etc."
		$this->fs_root = dirname(__FILE__)."/..";
	}
	
	public function load_session_handler()
	{
		if($this->session) return;
		require_once($this->_cwd."/session_handler.class.php");
		$this->session = new session_handler($this->_fs_root."/sessions");
	}
	
	public function load_mongo_handler()
	{
		try
		{
			if($this->mongo) return;
			$server = Settings::MONGO_Server;
			$port = Settings::MONGO_Port;
			$database = Settings::MONGO_Database;
			$con = new Mongo("mongodb://{$server}:{$port}/{$database}",array("username" => Settings::MONGO_User, "password" => Settings::MONGO_Pass));
			$this->mongo = $con->selectDB(Settings::MONGO_Database);
		} catch(Exception $e) { }
	}
	
	public function load_database_handler()
	{
		if ($this->db) return;
		$host = Settings::SQL_Server;
		$database = Settings::SQL_Database;
		$user = Settings::SQL_User;
		$pass = Settings::SQL_Pass;
		$this->db = new PDO("mysql:host={$host};dbname={$database}",$user,$pass);
		$this->db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_WARNING);
	}
	
	public function load_template_handler()
	{
		if($this->tpl) return;
		require_once($this->_cwd."/template.class.php");
		$this->tpl = new template($this);
	}
	
	public function load_user_handler()
	{
		if($this->user_handler) return;
		require_once($this->_cwd."/user_handler.class.php");
		$this->user_handler = new user_handler($this);
	}
	
	public function load_compliment_handler()
	{
		if($this->compliment_handler) return;
		require_once($this->_cwd."/compliment_handler.class.php");
		$this->compliment_handler = new compliment_handler($this);
	}
	
	public function load_REST()
	{
		if($this->REST) return;
		require_once($this->_cwd."/REST.class.php");
		$this->REST = new REST;
	}
	
	public function load_utilities()
	{
		require_once($this->_cwd."/utilities.class.php");
	}
	
	public function load_email()
	{
		require_once($this->_cwd."/email.class.php");
	}
	
	public function &__get($var)
	{
		header("X-Loading-{$var}: TRUE");
		switch($var)
		{
			case "session":
				$this->load_session_handler();
				return $this->session;break;
			case "db":
				$this->load_database_handler();
				return $this->db;break;
			case "user_handler":
				$this->load_user_handler();
				return $this->user_handler;break;
			case "compliment_handler":
				$this->load_compliment_handler();
				return $this->compliment_handler;break;
			case "tpl":
				$this->load_template_handler();
				return $this->tpl;break;
			case "REST":
				$this->load_REST();
				return $this->REST;break;
			case "mongo":
				$this->load_mongo_handler();
				return $this->mongo;
			default:
				return $this->{$var};break;
		}
	}
}