<?php

declare(strict_types=1);

namespace minijaham\WordScrambler\economy;

use pocketmine\Server;
use pocketmine\player\Player;

use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use cooldogedev\BedrockEconomy\BedrockEconomy;
use InvalidArgumentException;

final class BedrockEconomyProvider implements EconomyProvider
{
	private BedrockEconomy $plugin;

	public function __construct() {
		$plugin = Server::getInstance()->getPluginManager()->getPlugin("BedrockEconomy") ?? throw new InvalidArgumentException("BedrockEconomy plugin was not found");
		$this->plugin = $plugin;
	}

	public function addMoney(Player $player, float $money) : void
	{
		BedrockEconomyAPI::getInstance()->addToPlayerBalance($player->getName(), (int) ceil($money));
	}

	public function formatMoney(float $money) : string
	{
		return $this->plugin->getCurrencyManager()->getSymbol() . number_format($money);
	}
}