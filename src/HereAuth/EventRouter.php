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

namespace HereAuth;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\plugin\MethodEventExecutor;

class EventRouter implements Listener{
	/** @type HereAuth */
	private $main;

	public function __construct(HereAuth $main){
		$this->main = $main;
		if($this->main->getConfig()->getNested("MultiSesCtrl.Enabled", true)){
			$this->registerHandler(PlayerPreLoginEvent::class, "onPreLogin", EventPriority::NORMAL, true);
		}
		$this->registerHandler(PlayerLoginEvent::class, "onLogin", EventPriority::MONITOR, true);
		$this->registerHandler(PlayerQuitEvent::class, "onQuit", EventPriority::MONITOR, true);
		$this->registerHandler(PlayerCommandPreprocessEvent::class, "onMessage", EventPriority::LOWEST, false);
		$this->registerHandler(PlayerRespawnEvent::class, "onRespawn", EventPriority::HIGHEST, false);
		$this->registerHandler(DataPacketSendEvent::class, "onSend", EventPriority::HIGH, true);
		$events = [
			"DropItem",
			"Pick",
			"Eat",
		];
		$class = new \ReflectionClass($this);
		foreach($events as $event){
			if($main->getConfig()->getNested("Blocking.$event", true)){
				$method = $class->getMethod("on" . $event);
				if($method === null){
					throw new \RuntimeException("Missing method on$event");
				}
				$this->registerHandler($method->getParameters()[0]->getClass()->getName(), $method->getName(), EventPriority::LOW, true);
			}
		}
		if($main->getConfig()->getNested("Blocking.Damage", true) or $main->getConfig()->getNested("Blocking.Attack", true)){
			$this->registerHandler(EntityDamageEvent::class, "onDamage", EventPriority::LOW, true);
		}
		if($main->getConfig()->getNested("Blocking.Move.Locomotion", true) xor $main->getConfig()->getNested("Blocking.Move.Rotation", true)){
			$this->registerHandler(PlayerMoveEvent::class, "onMove", EventPriority::LOW, true);
		}
		if($main->getConfig()->getNested("Blocking.Touch", true)){
			$this->registerHandler(PlayerInteractEvent::class, "onTouch", EventPriority::LOW, true);
			$this->registerHandler(BlockBreakEvent::class, "onBreak", EventPriority::LOW, true);
		}
	}

	private function registerHandler($event, $method, $priority, $ignoreCancelled){
		assert(is_callable([$this, $method]), "Attempt to register nonexistent event handler " . static::class . "::$method");
		$this->main->getServer()->getPluginManager()->registerEvent($event, $this, $priority, new MethodEventExecutor($method), $this->main, $ignoreCancelled);
	}

	public function onPreLogin(PlayerPreLoginEvent $event){
		$newPlayer = $event->getPlayer();
		foreach($this->main->getServer()->getOnlinePlayers() as $oldPlayer){
			if($oldPlayer === $newPlayer){
				continue; // too lazy to check if this would happen
			}
			if(($lowName = strtolower($newPlayer->getName())) === strtolower($oldPlayer->getName())){ // we are having trouble
				$checkIp = $this->main->getConfig()->getNested("MultiSesCtrl.CheckIP", true);
				$cond = $oldPlayer->getClientSecret() === $newPlayer->getClientSecret();
				if($checkIp){
					$cond = ($cond and $oldPlayer->getAddress() === $newPlayer->getAddress()); // don't forget these parentheses! :) PHP operator precedence >.<
				}
				if($cond){
					$oldPlayer->kick("Login from the same device", false);
					$this->main->getAuditLogger()->logPush($lowName, $oldPlayer->getAddress(), $newPlayer->getAddress());
				}else{
					$event->setCancelled();
					$user = $this->main->getUserByPlayer($oldPlayer);
					if($user === null){
						$status = "loading HereAuth account";
						$oldState = "loading";
					}elseif($user->isRegistering()){
						$status = "registering with HereAuth";
						$oldState = "register";
					}elseif($user->isLoggingIn()){
						$status = "pending to login with HereAuth";
						$oldState = "login";
					}elseif($user->getAccountInfo()->passwordHash){
						$status = "logged in with HereAuth";
						$oldState = "auth";
					}else{
						$status = "account not registered with HereAuth";
						$oldState = "noreg";
					}
					$this->main->getAuditLogger()->logBump($lowName, $oldPlayer->getAddress(), $newPlayer->getAddress(), $oldPlayer->getUniqueId()->toString(), $newPlayer->getUniqueId()->toString(), $oldState);
					$event->setKickMessage("Player of the same name ($lowName) from another device is already online ($status)");
				}
			}
		}
	}

	public function onLogin(PlayerLoginEvent $event){
		$this->main->startUser($event->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $event){
		$this->main->closeUser($event->getPlayer());
	}

	public function onKick(PlayerKickEvent $event){
		if($event->getReason() === "Flying is not enabled on this server"){
			if(($user = $this->main->getUserByPlayer($event->getPlayer())) === null or !$user->isPlaying()){
				$event->setCancelled();
			}
		}
	}

	public function onMessage(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		if(($user = $this->main->getUserByPlayer($player)) !== null){
			$user->onMessage($event);
		}elseif($user === null){
			$event->setCancelled();
			$player->sendMessage("We are still loading your account. Please wait...");
		}
	}

	public function onDropItem(PlayerDropItemEvent $event){
		$user = $this->main->getUserByPlayer($event->getPlayer());
		if($user === null or !$user->isPlaying()){
			$event->setCancelled();
		}
	}

	public function onTouch(PlayerInteractEvent $event){
		$user = $this->main->getUserByPlayer($event->getPlayer());
		if($user === null or !$user->isPlaying()){
			$event->setCancelled();
		}
	}

	public function onBreak(BlockBreakEvent $event){
		$user = $this->main->getUserByPlayer($event->getPlayer());
		if($user === null or !$user->isPlaying()){
			$event->setCancelled();
		}
	}

	public function onEat(PlayerItemConsumeEvent $event){
		$user = $this->main->getUserByPlayer($event->getPlayer());
		if($user === null or !$user->isPlaying()){
			$event->setCancelled();
		}
	}

	public function onPick(InventoryPickupItemEvent $event){
		$inv = $event->getInventory();
		if(!($inv instanceof PlayerInventory)){
			return;
		}
		$player = $inv->getHolder();
		if(!($player instanceof Player)){
			return;
		}
		$user = $this->main->getUserByPlayer($player);
		if($user === null or !$user->isPlaying()){
			$event->setCancelled();
		}
	}

	public function onDamage(EntityDamageEvent $event){
		if($this->main->getConfig()->getNested("Blocking.Damage", true)){
			$victim = $event->getEntity();
			if($victim instanceof Player){
				$user = $this->main->getUserByPlayer($victim);
				if($user === null or !$user->isPlaying()){
					$event->setCancelled();
					return;
				}
			}
		}
		if($this->main->getConfig()->getNested("Blocking.Attack", true) and $event instanceof EntityDamageByEntityEvent){
			$player = $event->getDamager();
			if($player instanceof Player){
				$user = $this->main->getUserByPlayer($player);
				if($user === null or !$user->isPlaying()){
					$event->setCancelled();
				}
			}
		}
	}

	public function onMove(PlayerMoveEvent $event){
		if($this->main->getConfig()->getNested("Blocking.Move.Locomotion", true)){
			if(!$event->getFrom()->equals($to = $event->getTo())){
				$user = $this->main->getUserByPlayer($event->getPlayer());
//				if(!($user !== null and $user->origPos !== null and $user->origPos->equals($to))){
				if($user === null or !$user->isPlaying()){
					$event->setCancelled();
					return;
				}
//				}
			}
		}
		if($this->main->getConfig()->getNested("Blocking.Move.Rotation", true)){
			$from = $event->getFrom();
			$to = $event->getTo();
			if($from->yaw !== $to->yaw or $from->pitch !== $to->pitch){
				$user = $this->main->getUserByPlayer($event->getPlayer());
				if($user === null or !$user->isPlaying()){
					$event->setCancelled();
				}
			}
		}
	}

	public function onRespawn(PlayerRespawnEvent $event){
		$user = $this->main->getUserByPlayer($player = $event->getPlayer());
		if($user === null){
			return;
		}
		if($user->getAccountInfo()->opts->maskLoc){
			$mask = $user->getAccountInfo()->opts->getMaskLocation($player);
			$user->origPos = $player->getPosition();
			$event->setRespawnPosition($mask);
		}
	}

	public function onSend(DataPacketSendEvent $event){
		$player = $event->getPlayer();
		$user = $this->main->getUserByPlayer($player);
		$pk = $event->getPacket();
		if($pk::NETWORK_ID === ProtocolInfo::CONTAINER_SET_CONTENT_PACKET){
			/** @type ContainerSetContentPacket $pk */
			if($user !== null and $user->isPlaying()){
				return;
			}
			if($player->isSurvival()){ // survival/adventure
				if(
					$pk->windowid === ContainerSetContentPacket::SPECIAL_ARMOR or
					$pk->windowid === ContainerSetContentPacket::SPECIAL_INVENTORY
				){
					$event->setCancelled();
				}
			}
		}
	}
}
