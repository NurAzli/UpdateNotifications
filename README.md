# UpdateNotifications

**UpdateNotifications** is a virion for PocketMine-MP that checks for plugin updates available on Poggit and notifies admins.

## Features

- Checks for plugin updates from Poggit every hour.
- Sends notifications to admins with the `update.notify` permission.
- Logs notifications to the server console.

## Installation

1. **Download the virion:**
   You can download this virion from [Poggit](https://poggit.pmmp.io/).

2. **Add to your project:**
   Add this virion to your PocketMine-MP project. You can do this using Poggit-CI or manually by adding the virion files to your project.

## Usage

1. **Import the Virion:**
   Make sure to import the virion in your plugin:

   ```php
   use NurAzliYT\UpdateNotifications\UpdateNotifications;
   ```

2. **Initialize UpdateNotifications:**
   Initialize `UpdateNotifications` in your plugin's `onEnable` method:

   ```php
   public function onEnable(): void {
       $currentVersion = $this->getDescription()->getVersion();
       $pluginName = $this->getDescription()->getName();

       new UpdateNotifications($this, $currentVersion, $pluginName);
   }
   ```

## Example Code

### Your Plugin

```php
<?php

namespace YourPluginNamespace;

use pocketmine\plugin\PluginBase;
use NurAzliYT\UpdateNotifications\UpdateNotifications;

class YourPlugin extends PluginBase {

    public function onEnable(): void {
        $currentVersion = $this->getDescription()->getVersion();
        $pluginName = $this->getDescription()->getName();

        new UpdateNotifications($this, $currentVersion, $pluginName);
    }
}
```

### UpdateNotifications Virion

```php
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
        $response = file_get_contents($url);
        if ($response === false) {
            $this->plugin->getLogger()->warning("Failed to check for updates.");
            return;
        }

        $data = json_decode($response, true);
        if ($data === null || !isset($data[0]['version'])) {
            $this->plugin->getLogger()->warning("Failed to decode update data.");
            return;
        }

        $latestVersion = $data[0]['version'];
        if (version_compare($this->currentVersion, $latestVersion, '<')) {
            $this->notifyAdmins($latestVersion);
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
```

## Permissions

- `update.notify` - Permission to receive update notifications.

## Contributing

If you would like to contribute to this project, please create a pull request or open an issue on the [GitHub repository](https://github.com/NurAzli/UpdateNotifications).

## License

This project is licensed under the [MIT License](LICENSE).
