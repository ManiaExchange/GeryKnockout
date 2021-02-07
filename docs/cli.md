# CLI reference

Commands are case insensitive, logins are case sensitive. Settings carry over across knockouts; default values are applied after a server and/or controller restart.

## Admin commands

All admin commands for this plugin are available to players defined in both `$admin` and `$admin2` arrays in `includes\tm_gery_config.php`.

### /ko start [now]
Starts the knockout. If "now" is given, the current track will be skipped; otherwise the knockout will be scheduled to start on the next round (or after the warmup, if there currently is one). All players will be forced to play; inactive players need to opt out using `/opt out` or be forced to spec.

### /ko stop
Stops the knockout with immediate effect. Knocked out players are put to play; spectating players remain in spec but can choose to leave spectator mode.

### /ko skip [warmup]
Skips the current track. If "warmup" is given, only the warmup is skipped. During a tiebreaker, will exit the tiebreaker and resume the knockout.

### /ko restart [warmup]
Restarts the current track, or the current round if in Rounds. If "warmup" is given, the track is restarted with a warmup.

Note: `/ko restart warmup` will exit a tiebreaker.

### /ko add (*login* | \*)
Adds a player with login `login` to the knockout. If the wildcard `*` is used, then everyone on the server is added to the knockout. The number of lives will be equal to the value set with `/ko lives`.

Examples:

- `/ko add eyebo` - adds player with login `eyebo` to the knockout
- `/ko add *` - adds everyone on the server to the knockout

Note: if a player is reinstated and can't set a time/score on the current round, the player will lose a life.

Note: adding players during a tiebreaker will make them able to play once the tiebreaker has ended. The tiebreaker will continue as normal, with the losing players losing a life.

### /ko remove (*login* | \*)
Removes a player with login `login` from the knockout, regardless of how many lives they have. If the wildcard `*` is used, then everyone that are currently playing are removed from the knockout.

If applied during a round, the player(s) will instantly be put in spec and their runs will not count (regardless whether they finished or not). Keep in mind that it won't be a free round; KOs are still performed as usual but without the removed player.

Using this command with the dynamic KO multiplier (`/ko multi dynamic <total_rounds>`) may alter the number of KOs to be performed.

Examples:

- `/ko remove eyebo` - puts player with login `eyebo` out of the knockout
- `/ko remove *` - puts everyone out of the knockout

Note: `/ko remove *` will exit a tiebreaker.

### /ko spec (*login* | \*)
Same as `/ko remove` but instead puts the player(s) into spectator status. Use if a knocked out player is afk and becomes a cause of synchronization delays.

### /ko lives (*login* | \*) [[+ | -]*lives*]
Displays or adjusts the number of lives to use for the knockout. When adjusting, the number of lives may be relative (by using a plus or minus sign in front of the number of lives) or absolute. The change may be applied to a single player (using their login) or to everyone who is currently playing (using the wildcard `*`). If the number of lives is not supplied, the current number of lives for the given player(s) are displayed in chat.

This command can be used during a knockout; players will get the same number of lives or get knocked out depending on what the new number is set to.

Examples:

1. `/ko lives eyebo` - displays the number of lives for player with login `eyebo`
2. `/ko lives eyebo 2` - sets the number of lives to 2 for player with login `eyebo`
3. `/ko lives eyebo +1` - adds a life to player with login `eyebo`
4. `/ko lives eyebo -1` - removes a life from player with login `eyebo`
5. `/ko lives *` - displays the number of lives for players currently participating in the knockout
6. `/ko lives * 2` - sets the number of lives to 2 for players currently participating in the knockout
7. `/ko lives * +1` - adds a life to all players
8. `/ko lives * -1` - removes a life from all players

Examples 6, 7 and 8 will also adjust the value used for subsequent calls to `/ko add`.

Note: Players who are knocked out are not reinstated when using `/ko lives *`; the only way to do so is to specify their login.

Note: When reducing the number of lives relatively (using `/ko lives * -<lives>`), players may be knocked out.

Note: `/ko lives * <lives>` will restore lost lives; everyone will have the same amount of lives regardless of how many they lost so far. To avoid this, use `/ko lives * +<lives>` and `/ko lives * -<lives>`.

Default: 1

### /ko multi (constant *kos* | extra *per_x_players* | dynamic *total_rounds* | none)
Sets the KO multiplier mode:
- Constant: `x` KOs per round.
- Extra: +1 KO for every `x`'th player, such that if `x` = 10 then there will be 1 KO for 2-10 players, 2 KOs for 11-20, 3 KOs for 21-30, and so on.
- Dynamic: Aims for a total amount of `x` rounds. Starts off with 1 KO, progressively increases the KO count towards the middle and goes gradually back down to 1 KO for the final rounds.
- None: 1 KO per round.

Note: adjusting the multiplier is not possible during a tiebreaker.

Default: None

### /ko behaviour (playwarmup | forcespec | kick)
Determines what happens when a player gets knocked out:
- Playwarmup: Knocked out players stay on the server and may play during warmups if `/ko openwarmup` is enabled.
- Forcespec: Knocked out players are forced to spec and won't play during warmups, even if `/ko openwarmup` is enabled.
- Kick: Players are kicked from the server when they get knocked out. Applies until top 5.

Default: on

### /ko openwarmup (on | off)
Enables or disables open warmup which lets knocked out players play during warmup.

Default: on

### /ko falsestart *max_tries*
Sets the limit for how many times the round will be restarted if someone retires before the countdown (known TMF bug). Set to 0 to disable.

Default: 2

### /ko tiebreaker (on | off)
Enables or disables tiebreakers, a custom mode which takes effect when multiple players tie and at not all of them would be knocked out. Once invoked, all other players are shelved (put to spec momentarily) and the track is restarted with only the tied players racing. If set to off, ties are broken by when they were submitted; times which are set earlier are preferred.

Default: on

### /ko authorskip *for_top_x_players*
Automatically skips a track when its author is present, once a given player count has been reached. Set to 0 to disable.

Default: 7

### /ko settings
Displays knockout settings such as multiplier, lives, open warmup, etc in the chat. Also shown when starting a knockout.

### /ko status
Shows knockout mode, knockout status, player list and scores in a dialog window. For debugging purposes.

### /ko help
Shows the list of commands.

## Public commands

### /opt in
Rejoins the knockout after having opted out. Works only if you're still eligible to join (the live round has not started yet).

### /opt out
Puts yourself out of an upcoming or ongoing knockout. If applied during a round, your run will count as a DNF (regardless whether you finished or not).
