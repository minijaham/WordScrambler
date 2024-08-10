<?php

declare(strict_types=1);

namespace minijaham\WordScrambler\economy;

use minijaham\WordScrambler\WordScrambler;

use pocketmine\player\Player;

use SOFe\Capital\{
    Capital,
    CapitalException,
    LabelSet
};

use Exception;

final class CapitalProvider implements EconomyProvider
{
    private $selector;

    public function __construct() {
        Capital::api("0.1.2", function(Capital $api) {
            $this->selector = $api->completeConfig(WordScrambler::getInstance()->getConfig()->getNested("economy.capital.selector"));
        });
    }

    public function addMoney(Player $player, float $money) : void
    {
        Capital::api("0.1.0", function(Capital $api) use ($player, $money) {
            try {
                yield from $api->addMoney(
                    "HitReward",
                    $player,
                    $this->selector,
                    $money, 
                    new LabelSet(["reason" => "chat-scramble-reward"]),
                );
            } catch(CapitalException $e) {
                throw new Exception("Tried to give " . $player->getName() . " $" . $money . " from chat scramble reward, but the player had too much money.");
            }
        });
    }

    public function formatMoney(float $money) : string
    {
        return WordScrambler::getInstance()->getConfig()->getNested("economy.capital.unit") . number_format($money);
    }
}