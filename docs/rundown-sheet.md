# Rundown sheet

## Preparing for a knockout

If the server is not running, start it (requires server access). If it has been running for a while, it might be an idea to restart it as it starts to bog down.

If someone is new to the event and wonder what the knockout is about, let them know about the `/info` command.

## Starting the knockout

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

Last but not least - remember to have fun!
