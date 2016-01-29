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

use pocketmine\event\Cancellable;

/**
 * This event is fired when a user undergoes MFA.
 *
 * - Call addFailure()/addFailureEntry() if another plugin has a failed MFA for the user.
 * - Call removeFailure() to disable another failed MFA.
 * - HereAuth would check for "ip" and "skin" failure types before calling this event.
 * - This event is cancelled/can be cancelled if MFA is disabled for this user (e.g. because of MFA timeout or other
 * plugins' reasons)
 */
class HereAuthMultiFactorAuthEvent extends HereAuthUserEvent implements Cancellable{
	public static $handlerList = null;

	/** @type MultiFactorAuthFailure[] */
	private $failures = [];

	/**
	 * @return MultiFactorAuthFailure[]
	 */
	public function getFailures(){
		return $this->failures;
	}

	/**
	 * See {@link MultiFactorAuthFailure::__construct} for explanation on the parameters.
	 *
	 * @param string $type
	 * @param string $message
	 * @param string $logData
	 */
	public function addFailureEntry($type, $message, $logData = ""){
		$this->addFailure(new MultiFactorAuthFailure($type, $message, $logData));
	}

	public function addFailure(MultiFactorAuthFailure $failure){
		$this->failures[$failure->type] = $failure;
	}

	/**
	 * @param string $type type of failure to remove
	 *
	 * @return bool whether a failure of this type existed.
	 */
	public function removeFailure($type){
		if(isset($this->failures[$type])){
			unset($this->failures[$type]);
			return true;
		}
		return false;
	}
}
