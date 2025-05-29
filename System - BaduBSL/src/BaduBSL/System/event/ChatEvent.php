<?php

declare(strict_types=1);

namespace BaduBSL\System\event;

use BaduBSL\System\System;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\chat\LegacyRawChatFormatter;

class ChatEvent implements Listener
{
    private $plugin;

    public function __construct(System $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $message = $event->getMessage();

        // Prüfe ob gemuted
        if ($this->plugin->getMuteAPI()->isMuted($player->getName())) {
            $reason = $this->plugin->getMuteAPI()->getMuteReason($player->getName());
            $player->sendMessage("§cDu bist gemuted. Grund: §f$reason");
            $event->cancel();
            return;
        }

        // Formatiere Nachricht nach Rangschema
        $format = $this->plugin->getRankAPI()->getChatFormat($name);
        $formattedMessage = str_replace(["{name}", "{message}"], [$name, $message], $format);

        // Setze neuen Formatter
        $event->setFormatter(new LegacyRawChatFormatter($formattedMessage));
    }
}