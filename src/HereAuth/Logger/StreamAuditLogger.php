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

namespace HereAuth\Logger;

use HereAuth\HereAuth;
use pocketmine\utils\Utils;

class StreamAuditLogger implements AuditLogger{
	const DATE_FORMAT = \DateTime::ATOM;

	/** @type resource[] */
	private $instances = [];
	/** @type resource */
	private $register, $login, $push, $bump, $invalid, $timeout, $factor;

	public function __construct(HereAuth $main){
		$dir = rtrim($main->getConfig()->getNested("AuditLogger.LogFolder", "audit"), "/") . "/";
		if($dir{0} !== "/" and strpos($dir, "://") === false){
			$dir = $main->getDataFolder() . $dir;
		}
		if(!is_dir($dir)){
			mkdir($dir, 0777, true);
		}
		foreach($entries = ["register", "login", "push", "bump", "invalid", "timeout", "factor"] as $entry){
			$value = $main->getConfig()->getNested("AuditLogger.Log." . ucfirst($entry), ($isWin = Utils::getOS() === "win") ? "/NUL" : "/dev/null");
			if($value === "/NUL" and !$isWin or $value === "/dev/null" and $isWin){
				$main->getLogger()->warning("Your OS is " . ($isWin ? "Windows" : "not Windows") . ", where $value is not a special file! HereAuth will attempt to create that file!");
			}
			if($value{0} !== "/"){
				$value = $dir . $value;
			}
			$this->{$entry} = $stream = $this->getStream($value);
//			fwrite($stream, date(self::DATE_FORMAT) . " Start logging $entry\n");
		}
	}

	private function getStream($path){
		if(isset($this->instances[$path])){
			return $this->instances[$path];
		}
		return $this->instances[$path] = fopen($path, "at");
	}

	public function logRegister($name, $ip){
		fwrite($this->register, date(self::DATE_FORMAT) . " Register:$name/$ip\n");
	}

	public function logLogin($name, $ip, $method){
		fwrite($this->login, date(self::DATE_FORMAT) . " Login:$name,$ip,$method\n");
	}

	public function logPush($name, $oldIp, $newIp){
		fwrite($this->push, date(self::DATE_FORMAT) . " Push:$name,$oldIp,$newIp\n");
	}

	public function logBump($name, $oldIp, $newIp, $oldUuid, $newUuid, $oldState){
		fwrite($this->bump, date(self::DATE_FORMAT) . " Bump,$name,$oldIp,$newIp,$oldUuid,$newUuid,$oldState\n");
	}

	public function logInvalid($name, $ip){
		fwrite($this->invalid, date(self::DATE_FORMAT) . " Invalid:$name,$ip\n");
	}

	public function logTimeout($name, $ip){
		fwrite($this->timeout, date(self::DATE_FORMAT) . " Timeout:$name,$ip\n");
	}

	public function logFactor($name, $wrongType, $wrongData){
		fwrite($this->factor, date(self::DATE_FORMAT) . " Factor:$name,$wrongType,$wrongData\n");
	}

	public function close(){
		foreach($this as $k => $v){
			if($k !== "instances" and is_resource($v)){
//				fwrite($v, date(self::DATE_FORMAT) . " End logging $k\n");
				unset($this->{$k});
			}
		}
		foreach($this->instances as $instance){
			fclose($instance);
		}
	}
}
