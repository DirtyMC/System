<?php

declare(strict_types=1);

namespace BaduBSL\System\command;

use BaduBSL\System\System;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;

class CoinsCommand extends Command
{

    private System $plugin;

    public function __construct(System $plugin)
    {
        parent::__construct("coins", "Zeige deine Coins an");
        $this->setPermission("system.command.coins");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        $target = count($args) > 0 ? $args[0] : $sender->getName();
        $coins = $this->plugin->getCoinsAPI()->getCoins($target);
        $sender->sendMessage(TF::GREEN . "$target hat §6$coins §gCoins.");
        return true;
    }
}