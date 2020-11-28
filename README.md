# GeryKnockout
Knockout plugin for the TMGery server controller.

[CLI reference](https://github.com/ManiaExchange/GeryKnockout/blob/main/docs/cli.md) | [User guide](https://github.com/ManiaExchange/GeryKnockout/blob/main/docs/user-guide.md) | [Report a bug](https://github.com/ManiaExchange/GeryKnockout/issues/new/choose) | [Suggest a feature](https://github.com/ManiaExchange/GeryKnockout/issues/new/choose)

## About
This repository contains a plugin for TMGery that enables knockout competitions to be held. Each round, the last players are knocked out until one player remains. This plugin is used in the TMX Knockout United.

## Prerequisites
- Trackmania dedicated server
- PHP 5.3 or later
- TMGery v34 or later
- DedDerek's plugin manager 0.23 or later

## Installation
Copy the contents of `plugins` to the plugins folder of your TMGery installation, then append the following to `plugins.txt`:

```
plugins\plugin.knockout.php
```

Most of the CLI requires admin access. To specify who are admins on the server, add their logins to the `$admin` and `$admin2` arrays in `includes\tm_gery_config.php`.

Then, restart the controller (using `/die` in-game or rebooting the script) to apply the changes.

## Development
Clone this repository to a folder of your choice. Using Visual Studio Code, [PHP Intelephense](https://marketplace.visualstudio.com/items?itemName=bmewburn.vscode-intelephense-client) and [EditorConfig for VS Code
](https://marketplace.visualstudio.com/items?itemName=EditorConfig.EditorConfig) is recommended. Using this setup, copy `tm_gery.php`, `includes\plugin_manager.php` and `includes\GbxRemote.php` from your TMGery installation to a new top level folder `dependencies`. Files in this folder are ignored by `.gitignore`, so you should see them grey out.

## Contribution
You may contribute to this project by [reporting bugs](https://github.com/ManiaExchange/GeryKnockout/issues/new/choose), [suggesting new features](https://github.com/ManiaExchange/GeryKnockout/issues/new/choose) or creating pull requests that addresses particular issues. Please consult [CONTRIBUTING.md](https://github.com/ManiaExchange/GeryKnockout/blob/main/CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](https://github.com/ManiaExchange/GeryKnockout/blob/main/CODE_OF_CONDUCT.md) if you plan to contribute on the coding part.

## Screenshots
![In-game screenshot showing the status bar](docs/img/screenshot-status-bar-324p.png)

![In-game screenshot showing the scoreboard](docs/img/screenshot-scoreboard-324p.png)

## The team
- [@Voyager006](https://github.com/Voyager006) - main plugin work, documentation
- [@stefan-baumann](https://github.com/stefan-baumann) - dynamic KO multiplier algorithm, graphs

## Thanks to...
- MrA for suggesting a progressive KO multiplier
- Dennis for suggesting the syntax for the `/ko lives` command
- Realspace for suggesting the tiebreaker mode
- CavalierDeVache for the original plugin
- Mikey for the original concept (Madhouse Knockout)
- All the hosters of the TMX Knockout for keeping it running throughout the years
- And everyone who joined the TMX Knockout server to help testing out the plugin!
