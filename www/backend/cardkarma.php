<?php
	if($System->REST->post)
	{
		// handle adding the Karma
		$valid = TRUE;
		$giver = $System->REST->post["giver"];
		$receiver = $System->REST->post["receiver"];
		$adjective = $System->REST->post["adjective"];
		$reason = $System->REST->post["reason"];
		$client = $System->REST->post["connection"];
		if(!$giver || !$receiver || !$adjective || !$reason)
		{
			$valid = FALSE;
			$tpl_errors[] = "You must fill in <strong>ALL</strong> form fields.";
		}
		if(!validate_email($giver) || !validate_email($receiver))
		{
			$valid = FALSE;
			$tpl_errors[] = "Both emails must be VALID.";
		}
		$client_obj = $System->ClientHandler->getClientById($client);
		if($client_obj->id == -1)
		{
			$valid = FALSE;
			$tpl_errors[] = "There was an error adding the karma.";
		}
		if($valid)
		{
			$giver_hash = $System->UserHandler->emailToHash($giver);
			$giver_obj = $System->UserHandler->getUserByHash($giver_hash);
			if($giver_obj->id == -1)
			{
				$giver_obj = $System->UserHandler->create($giver);
				$displayname = substr($giver,0,4)."...";
				$giver_obj->setDisplay($displayname);
			}
			elseif(!$giver_obj->email)
			{
				$giver_obj->updateEmail($giver);
			}
			$receiver_hash = $System->UserHandler->emailToHash($receiver);
			$receiver_obj = $System->UserHandler->getUserByHash($receiver_hash);
			if($receiver_obj->id == -1)
			{
				$receiver_obj = $System->UserHandler->create($receiver);
				$displayname = substr($receiver,0,4)."...";
				$receiver_obj->setDisplay($displayname);
			}
			elseif(!$receiver_obj->email)
			{
				$receiver_obj->updateEmail($receiver);
			}
			// Now that we've got a giver and a receiver... Add the Karma!
			
			$karma_id = $receiver_obj->addKarma($giver_obj->id,$client_obj->id,$adjective,$reason,2);
			if($karma_id === false)
			{
				$tpl_errors[] = "There was an error adding the karma.";
			}
			else
			{
				$_SESSION["just_gave"] = true;
				error_reporting(E_ALL);
				// Send an email to the receiver
				$email = new FauxTemplate;
				$email->sender = $giver_obj->display;
				$email->link = "http://karma.dev.blackglasses.net/postcard/{$karma_id}";
				$email->profile_link = "http://karma.dev.blackglasses.net/user/{$receiver_obj->id}";
				$email->sender_profile = "http://karma.dev.blackglasses.net/user/{$giver_obj->id}";
				
				$email_headers = "From: {$giver_obj->email}\r\n";
				$email_headers.= "Content-Type: text/html;charset=utf-8\r\n";

				$send_mail = mail($receiver_obj->email,"{$giver_obj->display} gave you a compliment!",$email->get_content(dirname(__FILE__)."/../_inc/email/addedKarma.php"),$email_headers);

				session_write_close();
				session_regenerate_id();
				$System->REST->location("/user/{$giver_obj->id}");
				die();
			}

		}
	}


	function validate_email($email)
	{
		$email_parts = explode("@",$email);
		if(count($email_parts) != 2) { return FALSE; } // You can only have one @ in an email address.
		$domain = $email_parts[1];
		if(!getmxrr($domain,$array)) { return FALSE; } // This domain doesn't have any MX Records.
		return TRUE; // Everything else is 'valid.'
	}

	class FauxTemplate
	{
		public function get_content($file)
		{
			ob_start();
			include($file);
			$data = ob_get_contents();
			ob_end_clean();
			return $data;
		}
	}

	if($System->REST->request[1])
	{
		$DISPLAY = true;
		$karma = $System->KarmaHandler->getKarmaById($System->REST->request[1]);
		$giver = $System->UserHandler->getUserById($karma["submitted_uid"]);
		$receiver = $System->UserHandler->getUserById($karma["referenced_uid"]);
		$con = $System->ClientHandler->getClientById($karma["connection_id"]);
	} else { $DISPLAY = false; }

?>
<!DOCTYPE html>
<html>
<head>
<title>Send a Virtual Compliment!</title>
<style type="text/css">
  body { font-family: sans-serif; text-align:center; }
  div.line { border-bottom:1px solid #A9A9A9; text-indent: 0em; margin-top: 10px; }
  div.line-noline { text-indent: 0em; margin-top: 10px; }
  #postcard { display: inline-block;text-align:left; }
  div.line.indent { text-indent: 3em; }
  input.send { color: black; }
  input,select { border: 1px solid #E0E0E0; border-width: 1px 0 0 1px; color: black; background-color: #D0FFD0; }
  input[type="submit"],input[disabled] { border-width: 1px; background-color: white; }
  input[disabled] { border-width: 0px; }
  input::-webkit-input-placeholder { color: #909090; }
  .right { text-align: right; }
</style>
</head>
<body>
<?php if(!$DISPLAY): ?><h1>Send a thank you postcard</h1><?php else: ?><h1>You received a thank you postcard!</h1><?php endif; ?>
<form method="post"><div id="postcard" style="height:350px;width:540px;border:5px solid black;padding:10px;position:relative;">
  <div id="stamp" style="position:absolute;top:10px;right:10px;width:75px;height:75px;border:3px solid black;"></div>
  <div id="left" style="display:inline-block;width:267px;height:100%;border-right:3px solid #A9A9A9;float:left;position:relative;">
    <div id="form" style="position:absolute;top:20px;right:0px;right:10px;left:5px;">
      <div class="line-noline">Dear <input <?php if($DISPLAY): ?>disabled value="<?= $receiver->email ?>"<?php endif; ?> type="email" name="receiver" id="theirEmail" placeholder="friend's email" onblur="document.getElementById('toEmail').value=this.value;addStamp();" >,</div>
      <div class="line-noline">&nbsp;</div>
      <div class="line">You are <select name="adjective" <?php if($DISPLAY): ?>disabled<?php endif; ?>>
				<?php if($DISPLAY): ?><option><?= $karma["adjective"] ?></option><?php else: ?>
				<option value="awesome">Awesome</option>
				<option value="generous">Generous</option>
				<option value="green">Green</option>
				<option value="responsible">Responsible</option>
				<option value="courteous">Courteous</option>
			<?php endif; ?></select> because</div>
      <div class="line"><input type="text" <?php if($DISPLAY): ?>disabled value="<?= str_replace("they","you",$karma["reason"]) ?>" <?php endif; ?>name="reason" placeholder="you helped me in the real world." style="width:100%;"></div>
      <div class="line">on <select name="connection" <?php if($DISPLAY): ?>disabled<?php endif; ?>>
				<?php if($DISPLAY): ?><option><?= $con->name ?></option><?php else: ?>
				<option value="4">Choose a Site</option>
				<?php foreach($System->ClientHandler->getClientList() as $client): if($client->id != 4): ?>
					<option value="<?= $client->id ?>"><?= $client->name ?></option>
				<?php endif; endforeach; ?>
				<option value="4">other</option>
			<?php endif; ?></select></div>
      <div class="line">&nbsp;</div>
      <div class="line-noline right">Sincerely,</div>
      <div class="line-noline right"><input <?php if($DISPLAY): ?>disabled value="<?= $giver->email ?>"<?php endif; ?> type="email" name="giver" id="myEmail" placeholder="Your Email" onblur="addStamp();"></div>
    </div>
  </div>
  <div id="right" style="display:inline-block;width:247px;height:100%;padding-left:20px;float:left;position:relative;">
   <div id="toField" style="position:absolute;top:150px;right:20px;left:20px;text-align: center;">
    To: <input type="email" disabled id="toEmail" onclick="document.getElementById('theirEmail').focus();" />
    <br /><br />
    <?php if(!$DISPLAY): ?><input type="submit" value="Send Compliment!" name="submit" /><?php endif; ?>
   </div>
   <div id="submit" style="position:absolute;bottom:0px;right:20px;left:20px;text-align:center;">
   </div>
  </div>
</div></form>
<h3 style="font-style:italic;">
<?php if(!$DISPLAY): ?>because we still need manners in the sharing economy
<?php else: ?><a href="/postcard">Now go out there and thank someone else!</a>
<?php endif; ?></h3>
<script src="./md5-min.js" type="text/javascript"></script>
<script type="text/javascript">
  function addStamp()
  {
	var hash = hex_md5(document.getElementById("theirEmail").value.toLowerCase());
	var url = "http://www.gravatar.com/avatar/" + hash + "?s=75";
       document.getElementById("stamp").style.backgroundImage = "url('" + url + "')";
  }
  <?php if($DISPLAY): ?>document.getElementById('toEmail').value=document.getElementById('theirEmail').value;<?php endif; ?>
  addStamp();
</script>
</body>
</html>