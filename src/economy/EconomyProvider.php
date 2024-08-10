<?php

declare(strict_types=1);

namespace minijaham\WordScrambler\economy;

use pocketmine\player\Player;

interface EconomyProvider
{
	/**
	 * Adds a given amount of money to the player.
	 *
	 * @param Player $player
	 * @param float $money
	 */
	public function addMoney(Player $player, float $money) : void;

	/**
	 * Formats money.
	 *
	 * @param float $money
	 * 
	 * @return string
	 */
	public function formatMoney(float $money) : string;
}