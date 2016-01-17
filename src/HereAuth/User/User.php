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

namespace HereAuth\User;

use HereAuth\Event\HereAuthRegistrationCreationEvent;
use HereAuth\HereAuth;
use HereAuth\User\Registration\Registration;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\level\Position;
use pocketmine\Player;

class User{
	const STATE_PLAYING = 0;
	const STATE_REGISTERING = 1;
	const STATE_PENDING_LOGIN = 2;

	/** @type HereAuth */
	private $main;
	/** @type Player */
	private $player;
	/** @type AccountInfo */
	private $accountInfo;
	/** @type int */
	private $state;
	/** @type Registration */
	private $registration;
	/** @type int */
	private $loginAttempts = 0;
	/** @type float */
	private $loadTime;
	/** @type Position|null */
	public $origPos = null;

	public function __construct(HereAuth $main, Player $player, AccountInfo $info){
		$this->main = $main;
		$this->player = $player;
		$this->accountInfo = $info;
		if(!$info->passwordHash){
			if(!$main->getConfig()->getNested("ForceRegister.Enabled", true)){ // no registration involved
				$this->onAuth();
				$reminder = $main->getConfig()->getNested("ForceRegister.Reminder", "");
				if(strlen($reminder) > 0){
					$player->sendMessage($reminder);
				}
				return;
			}
			$this->startRegistration();
			return;
		}
		if($info->opts->autoSecret and $player->getClientSecret() === $info->lastSecret){
			$this->main->getAuditLogger()->logLogin(strtolower($player->getName()), $player->getAddress(), "secret");
			$this->onAuth();
			return;
		}
		if($info->opts->autoIp and $player->getAddress() === $info->lastIp){
			$this->main->getAuditLogger()->logLogin(strtolower($player->getName()), $player->getAddress(), "ip");
			$this->onAuth();
			return;
		}
		if($info->opts->autoUuid and $player->getUniqueId()->toBinary() === $info->lastUuid){
			$this->main->getAuditLogger()->logLogin(strtolower($player->getName()), $player->getAddress(), "uuid");
			$this->onAuth();
			return;
		}
		$this->state = self::STATE_PENDING_LOGIN;
		$this->player->sendMessage($main->getConfig()->getNested("Messages.Login.Query", "Please login"));
	}

	public function startRegistration(){
		$this->state = self::STATE_REGISTERING;
		$this->main->getServer()->getPluginManager()->callEvent($ev = new HereAuthRegistrationCreationEvent($this));
		$this->registration = $ev->getRegistration();
		$this->getPlayer()->sendMessage($this->getMain()->getConfig()->getNested("Messages.Register.ImplicitRegister", "This server uses HereAuth to protect your account."));
		$this->registration->init();
	}

	/**
	 * @internal $DEATH_THREATS Do not use this method from other plugins.
	 */
	public function onRegistrationCompleted(){
		$this->main->getAuditLogger()->logRegister(strtolower($this->player->getName()), $this->player->getAddress());
		$this->getPlayer()->sendMessage($this->getMain()->getConfig()->getNested("Messages.Register.Completion", "registered"));
		$this->accountInfo->registerTime = time();
		$this->onAuth();
	}

	public function checkMultiFactor(){
		if($this->accountInfo->opts->multiIp){
			if($this->player->getAddress() !== $this->accountInfo->lastIp){
				$this->main->getAuditLogger()->logFactor(strtolower($this->player->getName()), "ip", $this->player->getAddress());
				$this->player->kick("Incorrect IP address!", false);
				return false;
			}
		}
		if($this->accountInfo->opts->multiSkin){
			if($this->player->getSkinName() . $this->player->getSkinData() !== $this->accountInfo->lastSkin){
				$this->main->getAuditLogger()->logFactor(strtolower($this->player->getName()), "skin", $this->player->getSkinName() . ":" . base64_encode($this->player->getSkinData()));
				$this->player->kick("Incorrect skin!", false);
				return false;
			}
		}
		return true;
	}

	public function finalize(){
		if($this->state === self::STATE_PLAYING){
			$this->save();
		}
		if($this->origPos !== null){
			$this->player->teleport($this->origPos);
		}
	}

	public function save(){
		$this->main->getDatabase()->saveData($this->player->getName(), $this->accountInfo);
	}

	public function onAuth(){
		$this->state = self::STATE_PLAYING;
		$this->accountInfo->lastUuid = $this->getPlayer()->getUniqueId()->toBinary();
		$this->accountInfo->lastLogin = time();
		$this->accountInfo->lastSecret = $this->getPlayer()->getClientSecret();
		$this->accountInfo->lastSkin = $this->getPlayer()->getSkinName() . $this->getPlayer()->getSkinData();
		$this->accountInfo->lastIp = $this->getPlayer()->getAddress();
		$this->player->sendMessage("You have been authenticated.");
		$this->player->getInventory()->sendContents($this->player);
		$this->player->getInventory()->sendArmorContents($this->player);
	}

	public function onMessage(PlayerCommandPreprocessEvent $event){
		$message = $event->getMessage();
		$hash = HereAuth::hash($message, $this->getPlayer());
		if($this->state === self::STATE_PENDING_LOGIN){
			if($this->accountInfo->testPassword($this->main, $message)){
				$this->main->getAuditLogger()->logLogin(strtolower($this->player->getName()), $this->player->getAddress(), "password");
				$this->onAuth();
			}else{
				$this->main->getAuditLogger()->logInvalid(strtolower($this->player->getName()), $this->player->getAddress());
				$this->loginAttempts++;
				$chances = $this->main->getConfig()->getNested("Login.MaxAttempts", 5);
				$left = $chances - $this->loginAttempts;
				if($left <= 0){
					$this->getPlayer()->kick("Failed to login in $chances attempts", false);
				}
				$msg = $this->getMain()->getConfig()->getNested("Messages.Login.WrongPass", "wrong pass");
				$msg = str_replace('$CHANCES', $left, $msg);
				$this->getPlayer()->sendMessage($msg);
			}
			$event->setCancelled();
			$event->setMessage("");
		}elseif($this->state === self::STATE_PLAYING){
			if($hash === $this->accountInfo->passwordHash and $this->getMain()->getConfig()->getNested("BlockPasswordChat", true)){
				$event->setCancelled();
				$event->setMessage("");
				$this->getPlayer()->sendMessage($this->getMain()->getConfig()->getNested("Messages.Chat.DirectPass", "Don't tell your password"));
			}
		}elseif($this->state === self::STATE_REGISTERING){
			$this->registration->handle($message);
			$event->setCancelled();
			$event->setMessage("");
		}
	}

	public function getState(){
		return $this->state;
	}

	public function isPlaying(){
		return $this->state === self::STATE_PLAYING;
	}

	public function isRegistering(){
		return $this->state === self::STATE_REGISTERING;
	}

	public function isLoggingIn(){
		return $this->state === self::STATE_PENDING_LOGIN;
	}

	/**
	 * @return HereAuth
	 */
	public function getMain(){
		return $this->main;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(){
		return $this->player;
	}

	/**
	 * @return Registration
	 */
	public function getRegistration(){
		return $this->registration;
	}

	/**
	 * @return AccountInfo
	 */
	public function getAccountInfo(){
		return $this->accountInfo;
	}

	/**
	 * Returns the microtime when this user is created (data is loaded)
	 *
	 * @return float
	 */
	public function getLoadTime(){
		return $this->loadTime;
	}
}
