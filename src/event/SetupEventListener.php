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

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;
use vp817\GameLib\GameLib;
use vp817\GameLib\player\SetupPlayer;

class SetupEventListener implements Listener
{

	public const SETUP_COMMANDS = [
		"lobby" => "set the lobby that the player will teleport to",
		"spawn1" => "set the first spawn for the player that will get teleported as first",
		"spawn2" => "set the second spawn for the player that will get teleported as second",
		"finish" => "mark the arena as that it has been setuped",
	];

	/**
	 * @param GameLib $gamelib
	 */
	public function __construct(private GameLib $gamelib)
	{
	}

	/**
	 * @param PlayerChatEvent $event
	 * @return void
	 */
	public function onChat(PlayerChatEvent $event): void
	{
		$player = $event->getPlayer();
		$bytes = $player->getUniqueId()->getBytes();
		$setupManager = $this->gamelib->getSetupManager();
		$message = $event->getMessage();

		if (!$setupManager->has($bytes)) {
			return;
		}

		$event->cancel();

		$this->gamelib->getSetupManager()->get($bytes, function (SetupPlayer $player) use ($message): void {
			$cells = $player->getCells();
			$currentWorld = $cells->getWorld();
			$currentWorldName = $currentWorld->getFolderName();
			$cellsLocation = $cells->getLocation();
			$arenaID = $player->getSetupingArenaID();
			$settings = $player->getSetupSettings();

			switch (strtolower($message)) {
				case "help":
					$cells->sendMessage(TextFormat::GOLD . "Sumo setup commands:");
					foreach (self::SETUP_COMMANDS as $command => $description) {
						$cells->sendMessage(TextFormat::AQUA . $command . ": " . TextFormat::GREEN . $description);
					}
					break;
				case "lobby":
					$cells->sendMessage(TextFormat::GOLD . "The lobby has been updated to: " . $currentWorldName);
					$settings->setLobbySettings($currentWorldName, $cellsLocation);
					break;
				case "spawn1":
					$cells->sendMessage(TextFormat::GOLD . "The spawn1 has been updated to your pos");
					$settings->setSpawn(1, $cellsLocation);
					break;
				case "spawn2":
					$cells->sendMessage(TextFormat::GOLD . "The spawn2 has been updated to your pos");
					$settings->setSpawn(2, $cellsLocation);
					break;
				case "finish":
					$settings->setArenaData(["slots" => 2]); // only for solo
					$this->gamelib->finishArenaSetup($cells, fn ($arena) => $cells->sendMessage(TextFormat::GOLD . "The arena \"$arenaID\" has been marked as it has been setuped"));
					break;
				default:
					$cells->sendMessage(TextFormat::GRAY . "Type \"help\" to get the setup commands help");
					break;
			}
		});
	}
}
