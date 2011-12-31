<?php
	class UserHandler
	{
		private $System;
		public $ids;
		public $hashes;

		public function __construct(&$System)
		{
			$this->System = &$System;
			$this->ids[0] = new User($System);
			$this->ids[0]->id = 0;
			$this->ids[0]->display = "System";
		}
		public function &getUserByHash($hash)
		{
			if(!isset($this->hashes[$hash]))
			{
				$temp = new User($this->System);
				$temp->loadFromHash($hash);
				$this->ids[$temp->id] = &$temp;
				$this->hashes[$temp->hash] = &$this->ids[$temp->id];
				return $this->ids[$temp->id];
			} else {
				return $this->hashes[$hash];
			}
		}
		public function &getUserById($id)
		{
			if(!isset($this->ids[$id]))
			{
				$temp = new User($this->System);
				$temp->loadFromId($id);
				$this->ids[$temp->id] = &$temp;
				$this->hashes[$temp->hash] = &$this->ids[$temp->id];
				return $this->ids[$temp->id];
			} else {
				return $this->ids[$id];
			}
		}
		// /*
		public function &create($email)
		{
			$email = trim(strtolower($email));
			$user = new User($this->System);
			$hash = $this->emailToHash($email);
			$user->createFromEmail($email);
			$this->ids[$user->id] = &$user;
			$this->hashes[$hash] = &$this->ids[$user->id];
			return $this->ids[$user->id];
		}
		/*
		public function &create($hash)
		{
			$user = new User($this->System);
			$user->createFromHash($hash);
			$this->ids[$user->id] = &$user;
			$this->hashes[$hash] = &$this->ids[$user->id];
			return $this->ids[$user->id];
		}
		// */
		public function emailToHash($email)
		{
			$email = trim(strtolower($email));
			return sha1("mailto:{$email}");
		}
		public function generateVerification($email)
		{
			$user = $this->getUserByHash($this->emailToHash($email));
			if($user->id < 1)
			{
				$user = $this->create($email);
			}
			$user->updateEmail($email);
			$user->generateVerification();
		}
		public function verifyCode($code)
		{
			$q = $this->System->Database->prepare("SELECT * FROM `users` WHERE `verify_hash` IS NOT NULL AND `verify_hash`= ? LIMIT 1");
			$q-> bindParam(1,$code,PDO::PARAM_STR,40);
			$q-> setFetchMode(PDO::FETCH_ASSOC);
			$q-> execute();
			$r = $q->fetch();

			if($r["id"])
			{
				$q = $this->System->Database->prepare("UPDATE `users` SET `verified`=1,`verify_hash`=NULL WHERE `verify_hash` IS NOT NULL AND `verify_hash` = ?");
				$q-> bindParam(1,$code,PDO::PARAM_STR,40);
				$q-> execute();

				$this->System->REST->location("/user/{$r["id"]}");
			} else {
				$this->System->REST->location("/");
			}
		}
	}
	class User
	{
		private $System;
		private $karmaCount;
		public $id = -1;
		function __construct(&$System)
		{
			$this->System = &$System;
			$this->display = htmlspecialchars($this->display);
			$this->gravatar = "http://www.gravatar.com/avatar/".md5(strtolower(trim($this->email)))."?d=mm&r=pg&s=50";
		}
		function loadFromHash($hash)
		{
			$STH = $this->System->Database->prepare("SELECT *,MD5(`email`) AS `gravatar` FROM `users` WHERE `hash`=? LIMIT 1");
			$STH->bindParam(1,$hash,PDO::PARAM_STR,40);
			$STH->setFetchMode(PDO::FETCH_INTO,$this);
			$STH->execute();
			$STH->fetch();
			$this->__construct($this->System);
		}
		function loadFromId($id)
		{
			$STH = $this->System->Database->prepare("SELECT *,MD5(`email`) AS `gravatar` FROM `users` WHERE `id`=? LIMIT 1");
			$STH->bindParam(1,$id,PDO::PARAM_INT);
			$STH->setFetchMode(PDO::FETCH_INTO,$this);
			$STH->execute();
			$STH->fetch();
			$this->__construct($this->System);
		}
		function createFromEmail($email)
		{
			$hash = $this->System->UserHandler->emailToHash($email);
			$STH = $this->System->Database->prepare("INSERT INTO `users` (`email`,`hash`) VALUES (?,?)");
			$STH->bindParam(1,$email,PDO::PARAM_STR,320);
			$STH->bindParam(2,$hash,PDO::PARAM_STR,40);
			$STH->execute();
			$this->loadFromHash($hash);
		}
		function updateEmail($email)
		{
			$STH = $this->System->Database->prepare("UPDATE `users` SET `email`=? WHERE `id`=?");
			$STH->bindParam(1,$email,PDO::PARAM_STR,320);
			$STH->bindParam(2,$this->id,PDO::PARAM_INT);
			$STH->execute();
			$this->loadFromHash($hash);
		}
		function createFromHash($hash)
		{
			$STH = $this->System->Database->prepare("INSERT INTO `users` (`hash`) VALUES (?)");
			$STH->bindParam(1,$hash,PDO::PARAM_STR,40);
			$STH->execute();
			$this->loadFromHash($hash);
		}
		function getKarmaCount()
		{
			if($this->karmaCount !== null)
			{
				return $this->karmaCount;
			} else {
				$this->karmaCount = 0;
				$STH = $this->System->Database->prepare("
					SELECT
						IF((SELECT SUM(`karma`) AS `karma` FROM `karma` WHERE `referenced_uid`=:user AND `referenced_uid` != `submitted_uid`),(SELECT SUM(`karma`) AS `karma` FROM `karma` WHERE `referenced_uid`=:user AND `referenced_uid` != `submitted_uid`),0)
						+
						IF((SELECT SUM(1) AS `karma` FROM `karma` WHERE `submitted_uid`=:user AND `referenced_uid` != `submitted_uid`),(SELECT SUM(1) AS `karma` FROM `karma` WHERE `submitted_uid`=:user AND `referenced_uid` != `submitted_uid`),0)
					 AS `karma`
				");
				$STH->bindParam(":user",$this->id,PDO::PARAM_INT);
				$STH->setFetchMode(PDO::FETCH_ASSOC);
				$STH->execute();
				$r = $STH->fetch();
				if($r["karma"]) $this->karmaCount = $r["karma"];
				return $this->karmaCount;
			}
		}
		function getKarmaCountDisplay()
		{
			$count = $this->getKarmaCount();
			if($count > 0)
				return "+{$count}";
			else
				return $count;
		}
		function getKarma($limit = 5,$offset = 0)
		{
			$STH = $this->System->Database->prepare("SELECT * FROM `karma` WHERE `referenced_uid`=:user OR `submitted_uid`=:user ORDER BY `id` DESC");
			$STH->bindParam(":user",$this->id,PDO::PARAM_INT);
			$STH->setFetchMode(PDO::FETCH_ASSOC);
			$STH->execute();
			while($karma = $STH->fetch())
			{
				if($karma["connection_id"])
				{
					$karma["connection"] = $this->System->ClientHandler->getClientById($karma["connection_id"]);
				//	$STH2 = $this->System->Database->prepare("SELECT * FROM `sites` WHERE `id`=?");
				//	$STH2->bindParam(1,$karma["connection_id"],PDO::PARAM_INT);
				//	$STH2->setFetchMode(PDO::FETCH_ASSOC);
				//	$STH2->execute();
				//	$karma["connection"] = $STH2->fetch();
				}
				$this->karma[$karma["id"]] = $karma;
				$this->System->UserHandler->getUserById($karma["submitted_uid"]);
				$this->System->UserHandler->getUserById($karma["referenced_uid"]);
			}
		}
		function addKarma($submitted_uid,$connection_id,$adj,$reason,$karma = 1)
		{
			$reason = trim($reason);
			if(substr($reason,0,3) == "you") { $reason = "they".substr($reason,3); }
			$STH = $this->System->Database->prepare("INSERT INTO `karma` (`referenced_uid`,`submitted_uid`,`connection_id`,`adjective`,`reason`,`karma`,`timestamp`) VALUES (?,?,?,?,?,?,UNIX_TIMESTAMP())");
			$STH->bindParam(1,$this->id);
			$STH->bindParam(2,$submitted_uid);
			$STH->bindParam(3,$connection_id);
			$STH->bindParam(4,$adj,PDO::PARAM_STR);
			$STH->bindParam(5,$reason,PDO::PARAM_STR);
			$STH->bindParam(6,$karma,PDO::PARAM_INT);
			$r = $STH->execute();
			$karma_id = $this->System->Database->lastInsertId();
			/* Chain Checking */
			if($submitted_uid != 0)
			{
				$receiver = &$this;
				$sender   = &$this->System->UserHandler->getUserById($submitted_uid);

				if(($sender->last_received + 60*60*24) <= time())
				{
					$STH = $this->System->Database->prepare("UPDATE `users` SET `last_sent`=UNIX_TIMESTAMP(),`current_chain`=`current_chain`+1,`longest_chain`=IF(`longest_chain`<`current_chain`,`current_chain`,`longest_chain`) WHERE `id`=?");
					$STH-> bindParam(1,$sender->id,PDO::PARAM_INT);
					$STH-> execute();
				} else {
					$STH = $this->System->Database->prepare("UPDATE `users` SET `last_sent`=UNIX_TIMESTAMP(),`current_chain`=1,`longest_chain`=IF(`longest_chain`<1,1,`longest_chain`) WHERE `id`=?");
					$STH-> bindParam(1,$sender->id,PDO::PARAM_INT);
					$STH-> execute();
				}

				if(($receiver->last_sent + 60*60*24) <= time())
				{
					$STH = $this->System->Database->prepare("UPDATE `users` SET `last_received`=UNIX_TIMESTAMP(),`current_chain`=`current_chain`+1,`longest_chain`=IF(`longest_chain`<`current_chain`,`current_chain`,`longest_chain`) WHERE `id`=?");
					$STH-> bindParam(1, $receiver->id,PDO::PARAM_INT);
					$STH-> execute();
				} else {
					$STH = $this->System->Database->prepare("UPDATE `users` SET `last_received`=UNIX_TIMESTAMP(),`current_chain`=1,`longest_chain`=IF(`longest_chain`<1,1,`longest_chain`) WHERE `id`=?");
					$STH-> bindParam(1, $receiver->id,PDO::PARAM_INT);
					$STH-> execute();
				}
					
			}
			if($r) return $karma_id;
			else return FALSE;
		}
		function setDisplay($display)
		{
			$STH = $this->System->Database->prepare("UPDATE `users` SET `display`=? WHERE `id`=?");
			$STH->bindParam(1,$display,PDO::PARAM_STR);
			$STH->bindParam(2,$this->id,PDO::PARAM_INT);
			$r = $STH->execute();
			return $r;
		}
		function setPassword($password)
		{
			$pass = sha1($password."karma");
			$q = $this->System->Database->prepare("UPDATE `users` SET `pass`=? WHERE `id`=?");
			$q-> bindParam(1,$pass,PDO::PARAM_STR,40);
			$q-> bindParam(2,$this->id,PDO::PARAM_INT);
			$r = $q->execute();
			return $r;
		}
		function generateVerification()
		{
			$verification = sha1(time()."-karma-".$this->email);
			$STH = $this->System->Database->prepare("UPDATE `users` SET `verify_hash`=? WHERE `id`=?");
			$STH-> bindParam(1,$verification,PDO::PARAM_STR,40);
			$STH-> bindParam(2,$this->id,PDO::PARAM_INT);
			$r = $STH->execute();

			if($r)
			{
				$subject = "Verify with Karma.Dev";
				$message = "A person has claimed this email as their own on Karma.Dev.BlackGlasses.net, if this was not you then you may safely disregard this message.  However, if this was you, please proceed to http://karma.dev.blackglasses.net/verify/{$verification} in order to confirm the ownership of this email.";
				$headers = "From: dev-noreply@blackglasses.net";
				$sent = mail($this->email,$subject,$message,$headers);
			}

			if($r && $sent)
				return true;
			else
				return false;

		}
	}