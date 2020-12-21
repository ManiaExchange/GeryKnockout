# Contribution guidelines

## Resources
You may find some of these resources useful when working on this project:

- XML-RPC methods: https://methods.xaseco.org/methodstmf.php
- Callback methods and structs: https://server.xaseco.org/callbacks.php
- Manialinks, icons, backgrounds and formatting tags: the [Trackmania ManiaLink styles](tmtp:///:example) manialink

## Some notes about commands and callbacks
onStatusChange and respective callbacks are called in varying order:

- onStatusChange (Launching) -> onBeginRace
- onStatusChange (Synchronization) ->
- onBeginRound -> onStatusChange (Play)
- onEndRound ->
- onEndRace -> onStatusChange (Finish)

Some XML-RPC methods only work at specific points in a match. Calling them at an unsuitable time may yield no result or an error from the client. Some of these cases are listed below:

---

- RestartChallenge/ChallengeRestart

Results in error code -1000 (Change in progress) in Synchronization. Results in error code -1000 (Change in progress) in Finish but only if proceeding to next round on the same map; it works in the podium sequence.

---

- NextChallenge

Results in error code -1000 (Change in progress) during Synchronization and Finish.

---

- SendDisplayManialinkPage
- SendDisplayManialinkPageToLogin

May not have an effect with custom_ui when called in onBeginSynchronization (but could be due to TMGery). Results in error code -1000 if you try to force a spectator target but the player is not in spec. Results in error code -1000 if the target player can not be found.

---

- SetRoundCustomPoints
- SetRoundPointsLimit

Has to be set before onStatusChange (Synchronization) in order to have effect on the upcoming round.

## Debugging
The following places are useful to look at for information regarding a bug or crash:

- Console output
- `/ko status`, if TMGery didn't crash
- Video recordings

The next step, if the source of the bug is still unknown, is to set `MinimumLogLevel` to `Log::Debug` in `plugin.knockout.php` in order to get more information in the console window. Note that this may have a negative impact on the server performance, so only do this temporarily.
