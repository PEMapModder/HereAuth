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
use HereAuth\User\User;
use pocketmine\command\CommandSender;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class HereAuthUnregisterEvent extends HereAuthEvent implements Cancellable{
	public static $handlerList = null;

	/** @type CommandSender */
	private $doer;
	/** @type string */
	private $subject;
	/** @type User|null */
	private $subjectUser;
	/** @type string */
	private $cancelMessage;

	public function __construct(HereAuth $main, CommandSender $doer, string $subject, User $subjectUser = null, string $cancelMessage = "Cancelled by a plugin"){
		parent::__construct($main);
		$this->doer = $doer;
		$this->subject = $subject;
		$this->subjectUser = $subjectUser;
	}

	public function getDoer() : CommandSender{
		return $this->doer;
	}

	public function getSubject() : string{
		return $this->subject;
	}

	/**
	 * @return User|null
	 */
	public function getSubjectUser(){
		return $this->subjectUser;
	}

	/**
	 * @return Player|null
	 */
	public function getSubjectPlayer(){
		return $this->subjectUser !== null ? $this->subjectUser->getPlayer() : null;
	}

	public function getCancelMessage() : string{
		return $this->cancelMessage;
	}

	public function setCancelMessage(string $cancelMessage){
		$this->cancelMessage = $cancelMessage;
	}
}
