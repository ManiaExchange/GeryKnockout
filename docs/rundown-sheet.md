# Rundown sheet

## Preparing for a knockout

If the server is not running, start it (requires server access). If it has been running for a while, it might be an idea to restart it as it starts to bog down.

- Start the server: `D:\TrackManiaServer\MkWeekly\TMX_MK.bat`
- Start the controller: `D:\TrackManiaServer\MkWeekly\GeryController\tm_gery_start.bat`

If you plan to use KO20 or a recently updated track group, follow these steps (requires access to drive and spreadsheet, ask me (Voyager006) if you need access)

- Download the tracks from [Google Drive](https://drive.google.com/drive/folders/1ReDe0nKBuUGTe8tdBSLRjK-_fb35vELN?usp=sharing)
- Copy and overwrite the tracks in `D:\TrackManiaServer\MkWeekly\GameData\Tracks\Campaigns\<group>`
- Visit the [TMX Knockout spreadsheet](https://docs.google.com/spreadsheets/d/1XC4tCTGvjnDtV83mjEyHImTbgeBZdW_LCILlL-ljZF8/edit) and generate matchsettings through `Scripts -> Configure matchsettings`
- Copy the matchsettings to `D:\TrackManiaServer\MkWeekly\GameData\Tracks\MatchSettings`

If you didn't restart the server, make sure the gen-mix matchsettings are loaded. If not, use `/LoadMatchSettings gen-mix.txt`. There is also a gen-mix for stunts that can be used if KO9 is going to be played: `/LoadMatchSettings gen-mix-stunts.txt`.

If someone is new to the event and wonder what the knockout is about, let them know about the `/info` command.

## Starting the knockout

When it's time to start the knockout, make sure the desired matchsettings are loaded. Use `/ListMatchSettings` to view the applicable matchsettings and `/LoadMatchSettings <file>` to load them (e.g. `/LoadMatchSettings ko1-rd.txt`).

To schedule the knockout to start, use `/ko start`. The knockout will start on the next round (or after the warmup, if there is one). If there are more than 20 players, use `/ko multi dynamic 20` to enforce 20 rounds. A Rounds knockout may be shortened by driving multiple rounds per track - for a TTC-style knockout, issue the command `/ko round 5`.

If the track list is not randomized, use `/shuffle` after the first track has loaded. Note: Loading a matchsetting and then doing `/shuffle` before the first track can make the server keep the previous settings.

If the matchsettings are not correct, use some of these commands to fix this:

- `/warmup [<rounds>/<minutes>]` - sets the number of warmups to use in Rounds/Laps, or the duration of the warmup in Time Attack/Stunts
- `/round [<points_limit>]` - sets Rounds mode. `points_limit` will be adjusted by the plugin after being set
- `/ta [<minutes>]` - sets TA mode (`<minutes>` can be a floating point number)
- `/stunt [<minutes>]` - sets Stunts mode (`<minutes>` can be a floating point number)
- `/laps [<laps>]` - sets Laps mode
- `/chattime <seconds>` - sets the podium time (+ 5 seconds roughly)
- `/timeout [<seconds>]` - sets the finish timeout (time to finish after first player finishes) in Rounds and Laps. 25 seconds is what we usually work with
- `/respawn [off]` - enables or disables respawns (for hardcore knockouts)

If someone doesn't want to play, inform them about `/opt out` (and `/opt in`).

## During the knockout

If you need to skip a track, use `/ko skip`.

If someone misbehaves, moderation steps include:

- Muting them (`/Ignore <login>`, `/UnIgnore <login>`)
- Kicking them from the server (`/kick <login>`)
- And, if necessary, banning/blacklisting them (`/ban <login>`/`/blacklist <login>`).

Note: banning/blacklisting is only temporary unless the server config file is modified manually. If you don't have access, ask the other knockout admins.

Other useful commands:

- `/ko restart [warmup]` - restarts the round/warmup
- `/ko skip [warmup]` - skips the track/warmup
- `/ko add <login>` - adds a single player to the knockout
- `/ko add *` - adds all players to the knockout
- `/ko remove <login>` - removes a single player from the knockout
- `/ko remove *` - removes all players from the knockout
- `/cancel` - cancels a vote
- `/end` - to force the end of a round if someone trolls around and everyone is waiting for them

## Stopping the knockout

If you plan on having another knockout on the same track group:

- On the final track, prepare for the 5 minute break with `/ta 5` and `/warmup 0` (or `/stunt 5` if you play Stunts)
- During the 5 minute break, do `/round 1` and `/warmup 1` if you play Rounds (or `/stunt 2.5` if you play Stunts)

Otherwise:

- On the final track, load the lobby tracks using `/LoadMatchSettings gen-mix.txt`

Last but not least - remember to have fun!

## Reporting an issue

If you encounter a bug or a plugin crash, you can report them to me (Voyager006). The following information would be helpful:

- What happened prior to the bug/crash
- A snapshot using `/ko status` (if the plugin didn't crash)
- The log in the console window (if you have access)

If the issue persists, there's always the option to revert back to the old plugin (requires server access). In `GeryController\plugins.txt`, replace `plugin.knockout2.php` with `plugin.knockout.php` and restart the controller (can be done by using the command `/die` in the chat). The old plugin follows the previous pattern with commands such as `/kostart`, `/korestartmap` and `/konextmap`.

## TL;DR

- Load the matchsettings you want to play, e.g. `/LoadMatchSettings ko1-rd.txt` (use `/ListMatchSettings` to see what you can choose)
- Start the knockout with `/ko start`
- If there are more than 20 players, use `/ko multi dynamic 20`
- If the track list is not randomized, use `/shuffle` after the first track has loaded
- If you need to skip a track during the knockout, use `/ko skip`
- When there are no more knockouts, load the lobby tracks using `/LoadMatchSettings gen-mix.txt`
