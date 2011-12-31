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

?>
<!DOCTYPE html>
<title>Send a Virtual Compliment!</title>
<h1>REAL. GOOD. PEOPLE.</h1>
<hr />
<span style="color:red;">
Send a <em>virtual</em> thank you, compliment, or acknowledgement<br /> for a <em>real world</em> deed.
<a href="http://share-the-oxytocin.blogspot.com/">why?</a> <a href="http://share-the-oxytocin.blogspot.com/2010/12/positive-feedback-loop.html">rules.</a>
</span><br /><br />
<form method="post">
	<table style="float:left;">
		<?php if(count($tpl_errors)): ?>
		<tr>
			<th>
				<?php foreach($tpl_errors as $error): ?>
					<?= $error ?><br />
				<?php endforeach; ?>
			</th>
		</tr>
		<?php endif; ?>
		<tr>
			<th>Dear</th>
			<td><input type="email" name="receiver" placeholder="Their Email" />, </td>
		</tr>
		<tr>
			<th>You are </th>
			<td><select name="adjective">
				<option value="awesome">Awesome</option>
				<option value="generous">Generous</option>
				<option value="green">Green</option>
				<option value="responsible">Responsible</option>
				<option value="courteous">Courteous</option>
			</select></td>
		</tr>
		<tr>
			<th>because</th>
			<td><input type="text" name="reason" placeholder="they helped me out in a pinch!" /></td>
		</tr>
		<tr>
			<th>on</th>
			<td><select name="connection">
				<option value="4">Choose a Site</option>
				<?php foreach($System->ClientHandler->getClientList() as $client): if($client->id != 4): ?>
					<option value="<?= $client->id ?>"><?= $client->name ?></option>
				<?php endif; endforeach; ?>
				<option value="4">other</option>
			</select></td>
		<tr>
			<th>From:</th>
			<td><input type="email" name="giver" placeholder="Your Email" /></td>
		</tr>
		<tr>
			<th colspan="2"><input type="submit" value="Give Karma!" name="submit" /></th>
		</tr>
	</table>
</form>
	<div style="float:left;padding:0 20px 0 20px;font-size:72pt;">
		&rarr;
	</div>
	<div style="float:left;margin-left:10px;padding:10px;border:2px solid black;">
<?php
	$leaders1 = $System->KarmaHandler->getLeaders(5);
	$leaders2 = $System->KarmaHandler->getLeaders(5,"mostgiven");
	$leaders3 = $System->KarmaHandler->getLeaders(5,"mostgot");
	$leaders4 = $System->KarmaHandler->getLeaders(5,"chain");
?>
<center><strong style="text-align:center;display:inline-block;">Leaderboards (<a href="http://share-the-oxytocin.blogspot.com/2010/12/it-pays-to-be-good.html">about</a>)<br />Real(ly). Great. People.</strong></center>
<ol style="float:left;">
	<strong>Overall</strong>
<?php foreach($leaders1 as $user): ?>
	<li><a href="/user/<?= $user->id ?>"><?= $user->display ?></a> (<?= $user->getKarmaCountDisplay() ?>)</li>
<?php endforeach; ?>
</ol>
<ol style="float:left;padding-left:30px;">
	<strong>Givers</strong>
<?php foreach($leaders2 as $user): ?>
	<li><a href="/user/<?= $user->id ?>"><?= $user->display ?></a> (<?= $user->givenCount ?>)</li>
<?php endforeach; ?>
</ol>
<ol style="float:left;padding-left:30px;">
	<strong>Recipients</strong>
<?php foreach($leaders3 as $user): ?>
	<li><a href="/user/<?= $user->id ?>"><?= $user->display ?></a> (<?= $user->receivedCount ?>)</li>
<?php endforeach; ?>
</ol>
<ol style="float:left;padding-left:30px;">
	<strong>Longest Chain</strong>
<?php foreach($leaders4 as $user): ?>
	<li><a href="/user/<?= $user->id ?>"><?= $user->display ?></a> (<?= $user->longest_chain ?>)</li>
<?php endforeach; ?>
</ol>
	<br style="clear:both;" />
	</div>
<br style="clear:both;" /><br /><hr /><br />
(<span style="color:grey;">Privacy strictly respected.  Email addresses will never be sold or published. Feedback is aggegated into a profile and used for leaderboards and syndicated to other social networks as a tool for trusting.</span>)
<style type="text/css">
	th { text-align: right; }
	body { font-family: sans-serif; }
	li { line-height: 150%; }
</style>