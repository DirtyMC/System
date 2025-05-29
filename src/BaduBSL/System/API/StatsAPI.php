<?php

declare(strict_types=1);

namespace BaduBSL\System\API;

use BaduBSL\System\System;
use mysqli;

class StatsAPI {

    public mysqli $db;

    public function __construct(System $plugin) {
        $cfg = $plugin->getConfig()->get("mysql");
        $this->db = new mysqli(
            $cfg['host'],
            $cfg['user'],
            $cfg['password'],
            $cfg['database'],
            $cfg['port'] ?? 3306
        );
    }

    public function getStat(string $player, string $gameMode, string $statKey): int {
        $stmt = $this->db->prepare("SELECT value FROM stats WHERE player = ? AND game_mode = ? AND stat_key = ?");
        $stmt->bind_param("sss", $player, $gameMode, $statKey);
        $stmt->execute();
        $stmt->bind_result($value);
        $stmt->fetch();
        $stmt->close();
        return $value ?? 0;
    }

    public function setStat(string $player, string $gameMode, string $statKey, int $value): void {
        $stmt = $this->db->prepare("REPLACE INTO stats (player, game_mode, stat_key, value) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $player, $gameMode, $statKey, $value);
        $stmt->execute();
        $stmt->close();
    }

    public function addStat(string $player, string $gameMode, string $statKey, int $amount): void {
        $current = $this->getStat($player, $gameMode, $statKey);
        $this->setStat($player, $gameMode, $statKey, $current + $amount);
    }

    public function removeStat(string $player, string $gameMode, string $statKey, int $amount): void {
        $current = $this->getStat($player, $gameMode, $statKey);
        $this->setStat($player, $gameMode, $statKey, max(0, $current - $amount));
    }

    public function getAllStats(string $player): array {
        $data = [];
        $stmt = $this->db->prepare("SELECT game_mode, stat_key, value FROM stats WHERE player = ?");
        $stmt->bind_param("s", $player);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[$row["game_mode"]][$row["stat_key"]] = (int)$row["value"];
        }
        $stmt->close();
        return $data;
    }
}