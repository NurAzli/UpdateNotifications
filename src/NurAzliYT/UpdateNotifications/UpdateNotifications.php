<?php

namespace NurAzliYT\UpdateNotifications;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\Server;

class UpdateNotifications {

    private $plugin;
    private $currentVersion;
    private $pluginName;

    public function __construct(PluginBase $plugin, string $currentVersion, string $pluginName) {
        $this->plugin = $plugin;
        $this->currentVersion = $currentVersion;
        $this->pluginName = $pluginName;
        
        $this->plugin->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private $notification;

            public function __construct(UpdateNotifications $notification) {
                $this->notification = $notification;
            }

            public function onRun(): void {
                $this->notification->checkForUpdates();
            }
        }, 20 * 60 * 60); // Check every hour
    }

    public function checkForUpdates(): void {
        $url = 'https://poggit.pmmp.io/releases.min.json?name=' . urlencode($this->pluginName);
        
        try {
            $response = file_get_contents($url);
            if ($response === false) {
                throw new \Exception("Failed to fetch update data.");
            }

            $data = json_decode($response, true);
            if ($data === null || !isset($data[0]['version'])) {
                throw new \Exception("Failed to decode update data.");
            }

            $latestVersion = $data[0]['version'];
            if (version_compare($this->currentVersion, $latestVersion, '<')) {
                $this->notifyAdmins($latestVersion);
            }
        } catch (\Exception $e) {
            $this->plugin->getLogger()->warning("Update check failed: " . $e->getMessage());
        }
    }

    private function notifyAdmins(string $newVersion): void {
        $message = TextFormat::YELLOW . "A new version of the plugin is available: " . $newVersion . ". Please update to the latest version.";
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($player->hasPermission("update.notify")) {
                $player->sendMessage($message);
            }
        }
        $this->plugin->getLogger()->info($message);
    }
}
