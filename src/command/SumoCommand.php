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

namespace vp817\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use vp817\GameLib\GameLib;
use vp817\Sumo;

class SumoCommand extends Command
{

	private const ADMIN_SUB_COMMANDS_USAGE = [
		"create" => "/sumo create <id> <worldName> <mode>",
		"remove" => "/sumo remove <id>",
		"list" => "/sumo list",
		"edit" => "/sumo edit <id>"
	];


	private const ADMIN_SUB_COMMANDS_DESCRIPTION = [
		"create" => "create a new sumo arena",
		"remove" => "remove a certain sumo arena",
		"list" => "get the sumo arenas list",
		"edit" => "edit a certain arena settings"
	];

	/**
	 * @param GameLib $gamelib
	 */
	public function __construct(private GameLib $gamelib)
	{
		parent::__construct("sumo", "sumo command");
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 * @return void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): void
	{
		if (empty($args)) {
			$this->sendHelp($sender);
			return;
		}

		$arg0 = strtolower($args[0]);

		switch ($arg0) {
			case "create":
				if (!$sender instanceof Player) {
					$sender->sendMessage(TextFormat::RED . "You will need to be ingame to run this command");
					return;
				}

				if (!$this->testPermission($sender, "sumo.admin")) {
					return;
				}

				if (!isset($args[1])) {
					$sender->sendMessage(self::ADMIN_SUB_COMMANDS_USAGE[$arg0]);
					return;
				}

				if (!isset($args[2])) {
					$sender->sendMessage(self::ADMIN_SUB_COMMANDS_USAGE[$arg0]);
					return;
				}
				
				if (!isset($args[3])) {
					$sender->sendMessage(self::ADMIN_SUB_COMMANDS_USAGE[$arg0]);
					return;
				}

				$this->gamelib->createArena($args[1], $args[2], $args[3], 10, 60 * 5, 10, function ($data) use ($sender): void {
					$arenaID = $data["arenaID"];

					$sender->sendMessage(TextFormat::GREEN . "The arena with the id of: \"{$arenaID}\" has been successfully created");
					$this->gamelib->addPlayerToSetupArena($sender, $arenaID, function ($player) use ($arenaID): void {
						$player->sendMessage(TextFormat::GREEN . "You have joined the setup mode.");
						$player->sendMessage(TextFormat::GOLD . "The arena that you are setuping is: " . TextFormat::AQUA . $arenaID);
						$player->sendMessage(TextFormat::GOLD . "Now type \"help\" in chat to know the setup commands");
					}, fn ($arenaID, $reason) => $sender->sendMessage(TextFormat::RED . "Failed to setup the arena that u created. \"$arenaID\", reason: $reason"));
				}, function ($arenaID, $reason) use ($sender): void {
					$sender->sendMessage(TextFormat::RED . "Unable to create an arena with the id of: {$arenaID}");
					$sender->sendMessage(TextFormat::RED . "reason: {$reason}");
				});
				break;
			case "remove":
				if (!$this->testPermission($sender, "sumo.admin")) {
					return;
				}

				if (!isset($args[1])) {
					$sender->sendMessage(self::ADMIN_SUB_COMMANDS_USAGE[$arg0]);
					return;
				}

				$this->gamelib->removeArena($args[1], function ($arenaID) use ($sender): void {
					$sender->sendMessage(TextFormat::GREEN . "The arena with the id of: \"{$arenaID}\" has been successfully removed");
				}, function ($arenaID, $reason) use ($sender): void {
					$sender->sendMessage(TextFormat::RED . "Unable to create an arena with the id of: {$arenaID}");
					$sender->sendMessage(TextFormat::RED . "reason: {$reason}");
				});
				break;
			case "list":
				if (!$this->testPermission($sender, "sumo.admin")) {
					return;
				}

				$arenasList = $this->gamelib->getArenasManager()->getAll();

				if (empty($arenasList)) {
					$sender->sendMessage(TextFormat::RED . "There is no arenas to list");
					return;
				}

				foreach ($arenasList as $id => $arena) {
					$mode = $arena->getMode();
					$current = $mode->getPlayerCount();
					$max = $mode->getMaxPlayers();

					$sender->sendMessage(TextFormat::GREEN . $id . ": ($current/$max)");
				}
				break;
			case "edit":
				if (!$this->testPermission($sender, "sumo.admin")) {
					return;
				}

				$sender->sendMessage(TextFormat::GOLD . "The command is not implemented");
				break;
			case "join":
				if (!$sender instanceof Player) {
					$sender->sendMessage(TextFormat::RED . "You will need to be ingame to run this command");
					return;
				}

				$withArgs = isset($args[1]);
				if ($withArgs) {
					$this->gamelib->joinArena($sender, $args[1], null, fn ($reason) => $sender->sendMessage($reason));
					return;
				}

				$this->gamelib->joinRandomArena($sender, null, fn ($reason) => $sender->sendMessage($reason));
				break;
			case "quit":
				if (!$sender instanceof Player) {
					$sender->sendMessage(TextFormat::RED . "You will need to be ingame to run this command");
					return;
				}

				$this->gamelib->leaveArena($sender);
				break;
		}
	}

	/**
	 * @param CommandSender $sender
	 * @return void
	 */
	private function sendHelp(CommandSender $sender): void
	{
		$sender->sendMessage(TextFormat::GOLD . "Sumo command help:");
		if ($sender->hasPermission("sumo.admin")) {
			foreach (self::ADMIN_SUB_COMMANDS_USAGE as $cmd => $usage) {
				$sender->sendMessage(TextFormat::GREEN . $usage);
				$sender->sendMessage(TextFormat::GRAY . self::ADMIN_SUB_COMMANDS_DESCRIPTION[$cmd]);
			}
		}

		$sender->sendMessage(TextFormat::GREEN . "/sumo join <id: optional>:");
		$sender->sendMessage(TextFormat::GRAY . "join a certain or random arena");
		$sender->sendMessage(TextFormat::GREEN . "/sumo quit");
		$sender->sendMessage(TextFormat::GRAY . "quit from the sumo arena you are in");
	}
}
