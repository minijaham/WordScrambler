<?php

declare(strict_types=1);

namespace minijaham\WordScrambler\economy;

use pocketmine\player\Player;

use onebone\economyapi\EconomyAPI;

final class EconomyAPIProvider implements EconomyProvider
{
    private EconomyAPI $plugin;

	public function __construct() {
		$this->plugin = EconomyAPI::getInstance();
	}

    public function addMoney(Player $player, float $money) : void
    {
		$this->plugin->addMoney($player, $money);
	}

	public function formatMoney(float $money) : string
    {
		return $this->plugin->getMonetaryUnit() . number_format($money);
	}
}