<?php

declare(strict_types=1);

namespace minijaham\WordScrambler;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat as C;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

use minijaham\WordScrambler\economy\EconomyProvider;
use minijaham\WordScrambler\economy\EconomyAPIProvider;
use minijaham\WordScrambler\economy\BedrockEconomyProvider;
# use minijaham\WordScrambler\economy\CapitalProvider;

use InvalidArgumentException;

final class WordScrambler extends PluginBase implements Listener
{
    use SingletonTrait;

    private array $words = [];
    private ?string $currentWord = null;
    private ?int $currentReward  = null;
    private ?EconomyProvider $economyProvider = null;

    private const CONFIG_WORDS            = 'words';
    private const CONFIG_INTERVAL         = 'interval';
    private const CONFIG_PLAYER_COUNT     = 'player-count';
    private const CONFIG_MATCH_EXACT      = 'match-exact';
    private const CONFIG_GAME_MESSAGE     = 'format.game-message';
    private const CONFIG_WIN_MESSAGE      = 'format.win-message';
    private const CONFIG_ECONOMY_PROVIDER = 'economy.provider';
    private const CONFIG_MIN_AMOUNT       = 'economy.min-amount';
    private const CONFIG_MAX_AMOUNT       = 'economy.max-amount';

    /**
     * Loader Function
     *
     * @return void
     */
    protected function onLoad() : void
    {
        self::setInstance($this);
        $this->saveDefaultConfig();
    }

    /**
     * Enable Function
     *
     * @return void
     */
    protected function onEnable() : void
    {
        $config = $this->getConfig();

        $this->words = $config->get(self::CONFIG_WORDS, []);
        $this->setEconomyProvider($this->initializeEconomyProvider($config->getNested(self::CONFIG_ECONOMY_PROVIDER)));

        $this->getScheduler()->scheduleRepeatingTask(new class extends Task {
            public function onRun(): void
            {
                WordScrambler::getInstance()->setRandomWord();
            }
        }, $config->get(self::CONFIG_INTERVAL));

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * Event Listener
     *
     * @param PlayerChatEvent $event
     * @return void
     */
    public function onChat(PlayerChatEvent $event) : void
    {
        if ($this->currentWord === null) {
            return;
        }

        $message = $event->getMessage();
        $config  = $this->getConfig();

        $guess = $config->get(self::CONFIG_MATCH_EXACT) ? $message : strtolower($message);
        $word  = $config->get(self::CONFIG_MATCH_EXACT) ? $this->currentWord : strtolower($this->currentWord);

        if ($guess === $word) {
            $this->reward($event->getPlayer());
            $event->cancel();
        }
    }

    /**
     * Set a random word in-play
     *
     * @return void
     */
    public function setRandomWord() : void
    {
        $server = $this->getServer();
        $config = $this->getConfig();

        if (count($server->getOnlinePlayers()) < $config->get(self::CONFIG_PLAYER_COUNT)) {
            return;
        }

        $this->currentWord = $this->words[array_rand($this->words)];
        $this->currentReward = rand(
            $config->getNested(self::CONFIG_MIN_AMOUNT),
            $config->getNested(self::CONFIG_MAX_AMOUNT)
        );

        $server->broadcastMessage(C::colorize(str_replace(
            ['{word}', '{money}'],
            [str_shuffle($this->currentWord), $this->getEconomyProvider()->formatMoney($this->currentReward)],
            $config->getNested(self::CONFIG_GAME_MESSAGE)
        )));
    }

    /**
     * Rewards player
     *
     * @param Player $player
     * @return void
     */
    public function reward(Player $player) : void
    {
        $this->getEconomyProvider()->addMoney($player, $this->currentReward);

        $this->getServer()->broadcastMessage(C::colorize(str_replace(
            ['{word}', '{player}'],
            [$this->currentWord, $player->getName()],
            $this->getConfig()->getNested(self::CONFIG_WIN_MESSAGE)
        )));

        $this->currentWord = null;
        $this->currentReward = null;
    }

    /**
     * Returns the current EconomyProvider
     *
     * @return EconomyProvider|null
     */
    public function getEconomyProvider() : ?EconomyProvider
    {
        return $this->economyProvider;
    }

    /**
     * Sets the EconomyProvider
     *
     * @param EconomyProvider|null $economyProvider
     * @throws InvalidArgumentException
     * @return void
     */
    private function setEconomyProvider(?EconomyProvider $economyProvider) : void
    {
        if ($economyProvider === null) {
            throw new InvalidArgumentException('Invalid economy provider specified. Please check the config.');
        }

        $this->economyProvider = $economyProvider;
    }

    /**
     * Initializes the appropriate EconomyProvider based on configuration
     *
     * @param string|null $providerName
     * @return EconomyProvider|null
     */
    private function initializeEconomyProvider(?string $providerName) : ?EconomyProvider
    {
        return match (strtolower($providerName ?? '')) {
            'economyapi', 'economys' => new EconomyAPIProvider(),
            'bedrockeconomy' => new BedrockEconomyProvider(),
            # 'capital' => new CapitalProvider(), # No longer supported as of PM5...
            default => null,
        };
    }
}
