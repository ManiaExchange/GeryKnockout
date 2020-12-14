# Some notes about commands and callbacks

onStatusChange and respective callbacks are called in varying order:

- onStatusChange (Launching) -> onBeginRace
- onStatusChange (Synchronization) ->
- onBeginRound -> onStatusChange (Play)
- onEndRound ->
- onEndRace -> onStatusChange (Finish)

Some XML-RPC methods only work at specific points in a match. Calling them at an unsuitable time may yield no result or an error from the client. Some of these cases are listed below:

---

- NextChallenge
- RestartChallenge/ChallengeRestart

Results in error code -1000 (Change in progress), if proceeding to next round, in Finish and Synchronization. Otherwise, results in error code -1000 in Synchronization (but works in Finish).

---

- SendDisplayManialinkPage
- SendDisplayManialinkPageToLogin

May not have an effect with custom_ui when called in onBeginSynchronization (but could be due to Tm-Gery)

---

- SetRoundCustomPoints
- SetRoundPointsLimit

Has to be set before onStatusChange (Synchronization) in order to have effect on the upcoming round.
