<?php

class email
{
	protected $core;
	protected $headers;
	protected $to;
	protected $cc;
	protected $bcc;
	public $tpl;
	protected $file;
	
	public $subject;
	
	public function __construct(&$core,$file = false)
	{
		$this->core = $core;
		$this->headers = array();
		$this->to = array();
		$this->cc = array();
		$this->bcc = array();
		$core->load_template_handler();
		$this->tpl = new template($core);
		$this->tpl->_dir = "{$core->fs_root}/templates/email/";
		$this->file = $file;
	}
	
	public function add_header($header,$content)
	{
		if(!$this->headers) $this->headers = array();
		$this->headers[$header] = $content;
	}
	
	public function add_to($to,$name)
	{
		$this->to[] = array($to,$name);
	}
	
	public function add_cc($to,$name)
	{
		$this->cc[] = array($to,$name);
	}
	
	public function add_bcc($to,$name)
	{
		$this->bcc[] = array($to,$name);
	}
	
	public function send()
	{
		// Generate To Headers
		$to = array();
		if(count($this->to))
		{
			foreach($this->to as $receiver)
			{
				$to[] = "'{$receiver[1]}' <{$receiver[0]}>";
			}
		}
		
		// Generate CC Headers
		if(count($this->cc))
		{
			$cc_header = array();
			foreach($this->cc as $receiver)
			{
				$to[] = $receiver[0];
				$cc_header[] = "'{$receiver[1]}' <{$receiver[0]}>";
			}
			$this->add_header("cc",implode(", ",$cc_header));
		}
		
		// Generate BCC Headers
		if(count($this->bcc))
		{
			$bcc_header = array();
			foreach($this->bcc as $receiver)
			{
				$to[] = $receiver[0];
				$bcc_header[] = "'{$receiver[1]}' <{$receiver[0]}>";
			}
			$this->add_header("bcc",implode(", ",$bcc_header));
		}
		
		// Other Important Headers
		$this->add_header("From",$this->from);
		$this->add_header("MIME-Version","1.0");
		$this->add_header("Content-type","text/html;charset=utf-8");
		
		// Generate all the Headers Now
		$headers = "";
		foreach($this->headers as $name => $content)
		{
			$headers .= "{$name}: {$content}\n";
		}
		$headers = substr($headers,0,-1); // Remove the last \n
		$this->tpl->subject = $this->subject;
		$this->tpl->_content = $this->tpl->fetch($this->file);
		$body = $this->tpl->fetch($this->tpl->_wrapper);
		
		return mail(implode(", ",$to),$this->subject,$body,$headers);
	}
	
	public function send_plain($text)
	{
		
	}
}