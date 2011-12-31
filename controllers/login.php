<?php
	if($core->REST->post)
	{
		try
		{
			$user = $core->REST->post["user"];
			$pass = $core->REST->post["pass"];
			$ident = $core->user_handler->login_by_email($user,$pass);
		}
		catch(Exception $e)
		{
			switch($e->getMessage())
			{
				case "400":
					$core->REST->status(400);
					$core->tpl->title = "Missing Parameters";
					$core->tpl->display("errors/login/400.html");
					break;
					
				case "403": // Simple Incorrect Password.
				case "404":
					$core->REST->status(404);
					$core->tpl->title = "Login Failed";
					$core->tpl->display("errors/login/404.html");
					break;
					
				case "405":
					$core->tpl->title = "Login Failed";
					$core->tpl->email = $user;
					$core->tpl->display("errors/login/405.html");
					break;
			}
		}
		$core->tpl->title = "Login Successful";
		$core->tpl->display("login/success.html");
	}
	$core->tpl->title = "Login";
	$core->tpl->display("login/login.html");