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

namespace HereAuth;

use HereAuth\Command\RegisterCommand;
use HereAuth\Database\Database;
use HereAuth\Database\Json\JsonDatabase;
use HereAuth\Database\MySQL\MySQLDatabase;
use HereAuth\Logger\AuditLogger;
use HereAuth\Logger\StreamAuditLogger;
use HereAuth\User\AccountInfo;
use HereAuth\User\User;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Utils;

class HereAuth extends PluginBase implements Listener{
	/** @type string */
	private static $NAME = "HereAuth";
	/** @var User[] */
	private $users = [];
	/** @type EventRouter */
	private $router;
	/** @type Database */
	private $database;
	/** @type AuditLogger */
	private $auditLogger;

	public function onLoad(){
		self::$NAME = $this->getName();
		if(!is_dir($this->getDataFolder())){
			mkdir($this->getDataFolder(), 0777, true);
		}
	}

	public function onEnable(){
		$new = false;
		$configPaths = [];
		if(!is_file($configPath = $this->getDataFolder() . "config.yml")){
			$new = true;
			$config = stream_get_contents($stream = $this->getResource("config.yml"));
			fclose($stream);
			$config = Utils::getOS() === "win" ?
				str_replace(["/dev/null", '${IS_WINDOWS}'], ["/NUL", "Windows"], $config) :
				str_replace('${IS_WINDOWS}', "non-Windows", $config);
			file_put_contents($configPath, $config);
			$configPaths[] = $configPath;
		}
		if(count($configPath) > 0){
			$action = $new ? "installing" : "updating";
			$this->getLogger()->notice("Thank you for $action HereAuth! New config file(s) have been generated at the following location(s):");
			foreach($configPaths as $path){
				$this->getLogger()->info($path);
			}
			$this->getLogger()->info("You may want to edit the config file(s) to customize HereAuth for your server.");
		}
		if(!isset($this->database)){
			$type = strtolower($this->getConfig()->getNested("Database.Type", "JSON"));
			if($type === "mysql"){
				$this->setDatabase(new MySQLDatabase($this));
			}else{
				if($type !== "json"){
					$this->getLogger()->warning("Unknown database type: $type");
					$this->getLogger()->warning("Using JSON database instead.");
				}
				$this->setDatabase(new JsonDatabase($this));
			}
		}
		$this->auditLogger = new StreamAuditLogger($this);
		$this->router = new EventRouter($this);
		$this->getServer()->getCommandMap()->registerAll("ha", [
			new RegisterCommand($this),
		]);
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->startUser($player);
		}
	}

	public function onDisable(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->closeUser($player);
		}
		$this->closeDatabase();
	}

	public function startUser(Player $player){
		$this->database->loadFor($player->getName(), $player->getId());
		if($player->spawned){
			$player->sendMessage("[HereAuth] Your account data are being loaded. Please wait patiently; it shouldn't take long.");
		}
	}

	/**
	 * @param int              $identifier
	 * @param AccountInfo|null $info
	 */
	public function onUserStart($identifier, $info){
		$player = $this->getPlayerById($identifier);
		if($player === null){
			return;
		}
		if(!isset($info->name)){
			$info = AccountInfo::defaultInstance($this, $player);
		}
		$this->users[$player->getId()] = new User($this, $player, $info);
	}

	public function closeUser(Player $player){
		if(isset($this->users[$player->getId()])){
			$this->users[$player->getId()]->finalize();
			unset($this->users[$player->getId()]);
		}
		return;
	}

	public function getUserById($id){
		return isset($this->users[$id]) ? $this->users[$id] : null;
	}

	public function getUserByPlayer(Player $player){
		$id = $player->getId();
		return isset($this->users[$id]) ? $this->users[$id] : null;
	}

	public function getUserByName($name){
		$player = $this->getServer()->getPlayer($name);
		return $player === null ? null : $this->getUserById($player->getId());
	}

	public function getUserByExactName($name){
		$player = $this->getServer()->getPlayerExact($name);
		return $player === null ? null : $this->getUserById($player->getId());
	}

	public function getPlayerById($id){
		if(isset($this->users[$id])){
			return $this->users[$id]->getPlayer();
		}
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->getId() === $id){
				return $player;
			}
		}
		return null;
	}

	public function getDatabase(){
		return $this->database;
	}

	public function setDatabase(Database $database){
		if(isset($this->database)){
			throw new \InvalidStateException("Database is already set and is not closed!");
		}
		$this->database = $database;
	}

	public function closeDatabase(){
		$this->database->close();
		unset($this->database);
	}

	/**
	 * @param string $password
	 * @param Player $player
	 *
	 * @return string
	 */
	public static function hash($password, Player $player){
		$salt = strtolower($player->getName());
		return bin2hex(hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true));
	}

	/**
	 * @param Server $server
	 *
	 * @return HereAuth|null
	 */
	public static function getInstance(Server $server){
		$me = $server->getPluginManager()->getPlugin(self::$NAME);
		return ($me !== null and $me->isEnabled()) ? $me : null;
	}

	/**
	 * @return User[]
	 */
	public function getUsers(){
		return $this->users;
	}

	/**
	 * @return AuditLogger
	 */
	public function getAuditLogger(){
		return $this->auditLogger;
	}
}
