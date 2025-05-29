<?php

declare(strict_types=1);

namespace BaduBSL\System\command;

use BaduBSL\System\System;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;

class SetRankCommand extends Command
{

    private System $plugin;

    public function __construct(System $plugin)
    {
        parent::__construct("setrank", "Setze den Rang eines Spielers");
        $this->setPermission("system.command.setrank");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        if (count($args) !== 2) {
            $sender->sendMessage(TF::RED . "Benutzung: /setrank <Spieler> <Rang>");
            return true;
        }

        $playerName = $args[0];
        $rank = $args[1];

        // Prüfe ob der Rang existiert
        if (!isset($this->plugin->getConfig()->get("ranks")[$rank])) {
            $sender->sendMessage(TF::RED . "Der Rang §f$rank §r§cexistiert nicht.");
            return true;
        }

        // Setze Rang + Berechtigungen
        $this->plugin->getRankAPI()->setRank($playerName, $rank);
        if ($player = $this->plugin->getServer()->getPlayerExact($playerName)) {
            $this->plugin->getRankAPI()->applyPermissions($player);
        }

        $sender->sendMessage(TF::GREEN . "Der Rang von §f$playerName §awurde auf §f$rank §agesetzt.");
        return true;
    }
}