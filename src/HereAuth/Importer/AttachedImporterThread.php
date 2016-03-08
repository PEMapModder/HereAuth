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

namespace HereAuth\Importer;

use pocketmine\command\CommandSender;

class AttachedImporterThread{
	/** @type CommandSender */
	public $sender;
	/** @type ImporterThread */
	public $thread;

	public function __construct(CommandSender $sender, ImporterThread $thread){
		$this->sender = $sender;
		$this->thread = $thread;
	}
}
