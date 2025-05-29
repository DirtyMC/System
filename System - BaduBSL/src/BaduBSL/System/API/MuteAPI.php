<?php

declare(strict_types=1);

namespace BaduBSL\System\API;

use BaduBSL\System\System;
use mysqli;

class MuteAPI
{

    private mysqli $db;

    public function __construct(System $plugin)
    {
        $cfg = $plugin->getConfig()->get("mysql");
        $this->db = new mysqli(
            $cfg['host'],
            $cfg['user'],
            $cfg['password'],
            $cfg['database'],
            $cfg['port'] ?? 3306
        );
    }

    public function isMuted(string $player): bool
    {
        $stmt = $this->db->prepare("SELECT player FROM mutes WHERE player = ? AND (expires IS NULL OR expires > ?)");
        $now = time();
        $stmt->bind_param("si", $player, $now);
        $stmt->execute();
        $stmt->store_result();
        $result = $stmt->num_rows > 0;
        $stmt->close();
        return $result;
    }

    public function mutePlayer(string $player, string $reason, ?int $duration = null): void
    {
        $expires = $duration !== null ? time() + $duration : null;

        $stmt = $this->db->prepare("REPLACE INTO mutes (player, reason, expires) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $player, $reason, $expires);
        $stmt->execute();
        $stmt->close();
    }

    public function unmutePlayer(string $player): void
    {
        $stmt = $this->db->prepare("DELETE FROM mutes WHERE player = ?");
        $stmt->bind_param("s", $player);
        $stmt->execute();
        $stmt->close();
    }

    public function getMuteReason(string $player): string
    {
        $stmt = $this->db->prepare("SELECT reason FROM mutes WHERE player = ?");
        $stmt->bind_param("s", $player);
        $stmt->execute();
        $stmt->bind_result($reason);
        $stmt->fetch();
        $stmt->close();
        return $reason ?? "Unbekannt";
    }

    public function cleanExpiredMutes(): void
    {
        $now = time();
        $stmt = $this->db->prepare("DELETE FROM mutes WHERE expires <= ?");
        $stmt->bind_param("i", $now);
        $stmt->execute();
        $stmt->close();
    }
}