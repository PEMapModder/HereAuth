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

namespace HereAuth\Event;

use HereAuth\HereAuth;
use pocketmine\event\plugin\PluginEvent;

abstract class HereAuthEvent extends PluginEvent{
	public function __construct(HereAuth $main){
		parent::__construct($main);
	}

	/**
	 * @return HereAuth
	 */
	public function getMain(){
		return $this->getPlugin();
	}
}
