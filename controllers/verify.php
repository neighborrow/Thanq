<?php
	// Verification for Email Addresses
	if($core->REST->post)
	{
		$email = $core->REST->post['email'];
		$code = $core->user_handler->generate_identifier_verification('email',$email);
		$core->load_email();
		$email = new email($core,"verify.html");
		$email->from = "ThanQ <noreply@neighborrow.com>";
		$email->subject = "ThanQ Verification Code";
		$email->add_to($email,$email);
		$email->tpl->code = $code;
		if($email->send())
		{
			$core->tpl->title = "Verification Code Sent";
			$core->tpl->display("verify/sent.html");
		} else {
			$core->tpl->title = "Unknown Error";
			$core->tpl->display("errors/verify/500.html");
		}
	}
	else
	{
		$code = $core->REST->request[1];
		if(!$code) // 400. Bad Request.
		{
			$core->REST->status(400);
			$core->tpl->title = "No Verification Code";
			$core->tpl->display("errors/verify/400.html");
		}
		
		try
		{
			$ident = $core->user_handler->verify_identifier($code);
			$core->tpl->title = "Verified Successfully";
			$core->tpl->display();
		}
		catch(Exception $e)
		{
			if($e->getMessage() == "404")
			{
				$core->REST->status(404);
				$core->tpl->title = "No Such Verificationn Code Found";
				$core->tpl->display("errors/verify/404.html");
			}
			die($e->getMessage());
		}
	}