<?php
	if($core->REST->post)
	{
		// Handle Verification (We have everything we need)
		$adjective = $core->REST->post("adjective");
		$reason = $core->REST->post("reason");
		$connection = $core->REST->post("connection");
		$receiver_ident = $core->REST->post("receiver");
		$sender_ident = $core->REST->post("giver");
		
		
		
		if($adjective && $reason && $connection && $receiver_ident && $sender_ident)
		{		
			$receiver = find_or_create_user_by_string($core,$receiver_ident);
			$sender = find_or_create_user_by_string($core,$sender_ident);
		
			$compliment = $core->compliment_handler->create($sender->id,$receiver->id);
			$compliment->set_var("adjective",$adjective);
			$compliment->set_var("reason",$reason);
			$compliment->set_var("connection_id",(new MongoID($connection)));
			
			if(!$compliment->get_var("queued"))
			{
				$result = $receiver->get_identifiers("email",true,true);
				if(count($result))
				{
					$core->REST->header("X-Sending-Mail",true);
					$core->load_email();
					$email = new email($core,"postcard.html");	
					$email->from = "ThanQ <noreply@neighborrow.com>";
					$email->subject = "You received a ThanQ Postcard!";
					$to = $result->getNext();
					$to = $to["identifier_id"];
					$email->add_to($to,$receiver->get_var("display"));
					$email->tpl->adjective = $adjective;
					$email->tpl->reason = $reason;
					$email->tpl->from = $sender->get_var("display");
					$email->tpl->to = $receiver->get_var("display");
					$collection = $core->mongo->selectCollection("sources");
					$source = $collection->findOne(array('_id' => new MongoID($connection)));
					$email->tpl->connection = $source["name"];
					$result = !!$email->send();
					$core->REST->header("X-Sent-Mail-Result",$reuslt); 
				} else $core->REST->header("X-Sending-Mail",false);
			}
			
			$core->REST->location("/postcard/{$compliment->id}",303);
		}
		else
		{
			var_dump($core->REST->post);
			throw new Exception("Not all Variables Filled Out");	
		}
	}
	if($core->REST->request[1])
	{
		$core->tpl->DISPLAY = true;
		$compliment = $core->compliment_handler->get_by_id($core->REST->request[1]);
		$core->tpl->giver = $core->user_handler->get_by_id($compliment->get_var("sender_id"));
		$core->tpl->receiver = $core->user_handler->get_by_id($compliment->get_var("receiver_id"));
		$core->tpl->adjective = $compliment->get_var("adjective");
		$collection = $core->mongo->selectCollection("sources");
		$core->tpl->connection = $collection->findOne(array('_id' => $compliment->get_var("connection_id")));
	}
	else
	{
		$collection = $core->mongo->selectCollection("adjectives");
		$cursor = $collection->find();
		foreach($cursor as $doc) $core->tpl->adjectives[] = $doc["name"];
		$collection = $core->mongo->selectCollection("sources");
		$cursor = $collection->find();
		$cursor->sort(array('name' => 1));
		foreach($cursor as $doc) $core->tpl->sites[] = $doc;
		$core->tpl->DISPLAY = false;
	}
	
	// Page-Only Functions
	function &find_or_create_user_by_string(&$core,$string)
	{
		if(strpos($string,"@") > 0) // email ?
		{
			if(Settings::DEBUG) trigger_error("{$string} was detected as email.");
			
			$res = utilities::validate_email($string);
			if($res["valid"]) // Valid Email
			{
				if(Settings::DEBUG) trigger_error("{$string} was validated as an Email Address");
				try
				{
					$return = $core->user_handler->get_by_identifier("email",$string);
				}
				catch(Exception $e)
				{
					if(Settings::DEBUG) trigger_error("{$string} was not found in the identifiers, and we're now creating it.");
					$return = $core->user_handler->create();
					if(Settings::DEBUG) trigger_error("{$string} was created as User ID: {$return->id}");
					$return->add_identifier("email",$string);
				}
			}
			else throw new Exception("Invalid Email");
		}
		else // twitter user
		{
			$res = utilities::valid_twitter($string);
			if($res["valid"])
			{
				$string = (substr($string,0,1) == "@" ? substr($string,1) : $string);
				try
				{
					$return = $core->user_handler->get_by_identifier("twitter",$string);
				}
				catch(Exception $e)
				{
					$return = $core->user_handler->create();
					$return->add_identifier("twitter",$string);
				}
			}
			else throw new Exception("Invalid Twitter");
		}
		
		return $return;
	}
	
	// Display
	if($core->tpl->DISPLAY) $core->tpl->title = "You received thanks!";
	else $core->tpl->title = "Send Thanks!";
	$core->tpl->display("postcard.html");