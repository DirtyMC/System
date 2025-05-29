<?php

declare(strict_types=1);

namespace BaduBSL\System\API;

use BaduBSL\System\System;
use pocketmine\plugin\Plugin;
use mysqli;

class CoinsAPI {

    private mysqli $db;

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

    public function getCoins(string $player): int {
        $stmt = $this->db->prepare("SELECT coins FROM coins WHERE player = ?");
        $stmt->bind_param("s", $player);
        $stmt->execute();
        $stmt->bind_result($coins);
        $stmt->fetch();
        $stmt->close();
        return $coins ?? 0;
    }

    public function setCoins(string $player, int $amount): void {
        if ($this->getCoins($player) === 0) {
            $stmt = $this->db->prepare("INSERT INTO coins (player, coins) VALUES (?, ?)");
            $stmt->bind_param("si", $player, $amount);
        } else {
            $stmt = $this->db->prepare("UPDATE coins SET coins = ? WHERE player = ?");
            $stmt->bind_param("is", $amount, $player);
        }
        $stmt->execute();
        $stmt->close();
    }

    public function addCoins(string $player, int $amount): void {
        $this->setCoins($player, $this->getCoins($player) + $amount);
    }

    public function removeCoins(string $player, int $amount): void {
        $this->setCoins($player, max(0, $this->getCoins($player) - $amount));
    }

    public function hasEnoughCoins(string $player, int $amount): bool {
        return $this->getCoins($player) >= $amount;
    }
}