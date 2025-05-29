<?php

declare(strict_types=1);

namespace BaduBSL\System\command;

use BaduBSL\System\System;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class TempBanCommand extends Command
{

    private System $plugin;

    public function __construct(System $plugin)
    {
        parent::__construct("tempban", "Banne einen Spieler temporär");
        $this->setPermission("system.command.tempban");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (count($args) < 2) {
            $this->sendUsage($sender);
            return true;
        }

        $playerName = strtolower($args[0]);
        $reasonKey = strtolower($args[1]);

        // Hole alle Grundeinstellungen
        $reasons = $this->plugin->getConfig()->get("ban_reasons", []);

        if (!isset($reasons[$reasonKey])) {
            $sender->sendMessage(TF::RED . "Ungültiger Grund. Mögliche Gründe: " . TF::YELLOW . implode(", ", array_keys($reasons)));
            return true;
        }

        $duration = (int)$reasons[$reasonKey];
        $reason = $reasonKey;

        // Setze Ban
        $this->plugin->getBanAPI()->banPlayer($playerName, $reason, $duration);

        // Kick, falls online
        if ($target = $this->plugin->getServer()->getPlayerExact($playerName)) {
            $target->kick(TF::RED . "Du bist gebannt!\n§fGrund: §c$reason\n§fDauer: §c" . $this->formatTime($duration));
        }

        $sender->sendMessage(TF::GREEN . "Spieler §f$playerName §awurde für §f" . $this->formatTime($duration) . " §a(§f $reason §a) gebannt.");

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