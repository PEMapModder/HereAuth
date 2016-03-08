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

namespace HereAuth\Task;

use HereAuth\HereAuth;
use pocketmine\scheduler\PluginTask;

class CheckImportThreadTask extends PluginTask{
	/** @type HereAuth */
	private $main;

	public function __construct(HereAuth $main){
		parent::__construct($this->main = $main);
	}

	public function onRun($currentTick){
		$this->main->checkThread();
	}
}
