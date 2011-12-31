<?php

class compliment
{
	private $core;
	public $id;
	protected $info;
	
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
	
	public function _set_core(&$core)
	{
		$this->core = $core;
	}
	
	public function get_var($vars)
	{
		$this->load();
		$store = &$this->info["vars"];
		$args = func_get_args();
		// $final_arg = array_pop($args);
		$broke = false;
		if(Settings::DEBUG) trigger_error("Calling get_var with Args: ".print_r($args,true));
		foreach($args as $arg)
		{
			if(isset($store[$arg]))
			{
				if(Settings::DEBUG) trigger_error("Is Set store[{$arg}]");
				$store = &$store[$arg];
			}
			else
			{
				if(Settings::DEBUG) trigger_error("Is Not Set store[{$arg}]");
				$broke = true;
				break;
			}
		}
		if((!isset($store) || $broke) && $this->id)
		{
			if(Settings::DEBUG) trigger_error("Calling Upon Default Values");
			return call_user_func_array(array($this->core->compliment_handler->default,"get_var"),func_get_args());
		}
		else if (!isset($store) && ($broke || !$this->id))
		{
			if(Settings::DEBUG) trigger_error("Returning NULL for Variable Query");
			return NULL;
		}
		else
		{
			if(Settings::DEBUG) trigger_error("Returning '{$store}' for Variable Query");
			return $store;
		}
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

		$collection = $this->core->mongo->selectCollection("compliments");
		$vars = $collection->findOne(array("_id" => (new MongoID($this->id))));
		
		$this->info["vars"] = $vars;
		$this->info["loaded"] = true;
		
		// Things to do after loading variables.
		/*
		if($this->get_var("sender_id"))
		{
			if(Settings::DEBUG) trigger_error("Loading Sender ID: ".print_r($this->get_var("sender_id"),true));
			$this->sender = $this->core->user_handler->get_by_id($this->get_var("sender_id"));	
		}
		if($this->get_var("receiver_id")) 
		{
			if(Settings::DEBUG) trigger_error("Loading Receiver ID: ".print_r($this->get_var("receiver_id"),true));
			$this->receiver = $this->core->user_handler->get_by_id($this->get_var("receiver_id"));
		}
		*/
		return true;
	}
	
	public function save()
	{
		if(!$this->info["loaded"]) return false;
		
		$collection = $this->core->mongo->selectCollection("compliments");
		$collection->update(array("_id" => $this->id),$this->info["vars"],array("upsert" => true));
	}
}