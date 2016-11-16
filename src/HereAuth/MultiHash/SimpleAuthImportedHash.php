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

namespace HereAuth\MultiHash;

use pocketmine\Player;

class SimpleAuthImportedHash implements ImportedHash{
	public function getName(){
		return "simpleauth";
	}

	public function hash($password, $salt, $suffix){
		return bin2hex(SimpleAuthImportedHash::hashAlgo($password, $salt));
	}

	/**
	 * @param string        $password
	 * @param string|Player $player
	 *
	 * @return string
	 */
	public static function hashAlgo($password, $player){
		$salt = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);
		return hash("sha512", $password . $salt, true) ^ hash("whirlpool", $salt . $password, true);
	}
}
