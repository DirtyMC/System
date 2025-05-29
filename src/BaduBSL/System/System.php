<?php

declare(strict_types=1);

namespace BaduBSL\System;

use BaduBSL\System\API\BanAPI;
use BaduBSL\System\API\CoinsAPI;
use BaduBSL\System\API\MuteAPI;
use BaduBSL\System\API\RankAPI;
use BaduBSL\System\API\StatsAPI;
use BaduBSL\System\command\CoinsCommand;
use BaduBSL\System\command\SetRankCommand;
use BaduBSL\System\command\TempBanCommand;
use BaduBSL\System\command\TempMuteCommand;
use BaduBSL\System\command\TopStatsCommand;
use BaduBSL\System\command\UnBanCommand;
use BaduBSL\System\command\UnMuteCommand;
use BaduBSL\System\event\ChatEvent;
use BaduBSL\System\event\JoinEvent;
use BaduBSL\System\task\BanTask;
use BaduBSL\System\task\MuteTask;
use pocketmine\plugin\PluginBase;

class System extends PluginBase
{

    private static ?self $instance = null;

    private BanAPI $banAPI;
    private CoinsAPI $coinsAPI;
    private MuteAPI $muteAPI;
    private RankAPI $rankAPI;
    private StatsAPI $statsAPI;

    public static function getInstance(): self {
        return self::$instance;
    }

    public function onLoad(): void
    {
        // Stellt sicher, dass die config.yml im Datenordner existiert
        $this->saveDefaultConfig();

        // Lade die Konfiguration
        $this->reloadConfig();
        $cfg = $this->getConfig()->get("mysql");

        // Prüfe, ob die mysql-Konfiguration ein Array ist
        if (!is_array($cfg)) {
            $this->getLogger()->error("MySQL-Konfiguration nicht gefunden oder ungültig in config.yml");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $cfg = $this->getConfig()->get("mysql");

        // Verbinde mit MySQL
        $mysqli = new \mysqli(
            $cfg['host'],
            $cfg['user'],
            $cfg['password'],
            $cfg['database'],
            $cfg['port'] ?? 3306
        );

        if ($mysqli->connect_error) {
            $this->getLogger()->error("Verbindung zur MySQL-Datenbank fehlgeschlagen: " . $mysqli->connect_error);
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        // Erstelle alle benötigten Tabellen, wenn sie nicht existieren
        $queries = [
            "CREATE TABLE IF NOT EXISTS coins (
                player VARCHAR(36) PRIMARY KEY,
                coins INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS bans (
                player VARCHAR(36) PRIMARY KEY,
                reason TEXT,
                expires BIGINT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS mutes (
                player VARCHAR(36) PRIMARY KEY,
                reason TEXT,
                expires BIGINT DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS stats (
                player VARCHAR(36),
                game_mode VARCHAR(32),
                stat_key VARCHAR(32),
                value INT NOT NULL,
                PRIMARY KEY (player, game_mode, stat_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS ranks (
                player VARCHAR(36) PRIMARY KEY,
                rank VARCHAR(32) NOT NULL DEFAULT 'default'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $query) {
            if (!$mysqli->query($query)) {
                $this->getLogger()->error("Fehler beim Erstellen der Tabelle: " . $mysqli->error);
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }

        $mysqli->close();

    }

    public function onEnable(): void {
        self::$instance = $this;

        // Initialisiere APIs
        $this->banAPI = new BanAPI($this);
        $this->coinsAPI = new CoinsAPI($this);
        $this->muteAPI = new MuteAPI($this);
        $this->rankAPI = new RankAPI($this);
        $this->statsAPI = new StatsAPI($this);

        $this->getScheduler()->scheduleRepeatingTask(new BanTask($this), 20 * 60);
        $this->getScheduler()->scheduleRepeatingTask(new MuteTask($this), 20 * 60);

        $this->getServer()->getPluginManager()->registerEvents(new ChatEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new JoinEvent($this), $this);

        $this->getServer()->getCommandMap()->register("coins", new CoinsCommand($this));
        $this->getServer()->getCommandMap()->register("setrank", new SetRankCommand($this));
        $this->getServer()->getCommandMap()->register("tempban", new TempBanCommand($this));
        $this->getServer()->getCommandMap()->register("tempmute", new TempMuteCommand($this));
        $this->getServer()->getCommandMap()->register("topstats", new TopStatsCommand($this));
        $this->getServer()->getCommandMap()->register("unban", new UnBanCommand($this));
        $this->getServer()->getCommandMap()->register("unmute", new UnMuteCommand($this));

        $this->getLogger()->info("System wurde erfolgreich geladen.");
    }

    public function onDisable(): void {
    }

    /**
     * Gibt Zugriff auf die BanAPI
     */
    public function getBanAPI(): BanAPI {
        return $this->banAPI;
    }

    /**
     * Gibt Zugriff auf die CoinsAPI
     */
    public function getCoinsAPI(): CoinsAPI {
        return $this->coinsAPI;
    }

    /**
     * Gibt Zugriff auf die MuteAPI
     */
    public function getMuteAPI(): MuteAPI {
        return $this->muteAPI;
    }

    /**
     * Gibt Zugriff auf die RankAPI
     */
    public function getRankAPI(): RankAPI {
        return $this->rankAPI;
    }

    /**
     * Gibt Zugriff auf die StatsAPI
     */
    public function getStatsAPI(): StatsAPI {
        return $this->statsAPI;
    }

}