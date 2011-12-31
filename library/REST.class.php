<?php
class REST
{
	protected $get = false;
	protected $method;
	protected $request;
	protected $post = false;
	protected $extension;
	protected $ext;
	
	public function __construct()
	{
		$this->method = $_SERVER["REQUEST_METHOD"];
		$request = explode("?",substr($_SERVER["REQUEST_URI"],1));
		$request[0] = preg_replace("@([/]+)@","/",$request[0]);
		$this->request = explode("/",$request[0]);
		if(count($_POST)) { foreach($_POST as $key => $val) {
			$this->post[$key] = $val;
		} }
		if(count($_GET)) { foreach($_GET as $key => $val) {
			$this->get[$key] = $val;
		}}
	}
	
	public function location($uri,$code = 302,$die = true)
	{
		header("Location: {$uri}",true,$code);
		if($die)
			{ die(); }
	}
	
	public function status($code,$phrase = null)
	{
		if($phrase == null) switch($code)
		{
			case 100: $phrase = "Continue";break;
			case 101: $phrase = "Switching Protocols";break;
			case 102: $phrase = "Processing";break;
			case 200: $phrase = "OK";break;
			case 201: $phrase = "Created";break;
			case 202: $phrase = "Accepted";break;
			case 203: $phrase = "Non-Authoritative Information";break;
			case 204: $phrase = "No Content";break;
			case 205: $phrase = "Reset Content";break;
			case 206: $phrase = "Partial Content";break;
			case 207: $phrase = "Multi-Status";break;
			case 300: $phrase = "Multiple Choices";break;
			case 301: $phrase = "Moved Permanently";break;
			case 302: $phrase = "Found";break;
			case 303: $phrase = "See Other";break;
			case 304: $phrase = "Not Modified";break;
			case 305: $phrase = "Use Proxy";break;
			case 306: $phrase = "Switch Proxy";break;
			case 307: $phrase = "Temporary Redirect";break;
			case 400: $phrase = "Bad Request";break;
			case 401: $phrase = "Unauthorized";break;
			case 402: $phrase = "Payment Required";break;
			case 403: $phrase = "Forbidden";break;
			case 404: $phrase = "Not Found";break;
			case 405: $phrase = "Method Not Allowed";break;
			case 406: $phrase = "Not acceptable";break;
			case 407: $phrase = "Proxy Authentication Required";break;
			case 408: $phrase = "Request Timeout";break;
			case 409: $phrase = "Conflict";break;
			case 410: $phrase = "Gone";break;
			case 411: $phrase = "Length Required";break;
			case 412: $phrase = "Precondition Failed";break;
			case 413: $phrase = "Request Entity Too Large";break;
			case 414: $phrase = "Request-URI Too Long";break;
			case 415: $phrase = "Unsupported Media Type";break;
			case 416: $phrase = "Requested Range Not Satisfiable";break;
			case 417: $phrase = "Expectation Failed";break;
			case 418: $phrase = "I'm A Teapot";break;
			case 422: $phrase = "Unprocessable Entity";break;
			case 423: $phrase = "Locked";break;
			case 424: $phrase = "Failed Dependency";break;
			case 425: $phrase = "Unordered Collection";break;
			case 426: $phrase = "Upgrade Required";break;
			case 500: $phrase = "Internal Server Error";break;
			case 501: $phrase = "Not Implemented";break;
			case 502: $phrase = "Bad Gateway";break;
			case 503: $phrase = "Service Unavailable";break;
			case 504: $phrase = "Gateway Timeout";break;
			case 505: $phrase = "HTTP Version Not Supported";break;
			case 506: $phrase = "Variant Also Negotiates";break;
			case 507: $phrase = "Insufficient Storage";break;
			case 509: $phrase = "Bandwidth Limit Exceeded";break;
			case 510: $phrase = "Not Extended";break;
		}
		$this->header("Status","{$code} {$phrase}");
	}
	
	public function json($array,$die = true)
	{
		$this->header("Content-Type","application/json");
		if(isset($_REQUEST["callback"]))
		{
			$this->header("Content-Type","text/javascript");
			echo($_REQUEST["callback"]."(");
		}
		echo(json_encode($array));
		if(isset($_REQUEST["callback"]))
		{
			echo(");");
		}
		if($die) die();
		return true;
	}

	public function header($header,$content)
	{
		header("{$header}: {$content}");
	}
	
	public function get($var,$default = null)
	{
		if(isset($this->get[$var]))
			return $this->get[$var];
		else
			return $default;
	}
	
	public function post($var,$default = null)
	{
		if(isset($this->post[$var]))
			return $this->post[$var];
		else
			return $default;
	}

	public function &__get($var)
	{
		return $this->{$var};
	}
}