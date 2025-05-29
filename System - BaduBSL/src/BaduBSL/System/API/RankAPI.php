<?php

declare(strict_types=1);

namespace BaduBSL\System\API;

use BaduBSL\System\System;
use pocketmine\player\Player;
use mysqli;

class RankAPI {

    private array $rankData = [];
    private array $playerAttachments = [];

    private mysqli $db;

    public System $plugin;

    public function __construct(System $plugin) {
        $cfg = $plugin->getConfig()->get("mysql");
        $this->db = new mysqli(
            $cfg['host'],
            $cfg['user'],
            $cfg['password'],
            $cfg['database'],
            $cfg['port'] ?? 3306
        );

        // Lade Rangdaten aus der Plugin-Config
        $this->rankData = $plugin->getConfig()->get("ranks", []);

        $this->plugin = $plugin;
    }

    public function getRank(string $player): string {
        $stmt = $this->db->prepare("SELECT rank FROM ranks WHERE player = ?");
        $stmt->bind_param("s", $player);
        $stmt->execute();
        $stmt->bind_result($rank);
        $stmt->fetch();
        $stmt->close();
        return $rank ?? "default";
    }

    public function setRank(string $player, string $rank): void {
        $stmt = $this->db->prepare("REPLACE INTO ranks (player, rank) VALUES (?, ?)");
        $stmt->bind_param("ss", $player, $rank);
        $stmt->execute();
        $stmt->close();
    }

    public function getChatFormat(string $playerName): string {
        $rank = $this->getRank($playerName);
        $format = $this->rankData[$rank]["chat-format"] ?? "<{name}> {message}";
        return $this->colorize($format);
    }

    public function getNameTagFormat(string $playerName): string {
        $rank = $this->getRank($playerName);
        $format = $this->rankData[$rank]["nametag-format"] ?? "{name}";
        return $this->colorize($format);
    }

    private function colorize(string $input): string {
        return str_replace("&", "§", $input);
    }

    public function updateNameTag(Player $player): void {
        $name = $player->getName();
        $format = $this->getNameTagFormat($name);
        $tag = str_replace("{name}", $name, $format);
        $player->setNameTag($tag);
    }

    public function formatMessage(string $playerName, string $message): string {
        $format = $this->getChatFormat($playerName);
        return str_replace(["{name}", "{message}"], [$playerName, $message], $format);
    }

    /**
     * Wende die Berechtigungen des Rangs an
     */
    public function applyPermissions(Player $player): void {
        // Entferne alte Berechtigungen
        if (isset($this->attachments[$player->getName()])) {
            $player->removeAttachment($this->attachments[$player->getName()]);
        }

        // Erstelle neuen Attachment
        $attachment = $player->addAttachment($this->plugin);

        // Hole aktuellen Rang und setze Permissions
        $rank = $this->getRank($player->getName());
        $permissions = $this->rankData[$rank]["permissions"] ?? [];

        foreach ($permissions as $perm) {
            $attachment->setPermission($perm, true); // ✔️ Nur noch true/false erlaubt
        }

        $this->attachments[$player->getName()] = $attachment;
    }

    /**
     * Entferne alle aktiven Berechtigungen
     */
    public function removePermissions(Player $player): void {
        $name = strtolower($player->getName());

        if (isset($this->playerAttachments[$name])) {
            $player->removeAttachment($this->playerAttachments[$name]);
            unset($this->playerAttachments[$name]);
        }
    }

    /**
     * Prüfe ob ein Spieler eine Berechtigung hat
     */
    public function hasPermission(Player $player, string $permission): bool {
        return $player->hasPermission($permission);
    }
}