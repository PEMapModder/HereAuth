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

use HereAuth\Command\ChangePasswordCommand;
use HereAuth\Command\ImportCommand;
use HereAuth\Command\LockCommand;
use HereAuth\Command\OptCommand;
use HereAuth\Command\RegisterCommand;
use HereAuth\Command\UnregisterCommand;
use HereAuth\Database\Database;
use HereAuth\Database\Json\JsonDatabase;
use HereAuth\Database\MySQL\MySQLDatabase;
use HereAuth\Importer\AttachedImporterThread;
use HereAuth\Importer\ImporterThread;
use HereAuth\Importer\Reader\ServerAuthMySQLAccountReader;
use HereAuth\Importer\Reader\ServerAuthYAMLAccountReader;
use HereAuth\Importer\Reader\SimpleAuthMySQLAccountReader;
use HereAuth\Importer\Reader\SimpleAuthSQLite3AccountReader;
use HereAuth\Importer\Reader\SimpleAuthYAMLAccountReader;
use HereAuth\Logger\AuditLogger;
use HereAuth\Logger\StreamAuditLogger;
use HereAuth\MultiHash\ImportedHash;
use HereAuth\MultiHash\RenamedHash;
use HereAuth\MultiHash\SaltlessArgumentedImportedHash;
use HereAuth\Task\CheckImportThreadTask;
use HereAuth\Task\CheckUserTimeoutTask;
use HereAuth\Task\RemindLoginTask;
use HereAuth\User\AccountInfo;
use HereAuth\User\User;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Utils;

class HereAuth extends PluginBase implements Listener{
	/** @type string */
	private static $NAME = "HereAuth";
	/** @type Config */
	private $messages;
//	/** @type Config */
//	private $http;
	/** @type User[] */
	private $users = [];
	/** @type EventRouter */
	private $router;
	/** @type Database */
	private $database;
	/** @type AuditLogger */
	private $auditLogger;
	/** @type ImportedHash[] */
	private $importedHashes = [];
	/** @type string[][] */
	private $accountReaders = [];
	/** @type Fridge */
	private $fridge;
	/** @type AttachedImporterThread|null */
	private $importThread = null;

	public function onLoad(){
		self::$NAME = $this->getName();
		assert(in_array($this->getServer()->getName(), ["PocketMine-MP", "PocketMine-Soft"]), "Haters Gonna Hate");
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
		if(!is_file($messagesPath = $this->getDataFolder() . "messages.yml")){
			$this->saveResource("messages.yml");
			$configPaths[] = $messagesPath;
		}
//		if(!is_file($messagesPath = $this->getDataFolder() . "http.yml")){
//			$this->saveResource("http.yml");
//			$configPaths[] = $messagesPath;
//		}
		if(count($configPaths) > 0){
			$action = $new ? "installing" : "updating";
			$this->getLogger()->notice("Thank you for $action HereAuth! New config file(s) have been generated at the following location(s):");
			foreach($configPaths as $path){
				$this->getLogger()->info(realpath($path));
			}
			$this->getLogger()->info("You may want to edit the config file(s) to customize HereAuth for your server.");
		}
		$this->messages = new Config($this->getDataFolder() . "messages.yml");
//		$this->http = new Config($this->getDataFolder() . "http.yml");
		$this->fridge = new Fridge($this);
		$this->addImportedHash(new RenamedHash);
		$this->addImportedHash(new SaltlessArgumentedImportedHash);
		if(!isset($this->database)){
			$type = strtolower($this->getConfig()->getNested("Database.Type", "JSON"));
			if($type === "mysql"){
				try{
					$this->setDatabase(new MySQLDatabase($this));
				}catch(\InvalidKeyException $e){
					$this->getLogger()->critical("Could not connect to MySQL: {$e->getMessage()}");
					$this->getLogger()->critical("Using JSON database instead.");
				}
			}elseif($type !== "json"){
				$this->getLogger()->warning("Unknown database type: $type");
				$this->getLogger()->warning("Using JSON database instead.");
			}

			if(!isset($this->database)){
				$this->setDatabase(new JsonDatabase($this));
			}
		}
		$this->auditLogger = new StreamAuditLogger($this);
		$this->router = new EventRouter($this);
		$this->getServer()->getCommandMap()->registerAll("ha", [
			new RegisterCommand($this),
			new UnregisterCommand($this),
			new ChangePasswordCommand($this),
			new LockCommand($this),
			new OptCommand($this),
			new ImportCommand($this),
		]);
		new CheckUserTimeoutTask($this);
		new RemindLoginTask($this);
		$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CheckImportThreadTask($this), 20, 20);

		$this->registerAccountReader("serverauth-mysql", ServerAuthMySQLAccountReader::class,
			$this->getMessages()->getNested("Commands.Import.Help.ServerAuth.MySQL", <<<EOU
Usage: /import [,overwrite] serverauth-mysql [parameters...]
You can specify these parameters: (default to config.yml MySQL settings)
,h <MySQL host address>
,u <MySQL username>
,p <MySQL password>
,s <MySQL schema/database name>
,port <MySQL port>
,sk <path to MySQL socket file>
E.g: /import serverauth-mysql ,h example.com ,u "my name" ,p ""
EOU
			));
		$this->registerAccountReader("serverauth-yaml", ServerAuthYAMLAccountReader::class,
			$this->getMessages()->getNested("Commands.Import.Help.ServerAuth.YAML", <<<EOU
Usage: /import [,overwrite] serverauth-yaml [parameters...]
You can specify these parameters:
,i <path to ServerAuth data folder>
,hash <special hash algorithm used by ServerAuth>
E.g: /import serverauth-yaml ,i /root/plugins/ServerAuth
EOU
			));
		$this->registerAccountReader("simpleauth-mysql", SimpleAuthMySQLAccountReader::class,
			$this->getMessages()->getNested("Commands.Import.Help.SimpleAuth.MySQL", <<<EOU
Usage: /import [,overwrite] simpleauth-mysql [parameters...]
You can specify these parameters. Default to config.yml MySQL settings.
,h <MySQL host address>
,u <MySQL username>
,p <MySQL password>
,s <MySQL schema/database name>
,port <MySQL port>
,sk <path to MySQL socket file>
E.g: /import simpleauth-mysql ,h example.com ,u "my name" ,p ""
EOU
			));
		$this->registerAccountReader("simpleauth-sqlite", SimpleAuthSQLite3AccountReader::class,
			$this->getMessages()->getNested("Commands.Import.Help.SimpleAuth.SQLite", <<<EOU
Usage: /import [,overwrite] simpleauth-sqlite [parameters...]
You can specify these parameters:
,i <path to SimpleAuth data folder>
E.g: /import simpleauth-sqlite ,i /root/plugins/SimpleAuth
EOU
			));
		$this->registerAccountReader("simpleauth-yaml", SimpleAuthYAMLAccountReader::class,
			$this->getMessages()->getNested("Commands.Import.Help.SimpleAuth.YAML", <<<EOU
Usage: /import [,overwrite] simpleauth-yaml [parameters...]
You can specify these parameters:
,i <path to SimpleAuth data folder>
E.g: /import simpleauth-yaml ,i /root/plugins/SimpleAuth
EOU
			));

		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->startUser($player);
		}
	}

	public function onDisable(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->closeUser($player);
		}
		if($this->database !== null){
			$this->closeDatabase();
		}
		if($this->getAuditLogger() !== null){
			$this->getAuditLogger()->close();
		}
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
			$info = AccountInfo::defaultInstance($player, $this);
		}
		try{
			$user = new User($this, $player, $info);
		}catch(\Exception $e){
			return;
		}
		$this->users[$player->getId()] = $user;
	}

	public function closeUser(Player $player){
		if(isset($this->users[$player->getId()])){
			$this->users[$player->getId()]->finalize();
			unset($this->users[$player->getId()]);
		}
	}

	public function getUserById($id){
		return $this->users[$id] ?? null;
	}

	public function getUserByPlayer(Player $player){
		$id = $player->getId();
		return $this->users[$id] ?? null;
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

	public function getDataBase(){
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

	public function getNewImporterThread(bool $overwrite, string $readerClass, array $readerArgs) : ImporterThread{
		if(!isset($this->database)){
			throw new \InvalidStateException("Database not initialized");
		}
		return new ImporterThread($this, $overwrite, $readerClass, $readerArgs,
			$this->getDataBase()->getAccountWriter($writerArgs), $writerArgs);
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

	public function getImportedHashes(){
		return $this->importedHashes;
	}

	public function addImportedHash(ImportedHash $hash){
		$this->importedHashes[$hash->getName()] = $hash;
	}

	public function getImportedHash($type){
		return $this->importedHashes[$type] ?? null;
	}

	public function registerAccountReader(string $name, string $class, string $usage){
		$this->accountReaders[$name] = [$class, $usage];
	}

	/**
	 * @param string $name
	 *
	 * @return string[]|null
	 */
	public function getAccountReader(string $name){
		return $this->accountReaders[$name] ?? null;
	}

	/**
	 * @return string[][]
	 */
	public function getAccountReaders() : array{
		return $this->accountReaders;
	}

	/**
	 * @return AttachedImporterThread|null
	 */
	public function getImportThread(){
		return $this->importThread;
	}

	public function setImportThread(CommandSender $commandSender, ImporterThread $thread){
		$this->importThread = new AttachedImporterThread($commandSender, $thread);
	}

	public function checkThread(){
		if($this->importThread !== null){
			if($this->importThread->thread->hasCompleted()){
				if(isset($this->importThread->thread->ex)){
					$this->importThread->sender->sendMessage("Exception caught during import: " . $this->importThread->thread->ex->getMessage());
					$this->getLogger()->logException($this->importThread->thread->ex);
				}else{
					$this->importThread->sender->sendMessage("Import completed!");
				}
				$this->importThread = null;
			}else{
				if($this->importThread->sender instanceof Player){
					$method = "sendPopup";
				}else{
					$method = "sendMessage";
				}
				$this->importThread->sender->$method("[HereAuth import] " . round($this->importThread->thread->progress * 100) .
					"% done: " . $this->importThread->thread->status . "...");
			}
		}
	}

	/**
	 * @return Fridge
	 */
	public function getFridge(){
		return $this->fridge;
	}

	public function getMessages() : Config{
		return $this->messages;
	}

	/**
	 * @param string        $password
	 * @param string|Player $player
	 *
	 * @return string
	 */
	public static function hash($password, $player){
		$salt = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);
		return hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true);
	}

	public function page($lines, &$pageNumber, &$maxPages){
		if(!is_array($lines)){
			$lines = explode("\n", $lines);
		}
		$pageSize = $this->getConfig()->getNested("Commands.HelpPageSize", 8);
		$linesCount = count($lines);
		$maxPages = ceil($linesCount / $pageSize);
		$pageNumber = min($maxPages, max(1, $pageNumber));
		$output = implode("\n", array_slice($lines, ($pageNumber - 1) * $pageSize, $pageSize));
		return $output;
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
}
