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

namespace HereAuth\User\Registration;

use HereAuth\HereAuth;
use HereAuth\User\User;

class Registration{
	private $user;
	/** @type RegistrationStep[] */
	private $steps = [];
	/** @type string */
	private $tempHash;

	/** @type int */
	private $currentStep = 0;

	public function __construct(User $user){
		$this->user = $user;
		$this->steps[] = new PasswordInputRegistrationStep($user);
		if($this->user->getMain()->getConfig()->getNested("Registration.RequireConfirm", true)){
			$this->steps[] = new PasswordConfirmRegistrationStep($user);
		}
		foreach($user->getMain()->getConfig()->getNested("Registration.ExtraFields", []) as $i => $field){
			if(!isset($field["Message"], $field["FieldName"])){
				$this->user->getMain()->getLogger()->warning("The #" . ($i + 1) . " entry in Registration.ExtraFields is does not have the Message/FieldName line! It is not going to be added.");
				continue;
			}
			$message = $field["Message"];
			$fieldName = $field["FieldName"];
			$regex = null;
			$error = null;
			if(isset($field["RegExp"], $field["ErrorMessage"])){
				$regex = $field["RegExp"];
				$error = $field["ErrorMessage"];
			}
			$this->addStep(new ConfigDefinedRegistrationStep($this->user, $message, $fieldName, $regex, $error));
		}
	}

	public function init(){
		$this->user->getPlayer()->sendMessage($this->current()->getMessage());
	}

	/**
	 * Append $step to the queue of registration steps in this registration
	 *
	 * @param RegistrationStep $step
	 */
	public function addStep(RegistrationStep $step){
		// yes, I know you can use reflections to overcome this check...
		/** @noinspection PhpInternalEntityUsedInspection */
		if($step instanceof PasswordRegistrationStep){
			throw new \RuntimeException("Attempt to register an internal registration step");
		}
		$this->steps[] = $step;
	}

	/**
	 * @return string
	 */
	public function getTempHash(){
		return $this->tempHash;
	}

	/**
	 * @param string $tempHash
	 */
	public function setTempHash($tempHash){
		$this->tempHash = $tempHash;
	}

	/**
	 * @return User
	 */
	public function getUser(){
		return $this->user;
	}

	public function handle($value){
		/** @noinspection PhpInternalEntityUsedInspection */
		if(!($this->current() instanceof PasswordRegistrationStep)){
			if(HereAuth::hash($value, $this->user->getPlayer()) === $this->user->getAccountInfo()->passwordHash){
				$this->user->getPlayer()->sendMessage("[HereAuth] If the message above is asking you to enter your password, it is not a message from HereAuth! Please beware your password being stolen!");
				return;
			}
		}
		if($this->current()->onSubmit($value)){
			if($this->next()){
				return;
			}
		}
		$this->user->getPlayer()->sendMessage($this->current()->getMessage());
	}

	private function current(){
		return $this->steps[$this->currentStep];
	}

	private function next(){
		$this->currentStep++;
		if(!isset($this->steps[$this->currentStep])){
			/** @noinspection PhpInternalEntityUsedInspection */
			$this->user->onRegistrationCompleted();
			return true;
		}
		return false;
	}

	/**
	 * Call this from {@link RegistrationStep::onSubmit} and then return false to revert to the previous step. This
	 * will NOT send the message again, because {@link Registration::handle} will send it.
	 *
	 * @throws \OutOfBoundsException if current step is already at the earliest step.
	 * @throws \InvalidStateException if an attempt to rewind from a non-{@link PasswordRegistrationStep} to a
	 *                                {@link PasswordRegistrationStep} (or the other way around, which MUST NOT happen)
	 *                                is detected
	 */
	public function rewind(){
		if($this->currentStep === 0){
			throw new \OutOfBoundsException("Current step is already at 0");
		}
		/** @noinspection PhpInternalEntityUsedInspection */
		if(
			$this->steps[$this->currentStep - 1] instanceof PasswordRegistrationStep
			!== $this->steps[$this->currentStep] instanceof PasswordRegistrationStep
		){
			throw new \InvalidStateException("Attempt to rewind from a non-PasswordRegistrationStep to a PasswordRegistrationStep");
		}
		$this->currentStep--;
	}
}
