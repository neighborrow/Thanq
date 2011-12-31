<?php
class utilities
{
	/* Validate Email
	 * 
	 * Returns an Array.
	 * array
	 * (
	 * 	valid: BOOL
	 *  errstr: Array of Strings
	 * )
	 */
	public static function validate_email($email)
	{
		$return = array
		(
			"valid" => true,
			"errstr" => array()
		);
		$email_parts = explode("@",$email);
		if(count($email_parts) != 2)
		{
			$return["valid"] = false;
			$return["errstr"][] = "Too many or too few email parts.";
		}
		$domain = $email_parts[1];
		if(!getmxrr($domain,$array))
		{
			$return["valid"] = false;
			$return["errstr"][] = "This domain name is not accepting email.";
		}
		
		return $return;
	}
	
	/* Validate Twitter - Validates a Twitter Handle
	 * 
	 * Returns an Array.
	 * array
	 * (
	 * 	valid: BOOL
	 * 	errstr: Array of Strings
	 * )
	 */
	public static function validate_twitter($handle)
	{
		$enc_handle = urlencode($handle);
		$url = "http://api.twitter.com/1/users/show.json?screen_name={$enc_handle}&skip_status=1";
		$data = file_get_contents($url);
		$json = json_decode($data,true);
		$return = array("valid" => true,"errstr" => array());
		if(isset($json["error"]))
		{
			$return["valid"] = false;
			$return["errstr"] = $json["error"];
		}
		if(!strlen($data))
		{
			$return["valid"] = false;
			$return["errstr"] = "No Data From URL - Maybe twitter is down?";
		}
		
		return $return;
	}
}