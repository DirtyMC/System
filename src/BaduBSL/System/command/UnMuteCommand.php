<?php

declare(strict_types=1);

namespace BaduBSL\System\command;

use BaduBSL\System\System;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;

class UnMuteCommand extends Command
{

    private System $plugin;

    public function __construct(System $plugin)
    {
        parent::__construct("unmute", "Entmute einen Spieler");
        $this->setPermission("system.command.unmute");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        if (count($args) !== 1) {
            $this->sendUsage($sender);
            return true;
        }

        $playerName = strtolower($args[0]);

        // Entmute den Spieler
        if ($this->plugin->getMuteAPI()->isMuted($playerName)) {
            $this->plugin->getMuteAPI()->unmutePlayer($playerName);
            $sender->sendMessage(TF::GREEN . "Spieler §f$playerName §awurde entmutet.");
        } else {
            $sender->sendMessage(TF::YELLOW . "Spieler §f$playerName §cist nicht gemuted.");
        }

        return true;
    }

    private function sendUsage(CommandSender $sender): void
    {
        $sender->sendMessage($this->getUsage());
    }
}