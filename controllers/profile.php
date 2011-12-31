<?php
	if(!isset($core->REST->request[1]))
	{
		if($core->user_handler->current->id != null) $profile_id = $core->user_handler->current->id;
	}
	else $profile_id = $core->REST->request[1];
		
	try
	{
		if(!isset($profile_id)) throw new Exception("404");
		try
		{
			$core->tpl->user = $user = $core->user_handler->get_by_id($profile_id);
		}
		catch(Exception $e)
		{
			if($e->getMessage() == "404")
			{
				$core->tpl->user = $user = $core->user_handler->get_by_unique($profile_id);
				$profile_id = $user->id;
			}
			else throw new Exception($e->getMessage());
		}
	}
	catch(Exception $e)
	{
		// Error.
		if($e->getMessage() == "404")
		{
			$core->REST->status(404);
			$core->tpl->title = "User Not Found";
			$core->tpl->display("errors/user/404.html");
		}
		else
		{
			$core->REST->status(500);
			$core->tpl->title = "Unknown Error";
			if(Settings::DEBUG) trigger_error("Error Loading User {$profile_id}: {$e}");
			$core->tpl->display("errors/unknown.html");
		}
	}
	// Get Compliments
	$core->tpl->compliments = $compliments = $core->compliment_handler->get_by_user_id($user->id);
	$karma_count = 0;
	foreach($compliments as $compliment)
	{
		$karma_count += $compliment->get_var("karma");
	}
	$core->tpl->karma_count = $karma_count;
	
	// Get Verified Emails
	$idents = $user->get_identifiers('email',true,false);
	$core->tpl->verifiedDomains = array();
	foreach($idents as $ident)
	{
		$temp = explode("@",$ident['identifier_id']);
		$core->tpl->verifiedDomains[] = $temp[1];
	}
	
	$core->tpl->title = $user->get_var("display");
	$core->tpl->display("profile.html");