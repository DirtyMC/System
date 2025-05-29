<?php

declare(strict_types=1);

namespace BaduBSL\System\command;

use BaduBSL\System\System;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;

class TempMuteCommand extends Command
{
    private System $plugin;

    public function __construct(System $plugin){
        parent::__construct("tempmute", "Mute einen Spieler zeitlich begrenzt");
        $this->setPermission("system.command.tempmute");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (count($args) < 2) {
            $this->sendUsage($sender);
            return true;
        }

        $playerName = strtolower($args[0]);
        $reasonKey = strtolower($args[1]);

        // Hole Mute-Gründe aus config.yml
        $reasons = $this->plugin->getConfig()->get("mute_reasons", []);

        if (!isset($reasons[$reasonKey])) {
            $sender->sendMessage(TF::RED . "Ungültiger Grund. Gültige Gründe: " . TF::YELLOW . implode(", ", array_keys($reasons)));
            return true;
        }

        $duration = (int)$reasons[$reasonKey];

        // Setze Mute
        $this->plugin->getMuteAPI()->mutePlayer($playerName, $reasonKey, $duration);
        $sender->sendMessage(TF::GREEN . "Spieler §f$playerName §awurde für §f" . $this->formatTime($duration) . " §agemuted (Grund: §f $reasonKey §a).");

        return true;
    }

    private function formatTime(int $seconds): string {
        if ($seconds < 60) return "$seconds Sekunden";
        if ($seconds < 3600) return floor($seconds / 60) . " Minuten";
        if ($seconds < 86400) return floor($seconds / 3600) . " Stunden";
        return floor($seconds / 86400) . " Tage";
    }

    private function sendUsage(CommandSender $sender): void {
        $sender->sendMessage($this->getUsage());
    }
}