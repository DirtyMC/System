<?php

declare(strict_types=1);

namespace BaduBSL\System\command;

use BaduBSL\System\System;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;

class UnBanCommand extends Command
{

    private System $plugin;

    public function __construct(System $plugin)
    {
        parent::__construct("unban", "Entbanne einen Spieler");
        $this->setPermission("system.command.unban");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        if (count($args) !== 1) {
            $this->sendUsage($sender);
            return true;
        }

        $playerName = strtolower($args[0]);

        // Entbanne den Spieler
        if ($this->plugin->getBanAPI()->isBanned($playerName)) {
            $this->plugin->getBanAPI()->unbanPlayer($playerName);
            $sender->sendMessage(TF::GREEN . "Spieler §f$playerName §awurde entbannt.");
        } else {
            $sender->sendMessage(TF::YELLOW . "Spieler §f$playerName §cist nicht gebannt.");
        }

        return true;
    }

    private function sendUsage(CommandSender $sender): void
    {
        $sender->sendMessage($this->getUsage());
    }
}