<?php

require_once(dirname(__FILE__)."/compliment.class.php");

class compliment_handler
{
	private $core;
	public $index;
	public $default;
	
	public function __construct(&$core)
	{
		$this->core = &$core;
		$this->default = new compliment($core,0);
		$this->default->load();
	}
	
	// Create a Compliment
	public function &create($sender_id = NULL, $receiver_id = NULL)
	{
		$sender = $this->core->user_handler->get_by_id($sender_id);
		$receiver = $this->core->user_handler->get_by_id($receiver_id);
		if(!$sender->id || !$receiver->id)
			throw new Exception("Error Creating new Compliment: Sender or Receiver does not Exist!");
		
		$queued = true;
		if($sender->get_var("can_send"))
			$queued = false;
			
		$collection = $this->core->mongo->selectCollection("compliments");
		$data = array("sender_id" => $sender_id, "receiver_id" => $receiver_id, "queued" => $queued);
		$collection->insert($data);
		
		if($sender->get_var("can_send"))
			$receiver->set_var("can_send",true);
			
		$compl = $this->get_by_id($data['_id']);
		return $compl;
	}
	
	public function get_by_user_id($id)
	{
		$collection = $this->core->mongo->selectCollection("compliments");
		$cursor = $collection->find(array('queued' => false,'$or' => array(array("sender_id" => new MongoID($id)), array("receiver_id" => new MongoID($id)))));
		$compliments = array();
		foreach($cursor as $doc)
		{
			$temp = new compliment($this->core,$doc["_id"]);
			$this->_add_compliment_to_cache($temp);
			$compliments[] = $temp;
		}
		return $compliments;
	}
	
	public function &get_by_id($id)
	{
		if($this->index[$id]) 
			return $this->index[$id];
		
		$compliment = new compliment($this->core,$id);
		if(!$compliment->load())
			throw new Exception("Compliment '{$id}' not found.");
		
		$this->_add_compliment_to_cache($compliment);
		
		return $compliment;
	}
	
	public function _add_compliment_to_cache(&$compliment)
	{
		if(!$compliment->id) throw new Exception("Invalid Compliment Attempted to be Added to Cache");
		$this->index[$compliment->id] = &$compliment;
	}
}