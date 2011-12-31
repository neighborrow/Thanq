<?php

require_once(dirname(__FILE__)."/user.class.php");

/**

user_handler {

	// Properties //
	
	private reference $core; // Reference to the Core
	private array $user_index; // Index of Users by ID
	public user $current; // Current User Object Reference
	public user $default; // Default User Information
	
	// Methods //
	
5	public void __construct( core &$core );
5	public user &get_by_id( int $user_id ); // Gets a User by ID
5	public user &get_by_username ( str $username ); // Gets a User by Username
5	public void _add_user_to_cache( user &$user ); // Adds a User to the Cache
5	public user login( str $username , str $password ); // Login a User (Sets as Active User)
5	public user logout( void ); // Logs a User Out
5	public user _login_by_id( int $user_id ); // Logs in a User by their ID

**/

class user_handler
{
	private $core;
	public $user_index;
	public $current;
	public $default;
	
	// Construction.  Takes a reference to the core as parameter.
	//
	// Sets up the User Handler and checks if a user is logged in, if so
	// sets them up.
	public function __construct(core &$core)
	{
		$this->core = $core;
		$this->user_index = array("ids" => array());
		//$this->default = new user($core,0);
		//$this->default->load();
		$this->current = $this->default;
		if($this->core->session->user)
			$this->_login_by_id($this->core->session->user);
	}
	
	// Create a User
	public function &create()
	{
		// Find the next ID
		$collection = $this->core->mongo->selectCollection("users");
		$data = array('karma' => 0);
		$collection->insert($data);
		$user = new user($this->core,$data['_id']);
		$user->save(true);
		return $user;
	}
	
	// Get a user by their ID
	public function &get_by_id($id)
	{	
		if(Settings::DEBUG) trigger_error("Searching for User by ID '{$id}'");
		
		if(isset($this->user_index["ids"][$id]) && $this->user_index["ids"][$id]) // If the user is in the cache, return it.
			return $this->user_index["ids"][$id];
		
		$user = new user($this->core,$id);
		if(!$user->load())
			throw new Exception("404");
		
		$this->_add_user_to_cache($user);
		
		return $user;
	}
	
	// Merge Two Accounts into One
	// Let Primary and Secondary be User Instances
	public function &merge_accounts($primary,$secondary)
	{
		// WARNING, This cannot be undone.
		// All information attached to the secondary account will be LOST.

		$opts = array('multiple' => true, 'safe' => false);
		
		// Firstly, Identifiers:
		$collection = $this->core->mongo->selectCollection("identifiers");
		$collection->update(
			array('user_id' => new MongoID($secondary->id)),
			array('$set' => array('user_id' => new MongoID($primary->id), 'primary' => false)),
			$opts
		);
		
		// Secondly, Karma
		$collection = $this->core->mongo->selectCollection("compliments");
		$collection->update(
			array('sender_id' => new MongoID($secondary->id)),
			array('$set' => array('sender_id' => new MongoID($primary->id))),
			$opts
		);
		$collection->update(
			array('receiver_id' => new MongoID($secondary->id)),
			array('$set' => array('receiver_id' => new MongoID($primary->id))),
			$opts
		);
		
		// Delete the Secondary User
		$collection = $this->core->mongo->selectCollection("users");
		$collection->remove(array('_id' => new MongoID($secondary->id)),array('justOne' => true, 'safe' => true));
		
		// For now, thats it.
		$this->_remove_user_from_cache($secondary);
		$primary->load();
		return $primary;
	}
	
	// Get a user by their unique
	public function &get_by_unique($id)
	{
		if(Settings::DEBUG) trigger_error("Searching for User by Unique '{$id}'");
		
		if(isset($this->user_index['uniques'][$id]) && $this->user_index['uniques'][$id]) // If the user is in the cache, return it.
			return $this->user_index['uniques'][$id];
			
		$user = user::load_from_unique($this->core,$id);
		if(!$user->load())
			throw new Exception('404');
		
		$this->_add_user_to_cache($user);
		
		return $user;
	}
	
	// Get a user by their Identifier
	public function &get_by_identifier($ident_type, $id)
	{
		$r = $this->_get_identifier($ident_type,$id);
		if(Settings::DEBUG) trigger_error("Found User '{$r['user_id']}'.");
		if($r["user_id"])
			return $this->get_by_id($r["user_id"]);
		else
			throw new Exception("404");
	}
	
	public function _get_identifier($ident_type, $id)
	{
		if(!$ident_type || !$id) throw new Exception("400");
		
		$collection = $this->core->mongo->selectCollection("identifiers");
		if(Settings::DEBUG) trigger_error("Ran Search for '{$id}' as Ident Type '{$ident_type}'");
		return $collection->findOne(array("type" => $ident_type, "identifier_id" => $id));	
	}
	
	public function generate_identifier_verification($ident_type, $id)
	{
		$ident = $this->_get_identifier($ident_type, $id);
		if($ident["verified"])
			throw new Exception("412");
		
		$collection = $this->core->mongo->selectCollection("identifiers");
		$verify_key = user_handler::salt_pass(microtime());
		
		$collection->update(
			array("_id" => $ident["_id"]),
			array('$set' => array('verified' => false, 'verification_key' => $verify_key)),
			array('multiple' => false, 'safe' => false)
		);
		
		return $verify_key;
	}
	
	public function verify_identifier($code)
	{
		$collection = $this->core->mongo->selectCollection("identifiers");
		$ident = $collection->findOne(array('verification_key' => $code));
		if(!$ident["_id"]) throw new Exception("404");
		
		$collection->update(
			array("_id" => $ident["_id"]),
			array('$set' => array('verified' => true), '$unset' => array('verification_key' => 1)),
			array('multiple' => false, 'safe' => true)
		);
		
		return $this->_login_by_user($this->get_by_identifier($ident['type'],$ident['identifier_id']));
	}
	
	// Attempt to Login a User, Returns the user object on creation.
	// Throws an error on failure
	public function login_by_email($username,$password)
	{
		if(!$username || !$password) throw new Exception("400");
		
		$r = $this->_get_identifier('email',$username);
		
		if(!$r["user_id"]) throw new Exception("404"); // Not Found
		if(!$r["verified"]) throw new Exception("405"); // Not Allowed
		if($r["key"] != user_handler::salt_pass($password)) throw new Exception("403"); // Forbidden
		
		$user = new user($this->core,$r["user_id"]);
		$this->_add_user_to_cache($user);
		
		$this->_login_by_id($user->id);
	}
	
	public function _login_by_id($id)
	{
		if(intval($id) != $id) throw new Exception("400");
		$user = $this->get_by_id($id);
		
		$this->current = $user;
		$this->core->session->user = $user->id;
		
		return $user;
	}
	
	public function _login_by_user(&$user)
	{
		return $this->_login_by_id($user->id);
	}
	
	public function logout()
	{
		$this->core->session->destroy();
		return true;
	}
	
	public function _add_user_to_cache(&$user)
	{
		if(!$user->id) throw new Exception("400"); // Discard
		
		$this->user_index["ids"][$user->id] = &$user;
		if(Settings::DEBUG) trigger_error("Adding User '{$user->id}' to the Cache.");
		if($user->has_var('unique'))
			$this->user_index['uniques'][$user->get_var('unique')] = &$user;
	}
	
	public function _remove_user_from_cache(&$user)
	{
		if(!$user->id) throw new Exception("400"); // Discard
		
		if(Settings::DEBUG) trigger_error("Removing User '{$user->id}' from the Cache.");
		
		if(isset($this->user_index["ids"][$user->id]))
			unset($this->user_index["ids"][$user->id]);
		if($user->has_var('unique') && isset($this->user_index['uniques'][$user->get_var('unique')]))
			unset($this->user_index['uniques'][$user->get_var('unique')]);
	}
	
	public static function salt_pass($password)
	{
		return sha1($password."karma");
	}
}

