<?php
	class System
	{
		public $Database;
		public $UserHandler;
		public $ClientHandler;
		public $REST;

		function __construct(&$DBH)
		{
			$this->REST = new REST;
			$this->Database = &$DBH;
			$this->UserHandler = new UserHandler($this);
			$this->ClientHandler = new ClientHandler($this);
			$this->KarmaHandler = new KarmaHandler($this);
		}
	}