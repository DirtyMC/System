<?php

declare(strict_types=1);

namespace BaduBSL\System\command;

use BaduBSL\System\System;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;

class TopStatsCommand extends Command
{

    private System $plugin;

    public function __construct(System $plugin)
    {
        parent::__construct("topstats", "Zeige die besten Spieler einer Statistik an");
        $this->setPermission("system.command.topstats");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        if (count($args) !== 2) {
            $sender->sendMessage(TF::RED . "Benutzung: /topstats <Modus> <Statistik>");
            return true;
        }

        $mode = $args[0];
        $stat = $args[1];

        $stmt = $this->plugin->getStatsAPI()->db->prepare("SELECT player, value FROM stats WHERE game_mode = ? AND stat_key = ? ORDER BY value DESC LIMIT 10");
        $stmt->bind_param("ss", $mode, $stat);
        $stmt->execute();
        $result = $stmt->get_result();

        $sender->sendMessage(TF::AQUA . "--- Top $mode - $stat ---");

        while ($row = $result->fetch_assoc()) {
            $sender->sendMessage(TF::GREEN . $row["player"] . ": " . TF::GOLD . $row["value"]);
        }

        return true;
    }
}