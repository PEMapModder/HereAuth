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

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\Info;
use pocketmine\Player;
use pocketmine\plugin\MethodEventExecutor;

class EventRouter implements Listener{
	/** @type HereAuth */
	private $main;

	public function __construct(HereAuth $main){
		$this->main = $main;
		$this->registerHandler(PlayerLoginEvent::class, "onLogin", EventPriority::MONITOR, true);
		$this->registerHandler(PlayerQuitEvent::class, "onQuit", EventPriority::MONITOR, true);
		$this->registerHandler(PlayerCommandPreprocessEvent::class, "onMessage", EventPriority::LOWEST, false);
		$events = [
			"DropItem",
			"Touch",
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
	}

	private function registerHandler($event, $method, $priority, $ignoreCancelled){
		assert(is_callable([$this, $method]), "Attempt to register nonexistent event handler " . static::class . "::$method");
		$this->main->getServer()->getPluginManager()->registerEvent($event, $this, $priority, new MethodEventExecutor($method), $this->main, $ignoreCancelled);
	}

	public function onLogin(PlayerLoginEvent $event){
		$this->main->startUser($event->getPlayer());
	}

	public function onQuit(PlayerQuitEvent $event){
		$this->main->closeUser($event->getPlayer());
	}

	public function onMessage(PlayerCommandPreprocessEvent $event){
		$player = $event->getPlayer();
		if(($user = $this->main->getUserByPlayer($player)) !== null){
			$user->onMessage($event);
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

	public function onEat(PlayerItemConsumeEvent $event){
		$user = $this->main->getUserByPlayer($event->getPlayer());
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
		if($this->main->getConfig()->getNested("Blocking.Move.Locomotion")){
			if($event->getFrom()->equals($event->getTo())){
				$user = $this->main->getUserByPlayer($event->getPlayer());
				if($user === null or !$user->isPlaying()){
					$event->setCancelled();
					return;
				}
			}
		}
		if($this->main->getConfig()->getNested("Blocking.Move.Rotation")){
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
		if($pk::NETWORK_ID === Info::CONTAINER_SET_CONTENT_PACKET){
			/** @var ContainerSetContentPacket $pk */
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
