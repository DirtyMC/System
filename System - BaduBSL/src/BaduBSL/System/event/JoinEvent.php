<?php

declare(strict_types=1);

namespace BaduBSL\System\event;

use BaduBSL\System\System;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class JoinEvent implements Listener
{

    private $plugin;

    public function __construct(System $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        // Setze Standardwerte, falls Spieler neu ist
        $this->ensurePlayerExistsInDatabase($name);

        // Aktualisiere Nametag basierend auf Rang
        $this->plugin->getRankAPI()->updateNameTag($player);

        $this->plugin->getRankAPI()->applyPermissions($player);

        // Prüfe ob der Spieler gebannt ist
        if ($this->plugin->getBanAPI()->isBanned($name)) {
            $reason = $this->plugin->getBanAPI()->getBanReason($name);
            $player->kick("§cDu bist gebannt.\n§7Grund: §f$reason");
        }

        // Optional: Keine Join-Nachricht
        $event->setJoinMessage("");
    }

    private function ensurePlayerExistsInDatabase(string $playerName): void {
        $cfg = $this->plugin->getConfig()->get("mysql");

        $db = new \mysqli(
            $cfg['host'],
            $cfg['user'],
            $cfg['password'],
            $cfg['database'],
            $cfg['port'] ?? 3306
        );

        // Prüfe ob Spieler in coins.yml existiert
        $stmt = $db->prepare("SELECT player FROM coins WHERE player = ?");
        $stmt->bind_param("s", $playerName);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            // Spieler ist neu → setze Standardwerte
            $this->initializePlayerData($playerName);
        }

        $stmt->close();
    }

    private function initializePlayerData(string $playerName): void {
        $cfg = $this->plugin->getConfig()->get("mysql");

        $db = new \mysqli(
            $cfg['host'],
            $cfg['user'],
            $cfg['password'],
            $cfg['database'],
            $cfg['port'] ?? 3306
        );

        // Coins setzen
        $stmt = $db->prepare("INSERT INTO coins (player, coins) VALUES (?, 0)");
        $stmt->bind_param("s", $playerName);
        $stmt->execute();
        $stmt->close();

        // Rang setzen (default)
        $stmt = $db->prepare("INSERT INTO ranks (player, rank) VALUES (?, 'default')");
        $stmt->bind_param("s", $playerName);
        $stmt->execute();
        $stmt->close();

        // Stats setzen (alle Modi und Statistiken auf 0)
        $modes = ["KnockbackFFA", "SkyWars"];
        $stats = ["Kills", "Deaths", "Wins"];

        foreach ($modes as $mode) {
            foreach ($stats as $stat) {
                $stmt = $db->prepare("INSERT INTO stats (player, game_mode, stat_key, value) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("sss", $playerName, $mode, $stat);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}