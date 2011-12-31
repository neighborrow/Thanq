<?php
	$code = $System->REST->request[1];
	
	if($code)
	{
		$System->UserHandler->verifyCode($code);
	}
	if($System->REST->post["submit"] && $System->REST->post["email"] && ($System->REST->post["password"] && $System->REST->post["password2"] && $System->REST->post["password"] == $System->REST->post["password2"]))
	{
		// Do Stuff
		$user = $System->UserHandler->getUserByHash($System->UserHandler->emailToHash($System->REST->post["email"]));
		if(!$user->id || !$user->verified)
		{
			$System->UserHandler->generateVerification($System->REST->post["email"]);
			$user = $System->UserHandler->getUserByHash($System->UserHandler->emailToHash($System->REST->post["email"]));
			if($System->REST->post["display"])
				$user-> setDisplay($System->REST->post["display"]);
			$user-> setPassword($System->REST->post["password"]);

			echo "Verification Email Sent";
			die();
		}
	}
?>
<h1>Verify Account</h1>
<fieldset><form method="post">
	<table>
		<tr>
			<th class="req">Email Address</th>
			<td><input type="email" name="email" placeholder="your-email@domain.tld" /></td>
		</tr>
		<tr>
			<th valign="top">Display Name</th>
			<td><input type="text" name="display" placeholder="John S." /><br /><em>The name you wish to show the world on your profile.  We recommend the format John S.</em></td>
		</tr>
		<tr>
			<th valign="top" class="req">Password</th>
			<td><input type="password" name="password" /><br />Login System coming soon!</td>
		</tr>
		<tr>
			<th class="req">Confirm Password</th>
			<td><input type="password" name="password2" /></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" value="Verify My Email" name="submit" /></td>
		</tr>
	</table>
</form></fieldset>
<style type="text/css">
	body { font-family: sans-serif; }
	th { text-align: right; }
	th:after { content:" "; }
	th.req:after { content:"*"; color: red; }
</style>