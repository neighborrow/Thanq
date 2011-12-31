<?php
	class ClientHandler
	{
		private $System;
		public $ids;

		public function __construct(&$System)
		{
			$this->System = &$System;
		}

		public function getClientById($id)
		{
			if(!isset($this->ids[$id]))
			{
				$temp = new Client($this->System);
				$temp->loadFromId($id);
				$this->ids[$temp->id] = &$temp;
				return $this->ids[$temp->id];
			} else {
				return $this->ids[$id];
			}
		}
		public function getClientList()
		{
			$STH = $this->System->Database->prepare("SELECT * FROM `clients` WHERE `id` != 4 ORDER BY `name` ASC");
			$STH->execute();
			$STH->setFetchMode(PDO::FETCH_CLASS,"Client",array(&$System));
			$list = array();
			while($r = &$STH->fetch())
			{
				$this->ids[$r->id] = $r;
			}
			return $this->ids;
		}
	}

	class Client
	{
		private $System;
		public $id = -1;
	
		function __construct(&$System)
		{
			$this->System = &$System;
		}

		public function loadFromId($id)
		{
			$STH = $this->System->Database->prepare("SELECT * FROM `clients` WHERE `id`=? LIMIT 1");
			$STH->bindParam(1,$id,PDO::PARAM_INT);
			$STH->setFetchMode(PDO::FETCH_INTO,$this);
			$STH->execute();
			$STH->fetch();
		}
	}