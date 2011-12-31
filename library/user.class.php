<?php

class user
{
	private $core;
	public $id;
	protected $info;
	
	// Construct Using the ID
	public function __construct(&$core,$id)
	{
		$this->_set_core($core);
		$this->id = $id;
		$this->info = array
		(
			"loaded" => false,
			"vars" => array()
		);
	}
	
	// Alternate Constructor Using a "Unique."  Additionally, auto-loads.
	public static function load_from_unique(&$core,$id)
	{
		$collection = $core->mongo->selectCollection("users");
		$vars = $collection->findOne(array('unique' => $id));
		if($vars == null) throw new Exception('404');
		$user = new user($core,$vars['_id']);
		$user->info['vars'] = $vars;
		$user->info['loaded'] = true;
		return $user;
	}
	
	public function _set_core(&$core)
	{
		$this->core = $core;
	}
	
	public function __set($var,$val)
	{
		if($var == "id")
			if(intval($val) != $val)
				throw new Exception("Bad ID Given to User.");
		$this->{$var} = $val;
	}
	
	public function get_var($vars)
	{
		$this->load();
		$store = &$this->info["vars"];
		$args = func_get_args();
		$broke = false;
		foreach($args as $arg)
		{
			if(isset($store[$arg]))
				$store = &$store[$arg];
			else
			{
				$broke = true;
				break;
			}
		}
		if((!isset($store) || $broke) && $no_default)
			return NULL;
		if((!isset($store) || $broke) && $this->id)
			return call_user_func_array(array($this->core->user_handler->default,"get_var"),$args);
		else
			return $store;
	}
	
	public function has_var($vars)
	{
		$this->load();
		$store = &$this->info["vars"];
		$args = func_get_args();
		$broke = false;
		foreach($args as $arg)
		{
			if(isset($store[$arg]))
				$store = &$store[$arg];
			else
			{
				$broke = true;
				break;
			}
		}
		if((!isset($store) || $broke))
			return false;
		else
			return true;
	}
	
	public function set_var($vars)
	{
		$this->load();
		$store = &$this->info["vars"];
		$args = func_get_args();
		$i = 1;
		foreach($args as $arg)
		{
			if($i == count($args))
				$store = $arg;
			else
			{
				if(!isset($store[$arg])) $store[$arg] = array();
				$store = &$store[$arg];
			}
			$i++;
		}
		return $this->save();
	}
	
	public function load($check = true)
	{
		if($this->info["loaded"] && $check) return true;
		$table = $this->core->mongo->selectCollection("users");
		$vars = $table->findOne(array("_id" => (new MongoID($this->id))));
		// If Failure
		if($vars == null) return false;
		
		$this->info["vars"] = $vars; 
		$this->info["loaded"] = true;
		return true;
	}
	
	public function save($skip_check = false)
	{
		if(!$skip_check && !$this->info["loaded"]) return false;
		$table = $this->core->mongo->selectCollection("users");
		$vars = $this->info["vars"];
		$table->update(array("_id" => $this->id),$vars,array("upsert" => true));
	}
	
	public function add_identifier($type, $id, $key = NULL, $verified = false)
	{
		$collection = $this->core->mongo->selectCollection("identifiers");
		if($type == 'email' && $key != NULL)
			$key = user_handler::salt_pass($key);
		$data = array("type" => $type, "user_id" => $this->id, "identifier_id" => $id, "key" => $key, "verified" => false, "primary" => false);
		$collection->insert($data);

		if(!$this->has_var("display") && $type == "email")
			$this->set_var("display",substr($id,0,strpos($id,"@")));			
	}
	
	public function get_identifiers($type = null, $must_be_verified = false, $primary = false)
	{
		$collection = $this->core->mongo->selectCollection("identifiers");
		$query = array("user_id" => $this->id);
		if($type != null) $query["type"] = $type;
		if($must_be_verified) $query["verified"] = true;
		if($primary) $query["primary"] = true;
		return $collection->find($query);
	}
}