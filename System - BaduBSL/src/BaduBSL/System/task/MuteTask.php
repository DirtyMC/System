<?php

namespace BaduBSL\System\task;

use BaduBSL\System\System;
use pocketmine\scheduler\Task;

class MuteTask extends Task
{
    private System $plugin;

    public function __construct(System $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun(): void
    {
        // Bereinige abgelaufene Mutes
        $this->plugin->getMuteAPI()->cleanExpiredMutes();
    }

}