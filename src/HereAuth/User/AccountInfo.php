<?php

/*
 * HereAuth
 *
 * Copyright (C) 2016 PEMapModder
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PEMapModder
 */

namespace HereAuth\User;

use HereAuth\HereAuth;
use pocketmine\Player;

class AccountInfo implements \Serializable{
	/** @type string */
	public $name;
	/** @type string */
	public $passwordHash;
	/** @type int */
	public $registerTime;
	/** @type int */
	public $lastLogin;
	/** @type string */
	public $lastIp;
	/** @type string */
	public $lastSecret;
	/** @type string */
	public $lastUuid;
	/** @type string */
	public $lastSkin;
	/** @type AccountOpts */
	public $opts;
	/** @type string[] */
	public $multiHash;

	public function testPassword(HereAuth $main, $password){
		$hash = HereAuth::hash($password, $this->name);
		if(strlen($this->passwordHash) === strlen($hash)){
			return $hash === $this->passwordHash;
		}
		foreach($this->multiHash as $type => $value){
			$array = explode(";", $type);
			$name = $array[0];
			$suffix = isset($array[1]) ? $array[1] : "";
			$iHash = $main->getImportedHash($name);
			if($iHash === null){
				continue;
			}
			if($iHash->hash($password, strtolower($this->name), $suffix) === $value){
				$this->multiHash = [];
				$this->passwordHash = $hash;
				return true;
			}
		}
		return false;
	}

	public static function defaultInstance(HereAuth $main, Player $player){
		$info = new self;
		$info->name = strtolower($player->getName());
		$info->passwordHash = "";
		$info->registerTime = -1;
		$info->lastLogin = -1;
		$info->lastIp = null;
		$info->lastSecret = null;
		$info->lastUuid = null;
		$info->lastSkin = null;
		$info->opts = AccountOpts::defaultInstance($main);
		return $info;
	}

	public function serialize(){
		$output = json_encode([
			"name" => $this->name,
			"passwordHash" => base64_encode($this->passwordHash),
			"registerTime" => $this->registerTime,
			"lastLogin" => $this->lastLogin,
			"lastIp" => $this->lastIp,
			"lastSecret" => base64_encode($this->lastSecret),
			"lastUuid" => base64_encode($this->lastUuid),
			"lastSkin" => base64_encode($this->lastSkin),
			"opts" => $this->opts,
			"multiHash" => $this->multiHash,
		]);
		return $output;
	}

	public function unserialize($string){
		$data = json_decode($string);
		$this->name = $data->name;
		$this->passwordHash = base64_decode($data->passwordHash);
		$this->registerTime = $data->registerTime;
		$this->lastLogin = $data->lastLogin;
		$this->lastIp = $data->lastIp;
		$this->lastSecret = base64_decode($data->lastSecret);
		$this->lastUuid = base64_decode($data->lastUuid);
		$this->lastSkin = base64_decode($data->lastSkin);
		$this->opts = (new AccountOpts)->extractObject($data->opts);
		$this->multiHash = $data->multiHash;
	}

	/**
	 * @param string   $tableName
	 * @param callable $escapeFunc a callable that escapes the string and <b>adds quotes around it</b>
	 *
	 * @return string
	 */
	public function getDatabaseQuery($tableName, callable $escapeFunc){
		$name = $escapeFunc($this->name);
		$hash = $this->binEscape($this->passwordHash);
		$lastIp = $escapeFunc($this->lastIp);
		$lastSecret = $this->binEscape($this->lastSecret);
		$lastUuid = $this->binEscape($this->lastUuid);
		$lastSkin = $this->binEscape(zlib_encode($this->lastSkin, ZLIB_ENCODING_DEFLATE));
		$opts = $escapeFunc(serialize($this->opts));
		$multiHash = $escapeFunc(json_encode($this->multiHash));
		return ("INSERT INTO `$tableName` (name, hash, register, login, ip, secret, uuid, skin, opts, multihash) VALUES " . "(" .
			"$name, $hash, $this->registerTime, $this->lastLogin, $lastIp, $lastSecret, $lastUuid, $lastSkin, $opts, $multiHash)");
	}

	public static function fromDatabaseRow($row){
		$info = new self;
		$info->name = $row["name"];
		$info->passwordHash = $row["hash"];
		$info->registerTime = (int) $row["register"];
		$info->lastLogin = (int) $row["login"];
		$info->lastIp = $row["ip"];
		$info->lastSecret = $row["secret"];
		$info->lastUuid = $row["uuid"];
		$info->lastSkin = $row["skin"];
		$info->opts = unserialize($row["opts"]);
		$info->multiHash = json_decode($row["multihash"], true);
		return $info;
	}

	private function binEscape($str){
		return "X'" . bin2hex($str) . "'";
	}
}
