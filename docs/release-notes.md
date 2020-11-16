# Release notes

## New features

### Allow knocked out players to drive in warmups
What you all have been waiting for. Anyone can come and play the warmups during a knockout. If you are knocked out and prefer to spec, you can go to spectator mode during a warmup and remain in spec for the rest of the knockout.

### Automatic forceplay when someone who should be forced in the knockout joins the server
Players who are eligible to be forced back into the knockout, will be. If you happen to disconnect, you'll be able to rejoin until the next live round has ended. This means you can still rejoin after the warmup, but then with a late start.

During a tiebreaker, players who are shelved may also leave the server and rejoin after the tiebreaker.

### Look Mom, no password!
The new plugin removes the need for setting a password on the server. When joining the server, you'll be forced in if eligible and forced to spec otherwise. If you join as a spectator during a knockout, you'll stay as a spectator. That is, until you leave spec mode during a podium or warmup phase.

### Fair for everyone
Thanks to a new algorithm by Solux, the number of KOs per round will be more forgiving for the less skilled players. If we're more than 21 players, the knockout will start off with 1 KO per round, then progressively increase the number of KOs per round before gradually going back to 1 KO for the final rounds.

### Automatic skip if track author is in the knockout
When proceeding to the next track, and with 7 or less players in the knockout (by default), the script will skip the next track if the author is still in.

### Used the wrong key to skip the intro?
If someone retires before the race starts, and false start detection is on, the round will be restarted.

### Introducing tiebreakers
Whenever two or more players tie and only some of them are subject to getting knocked out, the script will restart the current track with the tied players only, and other players who are still in are temporarily put to spec. Once the tiebreaker ends, the knockout resumes as normal on the next track. Thanks to Realspace for bringing this idea up!

### New GUI
Things can get a bit hairy when everyone suddenly starts playing in the warmups. Now, a status bar is shown at the top part of the screen showing knockout information and your player status. This can be disabled by clicking the Tm-Gery button on the top left - in that case, the knockout information will be shown in chat instead. The update also introduces a scoreboard that will show who's getting knocked out and who's dangerously close.

### Support for multiple rounds per track
KOs will be performed for each round with a custom points partition.

### Smashing 8 year old bugs
- Fixed bug where the script will KO everyone if it didn't execute until next track.
- Fixed bug where you could avoid getting KO by going in spec between tracks.
- Fixed bug where players could rejoin after being forced to spec.
- Fixed bug where a player that has been forced in during warmup is not knocked out if last.
- Fixed bug where forcing a player in as the third player will end the knockout afterwards.
- Patched vulnerability where the script will KO everyone if `/restart` or `/gonext` is used. There will be no KO's as long as no one has finished yet.
- Fixed bug where the winner of a previous knockout would be crowned as the winner when stopping a knockout manually.

## For knockout admins

### Changes to commands
- Commands now use the same format as in the MX Knockout, which means that they follow the pattern `/kostart` -> `/ko start`
- `/ko start` will not skip to next track by default. To immediately skip to the next track, use `/ko start now`.
- Commands `/ko start now`, `/ko restart` and `/ko skip` now work when issued during a synchronization phase; these will take effect after a small delay (when the round starts).
- Public info messages are shown in white ($fff), non-public info messages are in grey ($aaa) and error messages are in red ($f00).
- Updated admin commands:
    - `/kostart` -> `/ko start [now]`
    - `/kostop` -> `/ko stop`
    - `/konextmap` -> `/ko skip [warmup]`
    - `/korestartmap` -> `/ko restart [warmup]`
    - `/koadd <login>`, `/koaddall` -> `/ko add (<login> | *)`
    - `/koremove <login>`, `/koremoveall` -> `/ko remove (<login> | *)`
    - `/komulti <per_x_players>`, `/komulti ko <kos>` -> `/ko multi (constant <kos> | extra <per_x_players> | dynamic <total_rounds> | none)`
- New admin commands:
    - `/ko spec (<login> | *)`
    - `/ko lives (<login> | *) [[+ | -]<lives>]` (default: 1)
    - `/ko rounds <rounds>` (default: 1)
    - `/ko openwarmup (on | off)` (default: on)
    - `/ko falsestart <max tries>` (default 2, 0 to disable)
    - `/ko tiebreaker (on | off)` (default: on)
    - `/ko authorskip <for top x players>` (default: 7, 0 to disable)
    - `/ko settings`
    - `/ko status`
    - `/ko help`
- New public commands:
    - `/info`
    - `/opt in`
    - `/opt out`
- Removed command `/kogetpass`
- Removed command `/kosetpass <password>`
- Removed command `/koJOIN <password>`

Because this update has significant changes to internal structure, `/ForcePlay [<login>]` and `/ForcePlayAll` does not put players into the knockout any longer. Use `/ko add <login>` and `/ko add *` instead. You may use `/ForceSpec [<login>]`, but `/ko remove <login>` is recommended as it has immediate effect.

### What happens when I start the knockout now?
A more robust system makes the script now more versatile, meaning that you can do stuff that would otherwise break the script before:

- `/ko start`
    - If warmup, starts the knockout after the warmup
    - If not warmup, schedules the knockout to start on the next round (or next track if it's the last round)
    - If podium, starts the knockout on the next track
- `/ko start now`
    - Skips the current track and starts the knockout on the next track
- `/ko start` -> `/ko skip`
    - Skips the current track and starts the knockout on the next track
- `/ko start` -> `/ko skip warmup`
    - Skips the current warmup and starts the knockout
- `/ko start` -> `/ko restart warmup`
    - Restarts the current track with a warmup and starts the knockout
- `/ko start` -> `/ko restart`
    - Restarts the current track without warmup (or ends the warmup if there is one) and starts the knockout

### Can I use some of the old commands?
`/restart`, `/gonext` and `/end` can be used interchangeably with `/ko restart`, `/ko skip` and `/ko skip warmup` respectively, as long as no one has finished yet. Though, the new commands offer new functionality such as restarting the current track with a warmup (using `/ko restart warmup`), protection against unwanted KOs if someone have finished, and proper state management (status is reflected in the top bar).

### States of the knockout
The new GUI may display the following states throughout a knockout:

| Knockout status | Description |
| :-- | :-- |
| `Warmup` | Warmup phase. |
| `Skipping warmup` | Warmup phase is being skipped by using `/ko skip warmup`. No KOs are performed. |
| `Running` | Knockout is live. Displays "Round x". |
| `Restarting round` | Round is being restarted by using `/ko restart [warmup]`. No KOs are performed. |
| `Skipping track` | Track is being skipped by using `/ko skip`. No KOs are performed. |
| `Tiebreaker` | Fight for survival among tied players. Non-participating players are put to player status `Shelved`. |

| Player status | Description |
| :-- | :-- |
| `Playing` | Participating in the knockout. |
| `Knocked out` | Out of the knockout, but can play during warmumps. |
| `Spectating` | Out of the knockout, and won't be playing during warmups. |
| `Shelved` | Temporarily put aside for a tiebreaker. Once the tiebreaker is over, shelved players will be playing again. |
| `Opting out` | In the process of opting out of the knockout, but can still rejoin using `/opt in`. |

Note: this list is not exhaustive - there are some additional states that are not displayed in the GUI.

### Known issues

- There may be extra waiting time before each round. This may be due to the fact that players are forced in to play warmups, but someone may be "stuck" in an intro. Use `/ko spec <login>` to force them to always spec; hopefully this will help.
