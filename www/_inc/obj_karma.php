<?php
	class KarmaHandler
	{
		// Quick! Throw me together!

		private $System;

		function __construct($system)
		{
			$this->System = $system;
		}
		function getLeaders($top = 5,$type = "MostKarma")
		{
			$type = strtolower($type);
			if($type == "mostgiven")
			{
				return $this->getMostGivenLeaders($top);
			}
			else if($type == "mostgot")
			{
				return $this->getMostReceivedLeaders($top);
			}
			else if($type == "chain")
			{
				return $this->getLongestChainLeaders($top);
			}
			else
			{
				return $this->getMostKarmaLeaders($top);
			}
		}
		function getMostGivenLeaders($top = 5)
		{
			$q = $this->System->Database->prepare("SELECT *,(SELECT SUM(1) FROM `karma` WHERE `submitted_uid`=`users`.`id` AND `referenced_uid` != `submitted_uid`) AS `given_count` FROM `users` ORDER BY `given_count` DESC LIMIT ?");
			$q->bindParam(1,$top,PDO::PARAM_INT);
			$q->setFetchMode(PDO::FETCH_ASSOC);
			$q->execute();
			while($r = $q->fetch())
			{
				$i = count($leaders);
				$leaders[$i] = $this->System->UserHandler->getUserById($r["id"]);
				$leaders[$i]->givenCount = $r["given_count"];
			}
			return $leaders;
		}
		function getMostKarmaLeaders($top = 5)
		{
			$q = $this->System->Database->prepare("
				SELECT *,
				(
					IF((SELECT SUM(`karma`) FROM `karma` WHERE `referenced_uid`=`users`.`id` AND `referenced_uid` != `submitted_uid`),(SELECT SUM(`karma`) FROM `karma` WHERE `referenced_uid`=`users`.`id` AND `referenced_uid` != `submitted_uid`),0)
					+
					IF((SELECT SUM(1) FROM `karma` WHERE `submitted_uid`=`users`.`id` AND `referenced_uid` != `submitted_uid`),(SELECT SUM(1) FROM `karma` WHERE `submitted_uid`=`users`.`id` AND `referenced_uid` != `submitted_uid`),0)
				) AS `karma_count` 
				FROM `users` 
				ORDER BY `karma_count` 
				DESC LIMIT ?
			");
			$q->bindParam(1,$top,PDO::PARAM_INT);
			$q->setFetchMode(PDO::FETCH_ASSOC);
			$q->execute();
			$leaders = array();
			while($r = $q->fetch())
			{
				$i = count($leaders);
				$leaders[$i] = $this->System->UserHandler->getUserById($r["id"]);
				$leaders[$i]->getKarmaCount();
//				$leaders[$i]->karmaCount = $r["karma_count"];
			}
			return $leaders;
		}
		function getMostReceivedLeaders($top = 5)
		{
			$q = $this->System->Database->prepare("SELECT *,(SELECT SUM(1) FROM `karma` WHERE `referenced_uid`=`users`.`id` AND `submitted_uid` != 0 AND `referenced_uid` != `submitted_uid`) AS `received_count` FROM `users` ORDER BY `received_count` DESC LIMIT ?");
			$q->bindParam(1,$top,PDO::PARAM_INT);
			$q->setFetchMode(PDO::FETCH_ASSOC);
			$q->execute();
			$leaders = array();
			while($r = $q->fetch())
			{
				$i = count($leaders);
				$leaders[$i] = $this->System->UserHandler->getUserById($r["id"]);
				$leaders[$i]->receivedCount = $r["received_count"];
			}
			return $leaders;
		}
		function getLongestChainLeaders($top = 5)
		{
			$q = $this->System->Database->prepare("SELECT * FROM `users` ORDER BY `longest_chain` DESC LIMIT ?");
			$q-> bindParam(1,$top,PDO::PARAM_INT);
			$q-> setFetchMode(PDO::FETCH_ASSOC);
			$q-> execute();
			$leaders = array();
			while($r = $q->fetch())
			{
				$i++;
				$leaders[$i] = $this->System->UserHandler->getUserById($r["id"]);
			}
			return $leaders;
		}

		function getKarmaById($id)
		{
			$q = $this->System->Database->prepare("SELECT * FROM `karma` WHERE `id`=? LIMIT 1");
			$q->bindParam(1,$id,PDO::PARAM_INT);
			$q->setFetchMode(PDO::FETCH_ASSOC);
			$q->execute();
			return $q->fetch();
		}
	}