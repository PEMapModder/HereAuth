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

namespace HereAuth\Command;

use HereAuth\HereAuth;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ImportCommand extends HereAuthCommand{
	public function __construct(HereAuth $main){
		$this->main = $main;
		parent::__construct($main, "import",
			$this->getMessage("Commands.Import.Description", "Import accounts from database of another plugin"),
			$this->getMessage("Commands.Import.Usage", "Type `/import` for detailed usage"));
		$this->setPermission("hereauth.import.command");
	}

	protected function run(array $args, CommandSender $sender){
		if(isset($args[0])){
			$name = array_shift($args);
			$overwrite = false;
			if($name === "help" and isset($args[0])){
				$name = array_shift($args);
				$help = true;
			}elseif($name === ",overwrite" and isset($args[1])){
				$name = array_shift($args);
				$overwrite = true;
			}

			$reader = $this->getMain()->getAccountReader($name);
			if($reader !== null){
				list($readerClass, $readerUsage) = $reader;
				if(isset($help)){
					$sender->sendMessage(TextFormat::AQUA . $readerUsage);
					$sender->sendMessage(TextFormat::AQUA . str_repeat("=", 30));
					return true;
				}

				if($this->getMain()->getImportThread() !== null){
					$sender->sendMessage(TextFormat::RED . $this->getMessage("Commands.Import.Concurrent", "An import task is already in progress!"));
					return false;
				}

				if(
					$overwrite and !$sender->hasPermission("hereauth.import.overwrite") or
					!$overwrite and !$sender->hasPermission("hereauth.import.merge")
				){
					$sender->sendMessage(TextFormat::RED . "You don't have permission to " .
						($overwrite ? "overwrite" : "merge") . " databases.");
				}

				$thread = $this->getMain()->getNewImporterThread($overwrite, $readerClass, $args);
				$this->getMain()->setImportThread($sender, $thread);
				$thread->start();
				return true;
			}
			$sender->sendMessage(TextFormat::RED . "Account reader \"$name\" not registered!");
		}
		$sender->sendMessage(TextFormat::AQUA . $this->getMessage("Commands.Import.Main.Header", "The following account readers are available:"));
		$sender->sendMessage(TextFormat::DARK_PURPLE . implode(", ", array_keys($this->getMain()->getAccountReaders())));
		$sender->sendMessage(TextFormat::AQUA . $this->getMessage("Commands.Import.Main.Footer", "Use `/import help <reader name>` for detailed usage per reader"));
		return true;
	}
}
