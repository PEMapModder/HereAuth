<?php

/*
 *
 * HereAuth
 *
 * Copyright (C) 2017 SOFe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

namespace HereAuth\Command;

use HereAuth\HereAuth;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use const PHP_VERSION;
use const pocketmine\API_VERSION;
use const pocketmine\VERSION as PM_VERSION;

class HereAuthVersionCommand extends HereAuthCommand{
	public function __construct(HereAuth $main){
		parent::__construct($main, "haversion",
			"Show HereAuth version",
			"/haversion");
		$this->setPermission("hereauth.version");
	}

	protected function run(array $args, CommandSender $sender){
		$sender->sendMessage(sprintf("Version info: %s%s, %s%s, OS %s, PHP %s, PM %s %s, API %s, MC %s, protocol %d, metadata %s", TextFormat::GOLD,
			$this->main->getFullName(),
			(new \ReflectionClass($this->main->getPluginLoader()))->getShortName(),
			\Phar::running(false) ? ("?crc32=" . hash_file("crc32b", \Phar::running(false))) : "",
			Utils::getOS(), PHP_VERSION,
			base64_encode($this->main->getServer()->getName()), PM_VERSION, API_VERSION,
			ProtocolInfo::MINECRAFT_VERSION, ProtocolInfo::CURRENT_PROTOCOL,
			\Phar::running(false) ? base64_encode(json_encode((new \Phar(\Phar::running(false)))->getMetadata())) : ""));
	}
}
