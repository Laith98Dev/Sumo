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

namespace vp817;

use pocketmine\plugin\PluginBase;
use vp817\command\SumoCommand;
use vp817\event\ArenaEventListener;
use vp817\event\SetupEventListener;
use vp817\GameLib\GameLib;
use vp817\GameLib\GameLibType;

class Sumo extends PluginBase
{

	private GameLib $gamelib;

	protected function onEnable(): void
	{
		$this->gamelib = GameLib::init($this, GameLibType::MINIGAME(), [ "type" => "sqlite" ]);
		$this->gamelib->setArenasBackupPath($this->getDataFolder() . "backups");
		$this->gamelib->setArenaListenerClass(ArenaEventListener::class);
		$this->gamelib->loadArenas(fn ($arena) => $this->getLogger()->alert("The arena \"" . $arena->getID() . "\" has been loaded"));

		$this->getServer()->getCommandMap()->register("sumo", new SumoCommand($this->gamelib));

		$this->getServer()->getPluginManager()->registerEvents(new SetupEventListener($this->gamelib), $this);
	}

	protected function onDisable(): void
	{
		$this->gamelib->uninit();
	}
}
