<?php

/**
 *
 *                         .ooooo.     .o   ooooooooo
 *                        d88'   `8. o888  d"""""""8'
 * oooo    ooo oo.ooooo.  Y88..  .8'  888        .8'
 *  `88.  .8'   888' `88b  `88888b.   888       .8'
 *   `88..8'    888   888 .8'  ``88b  888      .8'
 *    `888'     888   888 `8.   .88P  888     .8'
 *     `8'      888bod8P'  `boood8'  o888o   .8'
 *              888
 *             o888o
 *
 * @author vp817
 * @youtube VaxPex
 * @github vp817
 * @discord Nazi#2267
 *
 * Copyright (C) 2023  vp817
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 */

declare(strict_types=1);

namespace vp817\event;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use vp817\GameLib\arena\modes\ArenaModes;
use vp817\GameLib\arena\states\ArenaStates;
use vp817\GameLib\event\ArenaEndEvent;
use vp817\GameLib\event\ArenaTickEvent;
use vp817\GameLib\event\PlayerArenaWinEvent;
use vp817\GameLib\event\PlayerJoinArenaEvent;
use vp817\GameLib\event\PlayerQuitArenaEvent;
use vp817\GameLib\event\listener\DefaultArenaListener;

class ArenaEventListener extends DefaultArenaListener
{

	/** @var Player[] $combatVictims */
	public array $combatVictims = [];
	/** @var Player[] $combatCriminals */
	public array $combatCriminals = [];
	/** @var int[] $combatTimers */
	public array $combatTimers = [];
	/** @var Player[] $spectators */
	public array $spectators = [];
	/** @var Player[] $crminialsWithKills */
	public array $crminialsWithKills = [];
	/** @var Player[] $victimsThatGotKilled */
	public array $victimsThatGotKilled = [];
	/** @var Player[] $victimsWithNoRecord */
	public array $victimsWithNoRecord = [];
	/** @var Player[] $rwVictimsWithNoRecordGK */
	public array $rwVictimsWithNoRecordGK = [];
	
	/** @var string[] $combatDamage */
	public array $combatDamage = [];

	/**
	 * @param PlayerJoinArenaEvent $event
	 * @return void
	 */
	public function onJoin(PlayerJoinArenaEvent $event): void
	{
		$player = $event->getPlayer();
		$player->initBasic();
		$player->setFood(20);
	}

	/**
	 * @param PlayerQuitArenaEvent $event
	 * @return void
	 */
	public function onQuit(PlayerQuitArenaEvent $event): void
	{
		$player = $event->getPlayer();
		$player->deinitBasic();
	}

	/**
	 * @param ArenaTickEvent $event
	 * @return void
	 */
	public function onTick(ArenaTickEvent $event): void
	{
		$arena = $event->getArena();
		$messageBroadcaster = $arena->getMessageBroadcaster();
		$mode = $arena->getMode();
		$state = $event->getState();
		$timer = $event->getTimer();

		if ($state->equals(ArenaStates::WAITING())) {
			$messageBroadcaster->broadcastPopup(TextFormat::GOLD . "Waiting for (" . $mode->getPlayerCount() . "/" . $mode->getMaxPlayers() . ")");
		} else if ($state->equals(ArenaStates::COUNTDOWN())) {
			if ($timer < 1) {
				return;
			}

			$color = match ($timer) {
				10 => TextFormat::RED,
				9 => TextFormat::RED,
				8 => TextFormat::RED,
				7 => TextFormat::GOLD,
				6 => TextFormat::GOLD,
				5 => TextFormat::GREEN,
				4 => TextFormat::GREEN,
				3 => TextFormat::GREEN,
				2 => TextFormat::GREEN,
				1 => TextFormat::GREEN
			};
			$messageBroadcaster->broadcastTitle($color . strval($timer));
		} else if ($state->equals(ArenaStates::INGAME())) {
			$messageBroadcaster->broadcastTip(TextFormat::GOLD . "MaxTime: " . TextFormat::GREEN . strval($timer));

			if ($mode->getPlayerCount() < 2) {
				$mode->endGame($arena);
			}
		} else if ($state->equals(ArenaStates::RESTARTING())) {
			$messageBroadcaster->broadcastTitle(TextFormat::GOLD . "Restarting in:");
			$messageBroadcaster->broadcastSubTitle(TextFormat::GREEN . strval($timer));
		}
	}

	/**
	 * @param PlayerArenaWinEvent $event
	 * @return void
	 */
	public function onWin(PlayerArenaWinEvent $event): void
	{
		$arena = $event->getArena();
		$mode = $arena->getMode();
		$winners = $arena->getWinners();

		if ($mode->equals(ArenaModes::SOLO())) {
			$winner = array_shift($winners);
			$winner->getCells()->sendTitle(TextFormat::GREEN . "You won!");
			$winner->getCells()->sendMessage(TextFormat::GOLD . "No coins cuz u dont know how to play");
			return;
		}

		foreach ($winners as $bytes => $winner) {
			$winner->getCells()->sendTitle(TextFormat::GREEN . "You won!");
			$winner->getCells()->sendMessage(TextFormat::GOLD . "No coins cuz u dont know how to play");
		}
	}

	/**
	 * @param ArenaEndEvent $event
	 * @return void
	 */
	public function onEnd(ArenaEndEvent $event): void
	{
		$arena = $event->getArena();
		$state = $arena->getState();

		if (!$state->equals(ArenaStates::RESTARTING())) {
			return;
		}

		foreach ($arena->getMode()->getPlayers() as $player) {
			$arena->quit($player->getCells(), null, fn($reason) => $player->getCells()->sendMessage(TextFormat::RED . $reason), false, true);
		}
	}

	/**
	 * @param string $cause
	 * @param Player $player
	 * @return void
	 */
	public function eliminatePlayer(string $cause, Player $player): void
	{
		$arena = $this->arena;
		$messageBroadcaster = $arena->getMessageBroadcaster();

		$bytes = $player->getUniqueId()->getBytes();

		if(isset($this->combatDamage[$bytes])){
			$cause = $this->combatDamage[$bytes];
		}

		$messageBroadcaster->broadcastMessage(TextFormat::GOLD . $player->getName() . TextFormat::AQUA . " has been eliminated by " . TextFormat::RED . $cause);

		$this->setSpectator($player);

		$arena->getMode()->endGame($arena);
	}

	/**
	 * @param Player $player
	 * @return void
	 */
	public function setSpectator(Player $player): void
	{
		$bytes = $player->getUniqueId()->getBytes();
		if (array_key_exists($bytes, $this->spectators)) {
			return;
		}
		$player->setGamemode(GameMode::SPECTATOR());
		$this->spectators[$bytes] = $player;
		$player->teleport($this->arena->getLobbySettings()->getLocation());
	}

	/**
	 * @param EntityDamageEvent $event
	 * @return void
	 */
	public function onDamage(EntityDamageEvent $event): void
	{
		$arena = $this->arena;
		$mode = $arena->getMode();
		$state = $arena->getState();
		$victim = $event->getEntity();

		if (!$victim instanceof Player) {
			return;
		}

		$victimBytes = $victim->getUniqueId()->getBytes();

		if (!array_key_exists($victimBytes, $mode->getPlayers())) {
			return;
		}

		$cause = $event->getCause();

		if ($event instanceof EntityDamageByEntityEvent) {
			$criminal = $event->getDamager();

			if (!$criminal instanceof Player) {
				return;
			}

			if ($state->equals(ArenaStates::WAITING()) || $state->equals(ArenaStates::COUNTDOWN()) || $state->equals(ArenaStates::RESTARTING())) {
				$event->cancel();
			}

			if ($victim->getHealth() < 20) $victim->setHealth(20);

			$bytes = $victim->getUniqueId()->getBytes();

			$this->combatDamage[$bytes] = $criminal->getName();

			$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($bytes){
				if(isset($this->combatDamage[$bytes])){
					unset($this->combatDamage[$bytes]);
				}
			}), 5 * 20);
			
		}

		switch ($cause) {
			case EntityDamageEvent::CAUSE_VOID:
			case EntityDamageEvent::CAUSE_LAVA:
			case EntityDamageEvent::CAUSE_FIRE:
			case EntityDamageEvent::CAUSE_FALL:
				$this->eliminatePlayer(match ($cause) {
					EntityDamageEvent::CAUSE_VOID => "Void",
					EntityDamageEvent::CAUSE_LAVA => "Lava",
					EntityDamageEvent::CAUSE_FIRE => "Fire",
					EntityDamageEvent::CAUSE_FALL => "Void",
					default => "Unknown"
				}, $victim);
				break;
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 * @return void
	 */
	public function onBreak(BlockBreakEvent $event): void
	{
		$arena = $this->arena;
		$mode = $arena->getMode();
		$player = $event->getPlayer();
		$bytes = $player->getUniqueId()->getBytes();

		if (!array_key_exists($bytes, $mode->getPlayers())) {
			return;
		}

		$event->cancel();
	}

	/**
	 * @param BlockPlaceEvent $event
	 * @return void
	 */
	public function onPlace(BlockPlaceEvent $event): void
	{
		$arena = $this->arena;
		$mode = $arena->getMode();
		$player = $event->getPlayer();
		$bytes = $player->getUniqueId()->getBytes();

		if (!array_key_exists($bytes, $mode->getPlayers())) {
			return;
		}

		$event->cancel();
	}

	/**
	 * @param PlayerExhaustEvent $event
	 * @return void
	 */
	public function onExhaust(PlayerExhaustEvent $event): void
	{
		$arena = $this->arena;
		$mode = $arena->getMode();
		$player = $event->getPlayer();
		$bytes = $player->getUniqueId()->getBytes();

		if (!array_key_exists($bytes, $mode->getPlayers())) {
			return;
		}

		$event->cancel();
	}
}
