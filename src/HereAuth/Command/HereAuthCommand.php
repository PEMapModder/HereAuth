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
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;

abstract class HereAuthCommand extends Command implements PluginIdentifiableCommand{
	/** @type HereAuth */
	protected $main;

	/**
	 * HereAuthCommand constructor.
	 *
	 * @param HereAuth $main
	 * @param string   $name
	 * @param string   $desc
	 * @param string   $usage
	 * @param string[] ...$aliases
	 */
	public function __construct(HereAuth $main, $name, $desc, $usage, ...$aliases){
		$this->main = $main;
		parent::__construct($name, $desc, $usage, $aliases);
	}

	/**
	 * @return HereAuth
	 */
	public function getMain(){
		return $this->main;
	}

	public function getMessage(string $key, string $default) : string{
		return $this->main->getMessages()->getNested($key, $default);
	}

	/**
	 * @return HereAuth
	 */
	public function getPlugin(){
		return $this->main;
	}

	public function execute(CommandSender $sender, $commandLabel, array $args){
		try{
			if(!$this->testPermission($sender)){
				return false;
			}
			$result = $this->run($args, $sender);
			if(is_string($result)){
				$sender->sendMessage($result);
			}
			return true;
		}catch(\Exception $e){
			// TODO implement error handling
			return false;
		}
	}

	protected abstract function run(array $args, CommandSender $sender);
}
