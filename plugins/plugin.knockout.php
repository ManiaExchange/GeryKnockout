<?php
/*
 * Knockout plugin for Tm-Gery by Voyager006.
 * Dynamic KO multiplier algorithm by Solux.
 * Based on original plugin by CavalierDeVache. Idea by Mikey.
 */

const Version = '2.0.0 (beta)';
const MinimumLogLevel = Log::Debug;


/*
 * Some notes about commands and callbacks:
 *
 * onStatusChange and respective callbacks are called in varying order:
 *
 * - onStatusChange (Launching) -> onBeginRace
 * - onStatusChange (Synchronization) ->
 * - onBeginRound -> onStatusChange (Play)
 * - onEndRound ->
 * - onEndRace -> onStatusChange (Finish)
 *
 * Some XML-RPC methods only work at specific points in a match. Calling them at an unsuitable time
 * may yield no result or an error from the client. Some of these cases are listed below:
 *
 * - NextChallenge
 * - RestartChallenge/ChallengeRestart
 *
 * Results in error code -1000 (Change in progress), if proceeding to next round, in Finish and
 * Synchronization. Otherwise, results in error code -1000 in Synchronization (but works in Finish).
 *
 * - SendDisplayManialinkPage
 * - SendDisplayManialinkPageToLogin
 *
 * May not have an effect with custom_ui when called in onBeginSynchronization (but could be due to
 * Tm-Gery)
 *
 * - SetRoundCustomPoints
 * - SetRoundPointsLimit
 *
 * Has to be set before onStatusChange (Synchronization) in order to have effect on the upcoming
 * round.
 */


/**
 * Obtains the names and values of constants through the Reflection API.
 *
 * @param string $className The name of the class to obtain constants from.
 *
 * @return array An array with the names of the constants as keys and their respective values as
 * values.
 */
function getConstants($className)
{
    $fooClass = new ReflectionClass($className);
    return $fooClass->getConstants();
}


/**
 * Returns the string representation of the given constant.
 *
 * @param mixed $value The constant value.
 * @param string $className The name of the class to retrieve constants from.
 *
 * @return string|bool The corresponding name of the constant, or false if the value does not
 * correspond to a constant.
 */
function getNameOfConstant($value, $className)
{
    return array_search($value, getConstants($className), true);
}


/**
 * Returns the sign of the given number.
 *
 * @param int|float $number The number to evaluate.
 *
 * @return int -1 if the number is negative, 1 if it is positive, 0 otherwise.
 */
function sign($number)
{
    if ($number < 0) return -1;
    elseif ($number > 0) return 1;
    else return 0;
}


if (!function_exists('str_contains')) {
    /**
     * Checks if a string is contained in another string.
     *
     * @param string $haystack The string to search in.
     * @param string $needle The string to search for.
     *
     * @return bool True if the string was found, false otherwise.
     */
    function str_contains($haystack, $needle)
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}


// For XML-RPC methods

class GameMode
{
    const Rounds = 0;
    const TimeAttack = 1;
    const Team = 2;
    const Laps = 3;
    const Stunts = 4;
    const Cup = 5;
}


class ServerStatus
{
    const Waiting = 1;
    const Launching = 2;
    const Synchronization = 3;
    const Play = 4;
    const Finish = 5;
}


class SpectatorMode
{
    const UserSelectable = 0;
    const Spectator = 1;
    const Player = 2;
}


// For the Knockout

class KnockoutMode
{
    const Normal = 11;
    // const Countdown = 12; // Beat the slowest surviving player's time
    // const Endurance = 13; // Time from leader to KO gets shorter for each checkpoint
    // const Softcore = 14; // TA; skips track once x amount of players finish or time runs out
    // const AdvanceCup = 15; // Each round, the fastest players advance to the next map
    // const Combine = 16; // Total time across several rounds
}


class KnockoutStatus
{
    const Idle = 21;
    const Starting = 22;
    const StartingNow = 23;
    const Warmup = 24;
    const Running = 25;
    const RestartingRound = 26;
    const RestartingTrack = 27;
    const SkippingWarmup = 28;
    const SkippingTrack = 29;
    const Tiebreaker = 30;
    const Stopping = 31;

    /**
     * Returns true if the knockout has started.
     *
     * @param KnockoutStatus $status The current knockout status.
     *
     * @return bool True if the knockout has started, false otherwise.
     */
    public static function isInProgress($status)
    {
        return $status !== self::Idle
            && $status !== self::Starting
            && $status !== self::StartingNow;
    }
}


class PlayerStatus
{
    const Playing = 41;
    const PlayingAndDisconnected = 42;  // When someone is disconnected but still eligible to rejoin and play
    const Shelved = 43;                 // Set aside for the moment (e.g. when not part of tiebreaker)
    const ShelvedAndDisconnected = 44;
    const KnockedOut = 45;              // Knocked out but plays during warmup
    const KnockedOutAndSpectating = 46; // Knocked out and always spectating
    const OptingOut = 47;               // About to opt out but still eligible to rejoin via /opt in

    /**
     * Tests whether a player is currently in the knockout.
     *
     * @param PlayerStatus $status The status to test.
     *
     * @return bool True if the player is playing, false otherwise.
     */
    public static function isIn($status)
    {
        return $status === self::Playing
            || $status === self::PlayingAndDisconnected;
    }

    /**
     * Tests whether a player is currently out of the knockout.
     *
     * @param PlayerStatus $status The status to test.
     *
     * @return bool True if the player is knocked out, false otherwise.
     */
    public static function isOut($status)
    {
        return $status === self::KnockedOut
            || $status === self::KnockedOutAndSpectating;
    }

    /**
     * Tests whether a player is currently shelved.
     *
     * @param PlayerStatus $status The status to test.
     *
     * @return bool True if the player is shelved, false otherwise.
     */
    public static function isShelved($status)
    {
        return $status === self::Shelved
            || $status === self::ShelvedAndDisconnected;
    }

    /**
     * Tests whether a player is disconnected (but still eligible to join).
     *
     * @param PlayerStatus $status The status to test.
     *
     * @return bool True if the player is disconnected (but still eligible to join), false
     * otherwise.
     */
    public static function isDisconnected($status)
    {
        return $status === self::PlayingAndDisconnected
            || $status === self::ShelvedAndDisconnected;
    }

    /**
     * Outputs a colored representation of a player status.
     *
     * @param PlayerStatus $status The status to output.
     *
     * @return string A string representation of the player status with a color code embedded.
     */
    public static function output($status)
    {
        switch ($status)
        {
            case self::Playing:
                return '$0f0Playing';
            case self::Shelved:
                return '$08fShelved';
            case self::KnockedOut:
                return '$f00Knocked out';
            case self::KnockedOutAndSpectating:
                return '$808Spectating';
            case self::OptingOut:
                return '$808Opting out';
            default:
                return '';
        }
    }
}


/**
 * Utility class for logging in the console window.
 */
class Log
{
    const Debug = 51;
    const Information = 52;
    const Warning = 53;
    const Error = 54;

    private static function write($level, $message)
    {
        printf("[%s %s] %s\n", date('H:i:s'), $level, $message);
    }

    /**
     * Logs a debug message in the terminal if MinimumLogLevel is Log::Debug or less.
     *
     * @param string $message The message to log.
     */
    public static function debug($message)
    {
        if (MinimumLogLevel <= self::Debug) self::write('DBG', $message);
    }

    /**
     * Logs an information message in the terminal if MinimumLogLevel is Log::Information or less.
     *
     * @param string $message The message to log.
     */
    public static function information($message)
    {
        if (MinimumLogLevel <= self::Information) self::write('INF', $message);
    }

    /**
     * Logs a warning message in the terminal if MinimumLogLevel is Log::Warning or less.
     *
     * @param string $message The message to log.
     */
    public static function warning($message)
    {
        if (MinimumLogLevel <= self::Warning) self::write('WRN', $message);
    }

    /**
     * Logs an error message in the terminal.
     *
     * @param string $message The message to log.
     */
    public static function error($message)
    {
        if (MinimumLogLevel <= self::Error) self::write('ERR', $message);
    }
}


/**
 * Utility class for client queries.
 *
 * Use this class as an instance for multicalling:
 *
 *     $queries = new QueryManager();
 *     $queries->add('ChatSendServerMessage', 'Hello world');
 *     $queries->add('ChatSendServerMessage', 'It's nice to be here');
 *     $queries->submit();
 *
 * or use its static methods for single queries:
 *
 *     QueryManager::query('ForceSpectator', 'voyager006', 0);
 *     $response = QueryManager::queryWithResponse('GetPlayerInfo', 'voyager006');
 */
class QueryManager
{
    private $multicall;

    /**
     * Instantiates a query manager.
     */
    public function __construct()
    {
        $this->multicall = array();
    }

    private static function handleError($methodName = null)
    {
        global $client;

        $method = $methodName === null ? '' : sprintf('%s ', $methodName);
        $msg = sprintf("Client query %sfailed with code %d: %s", $method, $client->getErrorCode(), $client->getErrorMessage());
        Log::error($msg);
        $client->resetError();
    }

    /**
     * Adds a client query to memory.
     *
     * @param string $methodName The method name of the query.
     * @param mixed $arg1 [Optional] The first argument for the specified query.
     * @param mixed $arg2 [Optional] The second argument for the specified query.
     * @param mixed $arg3 [Optional] The third argument for the specified query.
     * @param mixed $arg4 [Optional] The fourth argument for the specified query.
     */
    public function add($methodName, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        $args = array_filter(
            array($arg1, $arg2, $arg3, $arg4),
            function($arg) { return !is_null($arg); }
        );
        $this->multicall[] = array(
            'methodName' => $methodName,
            'params' => $args
        );
    }

    /**
     * Submits the in-memory queries to the client.
     *
     * @return bool True if the query was successfully sent, false if an error occurred or there are
     * no queries to send.
     */
    public function submit()
    {
        global $client;

        if (empty($this->multicall)) return false;

        $success = $client->query('system.multicall', $this->multicall);
        if (!$success) self::handleError();
        $this->multicall = array();
        return $success;
    }

    /**
     * Queries the client.
     *
     * @param string $methodName The method name of the query.
     * @param mixed $arg1 [Optional] The first argument for the specified query.
     * @param mixed $arg2 [Optional] The second argument for the specified query.
     * @param mixed $arg3 [Optional] The third argument for the specified query.
     * @param mixed $arg4 [Optional] The fourth argument for the specified query.
     *
     * @return bool True if the query was successfully handled, false if an error occurred.
     */
    public static function query($methodName, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        global $client;

        $success = false;
        if (is_null($arg1)) $success = $client->query($methodName);
        elseif (is_null($arg2)) $success = $client->query($methodName, $arg1);
        elseif (is_null($arg3)) $success = $client->query($methodName, $arg1, $arg2);
        elseif (is_null($arg4)) $success = $client->query($methodName, $arg1, $arg2, $arg3);
        else $success = $client->query($methodName, $arg1, $arg2, $arg3, $arg4);
        if (!$success) self::handleError($methodName);
        return $success;
    }

    /**
     * Queries the client and returns its response.
     *
     * @param string $methodName The method name of the query.
     * @param mixed $arg1 [Optional] The first argument for the specified query.
     * @param mixed $arg2 [Optional] The second argument for the specified query.
     * @param mixed $arg3 [Optional] The third argument for the specified query.
     * @param mixed $arg4 [Optional] The fourth argument for the specified query.
     *
     * @return mixed|bool The response from the client if the query was successfully handled, false
     * if an error occurred.
     */
    public static function queryWithResponse($methodName, $arg1 = null, $arg2 = null, $arg3 = null, $arg4 = null)
    {
        global $client;

        $success = false;
        if (is_null($arg1)) $success = $client->query($methodName);
        elseif (is_null($arg2)) $success = $client->query($methodName, $arg1);
        elseif (is_null($arg3)) $success = $client->query($methodName, $arg1, $arg2);
        elseif (is_null($arg4)) $success = $client->query($methodName, $arg1, $arg2, $arg3);
        else $success = $client->query($methodName, $arg1, $arg2, $arg3, $arg4);
        if (!$success)
        {
            self::handleError($methodName);
            return false;
        }
        else
        {
            return $client->getResponse();
        }
    }
}


/**
 * Utility class for in-game chat messaging.
 */
class Chat
{
    private static function sendMessage($color, $message, $logins = null)
    {
        $formatted = sprintf('$ff0>> %s%s', $color, str_replace('$g', $color, $message));
        if (is_null($logins))
        {
            QueryManager::query('ChatSendServerMessage', $formatted);
        }
        else
        {
            $commaSeparatedLogins = implode(',', $logins);
            QueryManager::query('ChatSendServerMessageToLogin', $formatted, $commaSeparatedLogins);
        }
    }

    /**
     * Sends a formatted announcement message to the chat.
     *
     * The message may contain in-game formatting itself. If the character sequence '$g' is used, it
     * will be replaced with the highlight color used by this function.
     *
     * @param string $message The message to be written.
     * @param array $logins [Optional] The logins of the players to send the message to. If null,
     * the message is sent to everyone.
     */
    public static function announce($message, $logins = null, $color = '$0f0')
    {
        self::sendMessage($color, $message, $logins);
    }

    /**
     * Sends a formatted error message to the chat.
     *
     * The message may contain in-game formatting itself. If the character sequence '$g' is used, it
     * will be replaced with the highlight color used by this function.
     *
     * @param string $message The message to be written.
     * @param array $logins [Optional] The logins of the players to send the message to. If null,
     * the message is sent to everyone.
     */
    public static function error($message, $logins = null)
    {
        self::sendMessage('$f00', $message, $logins);
    }

    /**
     * Sends a formatted information message to the chat.
     *
     * The message may contain in-game formatting itself. If the character sequence '$g' is used, it
     * will be replaced with the highlight color used by this function.
     *
     * @param string $message The message to be written.
     * @param array $logins [Optional] The logins of the players to send the message to. If null,
     * the message is sent to everyone.
     */
    public static function info($message, $logins = null)
    {
        self::sendMessage('$fff', $message, $logins);
    }

    /**
     * Sends a formatted information message with a darker tone to the chat.
     *
     * The message may contain in-game formatting itself. If the character sequence '$g' is used, it
     * will be replaced with the highlight color used by this function.
     *
     * @param string $message The message to be written.
     * @param array $logins [Optional] The logins of the players to send the message to. If null,
     * the message is sent to everyone.
     */
    public static function info2($message, $logins = null)
    {
        self::sendMessage('$aaa', $message, $logins);
    }
}


/**
 * Scores list for race times and stunt points with custom, determinant sorting.
 */
class Scores
{
    const HasNotFinishedYet = 0;
    const DidNotFinish = -1;

    private $scores;
    private $isAscending; // True for Stunts, false for TA and Rounds

    /**
     * Creates an empty scores instance.
     */
    public function __construct()
    {
        $this->scores = array();
        $this->isAscending = true;
        $this->setSortingOrder(true);
    }

    private function getComparator()
    {
        return $this->isAscending
            ? function($a, $b)
            {
                if ($b['Score'] <= 0) return -1;
                elseif ($a['Score'] <= 0) return 1;
                else return $a['Score'] < $b['Score'] ? -1 : 1;
            }
            : function($a, $b)
            {
                if ($b['Score'] <= 0) return -1;
                elseif ($a['Score'] <= 0) return 1;
                else return $a['Score'] > $b['Score'] ? -1 : 1;
            };
    }

    /**
     * Initializes the scores by setting the scores of the specified logins to 0. Usually done for
     * Time Attack and Stunts.
     *
     * @param array $logins An array of players, each with fields 'Login' and 'NickName'.
     */
    public function initialize($players)
    {
        $init = function($player)
        {
            array(
                'Login' => $player['Login'],
                'NickName' => $player['NickName'],
                'Score' => 0
            );
        };
        $this->scores = array_map($init, $players);
    }

    /**
     * Sorts an already sorted array using bubble sort for a single element inserted at $fromIndex.
     */
    private function sort($fromIndex)
    {
        for ($i = $fromIndex; $i > 0; $i--)
        {
            $current = $this->scores[$i];
            $next = $this->scores[$i - 1];
            $shouldMoveUp = false;
            if ($current['Score'] <= 0)
            {
                // Prioritize later DNFs over earlier DNFs
                $shouldMoveUp = $next['Score'] <= 0;
            }
            else
            {
                $isBetter = $this->isAscending ? ($current['Score'] < $next['Score']) : ($current['Score'] > $next['Score']);
                $shouldMoveUp = $next['Score'] <= 0 || $isBetter;
            }
            if ($shouldMoveUp)
            {
                $this->scores[$i - 1] = $current;
                $this->scores[$i] = $next;
            }
            else
            {
                return;
            }
        }
    }

    /**
     * Submits the score of the given player to the scoreboard.
     *
     * If the score is better than the previous one set by $login, the record will be updated. Worse
     * scores are ignored.
     *
     * @param string $login The login of the player.
     * @param string $nickName The nickname of the player.
     * @param int $score The player's score.
     */
    public function submitScore($login, $nickName, $score)
    {
        $logins = array_map(
            function($score) { return $score['Login']; },
            $this->scores
        );
        $index = array_search($login, $logins, true);
        if ($index === false)
        {
            $this->scores[] = array(
                'Login' => $login,
                'NickName' => $nickName,
                'Score' => $score
            );
            $this->sort(count($this->scores) - 1);
        }
        else
        {
            $previousScore = $this->scores[$index]['Score'];
            if ($previousScore <= 0)
            {
                $this->scores[$index] = array(
                    'Login' => $login,
                    'NickName' => $nickName,
                    'Score' => $score
                );
                $this->sort($index);
            }
            else
            {
                $isImprovement =
                    ($score > 0)
                    && ($this->isAscending ? ($score < $previousScore) : ($score > $previousScore));
                if ($isImprovement)
                {
                    $this->scores[$index] = array(
                        'Login' => $login,
                        'NickName' => $nickName,
                        'Score' => $score
                    );
                    $this->sort($index);
                }
            }
        }
    }

    /**
     * Gets the current best time (in milliseconds) or score of a given player.
     *
     * @param string $login The login of the player.
     *
     * @return int|bool The score if the player has set one, or false if no score has been set.
     */
    public function get($login)
    {
        $logins = array_map(
            function($player) { return $player['Login']; },
            $this->scores
        );
        $index = array_search($login, $logins, true);
        if ($index === false)
        {
            return false;
        }
        else
        {
            return $this->scores[$index]['Score'];
        }
    }

    /**
     * Gets an array of scores sorted according to the predefined mode.
     *
     * @return array An indexed array sorted by score, each element being an array with elements
     * 'Login' and 'Score'.
     */
    public function getSortedScores()
    {
        return $this->scores;
    }

    /**
     * Empties the scores.
     */
    public function reset()
    {
        $this->scores = array();
    }

    /**
     * Explicitly sets a score that might be lower than the current score. Note that this should
     * only be used for administrative operations as it is less performant than submitScore.
     *
     * @param string $login The login of the player.
     * @param string $nickName The nickname of the player.
     * @param int $score The time or score of the player.
     */
    public function set($login, $nickName, $score)
    {
        $logins = array_map(
            function($player) { return $player['Login']; },
            $this->scores
        );
        $index = array_search($login, $logins, true);
        if ($index === false)
        {
            $this->submitScore($login, $nickName, $score);
        }
        else
        {
            $this->scores[$index] = array(
                'Login' => $login,
                'NickName' => $nickName,
                'Score' => $score
            );
            uasort($this->scores, $this->getComparator());
        }
    }

    /**
     * Sets the sorting order used to sort the scores with.
     *
     * @param bool $isAscending Whether the scores should be sorted in ascending or descending
     * order.
     */
    public function setSortingOrder($isAscending)
    {
        $this->isAscending = $isAscending;
        uasort($this->scores, $this->getComparator());
    }
}


/**
 * Class for keeping track of players in a knockout.
 */
class PlayerList
{
    private $players;

    public function __construct()
    {
        $this->players = array();
    }

    /**
     * Adds a given player to this player list. If the login is already added, it will be replaced.
     *
     * @param string $login The login of the player.
     * @param string $nickName The formatted nickname of the player.
     * @param PlayerStatus $status [Optional] The status of the player. Default is
     * PlayerStatus::Playing.
     * @param int $lives [Optional] The number of lives the player should have. Default is 1.
     */
    public function add($login, $nickName, $status = PlayerStatus::Playing, $lives = 1)
    {
        $this->players[$login] = array(
            'Login' => $login,
            'NickName' => $nickName,
            'Status' => $status,
            'Lives' => $lives
        );
    }

    /**
     * Adds a list of players to this player list. If any login is already added, it will be
     * replaced.
     *
     * @param mixed[] $players An array of players to add, each element being an array with at least
     * the fields 'Login' and 'NickName'.
     * @param PlayerStatus $status [Optional] The status of the players. Default is
     * PlayerStatus::Playing.
     * @param int $lives [Optional] The number of lives each player should have. Default is 1.
     */
    public function addAll($players, $status = PlayerStatus::Playing, $lives = 1)
    {
        foreach ($players as $player)
        {
            $this->add($player['Login'], $player['NickName'], $status, $lives);
        }
    }

    /**
     * Gets a player object by their login.
     *
     * @param string $login The login of the player.
     * @return mixed[] The player object if found, null otherwise.
     */
    public function get($login)
    {
        if (isset($this->players[$login]))
        {
            return $this->players[$login];
        }
        else
        {
            Log::warning(sprintf('Player %s is not in the player list', $login));
            return null;
        }
    }

    /**
     * Tests whether a given player is in this player list.
     *
     * @param string $login The login of the player.
     *
     * @return bool True if the login exists in this player list, false otherwise.
     */
    public function exists($login)
    {
        return isset($this->players[$login]);
    }

    /**
     * Returns true if a player has the given status.
     *
     * @param string $login The login of the player.
     * @param PlayerStatus $status The status to search for.
     *
     * @return bool True if the player has the given status, false otherwise.
     */
    public function hasStatus($login, $status)
    {
        return isset($this->players[$login]) && $this->players[$login]['Status'] === $status;
    }

    /**
     * Gets all players in this list.
     *
     * @return mixed[] An array of all player objects.
     */
    public function getAll()
    {
        return $this->players;
    }

    /**
     * Filters this list by the given player status.
     *
     * @param PlayerStatus $playerStatus The player status to be met.
     * @return mixed[] An array of players with the specific player status.
     */
    public function filterByStatus($playerStatus)
    {
        $array = array();
        foreach ($this->players as $player)
        {
            if ($player['Status'] === $playerStatus)
            {
                $array[] = $player;
            }
        }
        return $array;
    }

    /**
     * Performs a callback function for each element in the player list.
     *
     * @param callback $mapping The callback function to perform on each player in the list.
     */
    public function map($mapping)
    {
        $this->players = array_map($mapping, $this->players);
    }

    /**
     * Removes a player from this player list. Note: this is not equivalent to performing a KO.
     *
     * @param string $login The login of the player.
     */
    public function remove($login)
    {
        if (isset($this->players[$login]))
        {
            unset($this->players[$login]);
        }
        else
        {
            Log::warning(sprintf('Player %s is not in the player list', $login));
        }
    }

    /**
     * Removes the players that are currently set.
     */
    public function reset()
    {
        $this->players = array();
    }

    /**
     * Updates the nickname of the given player.
     *
     * @param string $login The login of the player.
     * @param string $nickname The updated nickname.
     */
    public function setNickname($login, $nickname)
    {
        if (isset($this->players[$login]))
        {
            $this->players[$login]['NickName'] = $nickname;
        }
        else
        {
            Log::warning(sprintf('Player %s is not in the player list', $login));
        }
    }

    /**
     * Sets the number of lives for the given player.
     *
     * @param string $login The login of the player.
     * @param int $numberOfLives The desired number of lives.
     */
    public function setLives($login, $numberOfLives)
    {
        if (isset($this->players[$login]))
        {
            $this->players[$login]['Lives'] = $numberOfLives;
        }
        else
        {
            Log::warning(sprintf('Player %s is not in the player list', $login));
        }
    }

    /**
     * Sets the status of the specified player.
     *
     * @param string $login The login of the player.
     * @param PlayerStatus $playerStatus The new status of the player.
     */
    public function setStatus($login, $playerStatus)
    {
        if (isset($this->players[$login]))
        {
            $this->players[$login]['Status'] = $playerStatus;
        }
        else
        {
            Log::warning(sprintf('Player %s is not in the player list', $login));
        }
    }

    /**
     * Transitions all players of the given player status into another player status.
     *
     * @param string $login The login of the player.
     * @param PlayerStatus $playerStatus The status of the player.
     */
    public function applyStatusTransition($currentPlayerStatus, $nextPlayerStatus)
    {
        $players = logins($this->filterByStatus($currentPlayerStatus));
        foreach ($players as $login)
        {
            $this->players[$login]['Status'] = $nextPlayerStatus;
        }
    }

    /**
     * Adds a life to the given player.
     *
     * @param string $login The login of the player who should receive a life.
     * @param bool $isTiebreaker Whether there is a tiebreaker running or not. If there is, the
     * player will be shelved instead of playing.
     *
     * @return bool True if the player has been reinstated, false otherwise.
     */
    // public function addLife($login)
    // {
    //     if (isset($this->players[$login]))
    //     {
    //         $this->players[$login]['Lives'] += 1;
    //         $status = $this->players[$login]['Status'];
    //         if ($status === PlayerStatus::KnockedOut || $status === PlayerStatus::KnockedOutAndSpectating)
    //         {
    //             $this->players[$login]['Status'] = PlayerStatus::Playing;
    //             return true;
    //         }
    //         else
    //         {
    //             return false;
    //         }
    //     }
    //     else
    //     {
    //         Log::warning(sprintf('Player %s is not in the player list', $login));
    //         return false;
    //     }
    // }

    /**
     * Subtracts a life from the given player at the end of a round.
     *
     * @param string $login The login of the player who should lose a life.
     *
     * @return bool True if the player lost their last life, false otherwise.
     */
    public function subtractLife($login)
    {
        if (isset($this->players[$login]))
        {
            $lives = $this->players[$login]['Lives'];
            if ($lives <= 1)
            {
                $this->players[$login]['Lives'] = 0;
                $this->players[$login]['Status'] = PlayerStatus::KnockedOut;
                return true;
            }
            else
            {
                $this->players[$login]['Lives'] -= 1;
                return false;
            }
        }
        else
        {
            Log::warning(sprintf('Player %s is not in the player list', $login));
            return false;
        }
    }

    /**
     * Returns the players who are in the KO.
     */
    public function getPlaying()
    {
        return array_filter(
            $this->players,
            function($player) { return PlayerStatus::isIn($player['Status']); }
        );
    }

    /**
     * Returns the players who are in the KO.
     */
    public function getPlayingOrShelved()
    {
        return array_filter(
            $this->players,
            function($player) { return PlayerStatus::isIn($player['Status']) || PlayerStatus::isShelved($player['Status']); }
        );
    }

    /**
     * Returns the number of players who are in the KO.
     */
    public function countPlaying()
    {
        return count($this->getPlaying());
    }
}


/**
 * Utility class for manialinks.
 */
class UI
{
    private static function statusBarManialink($playerStatus, $knockoutStatus, $roundNumber, $numberOfPlayers, $numberOfKOs)
    {
        Log::debug(sprintf('statusBarManialink %d %d %d %d %d', $playerStatus, $knockoutStatus, $roundNumber, $numberOfPlayers, $numberOfKOs));

        $colors = array(
            KnockoutStatus::Warmup => 'f808',
            KnockoutStatus::Running => 'eee8',
            KnockoutStatus::RestartingRound => 'bbb8',
            KnockoutStatus::RestartingTrack => 'bbb8',
            KnockoutStatus::SkippingWarmup => 'f808',
            KnockoutStatus::SkippingTrack => 'bbb8',
            KnockoutStatus::Tiebreaker => 'f008',
            PlayerStatus::Playing => '0f08',
            PlayerStatus::KnockedOut => 'f008',
            PlayerStatus::KnockedOutAndSpectating => '8088',
            PlayerStatus::Shelved => '08f8',
            PlayerStatus::OptingOut => '8088'
        );
        $texts = array(
            KnockoutStatus::Warmup => 'Warmup',
            KnockoutStatus::Running => "Round {$roundNumber}",
            KnockoutStatus::RestartingRound => 'Restarting round',
            KnockoutStatus::RestartingTrack => 'Restarting track',
            KnockoutStatus::SkippingWarmup => 'Skipping warmup',
            KnockoutStatus::SkippingTrack => 'Skipping track',
            KnockoutStatus::Tiebreaker => 'Tiebreaker',
            PlayerStatus::Playing => 'Playing',
            PlayerStatus::KnockedOut => 'Knocked out',
            PlayerStatus::KnockedOutAndSpectating => 'Spectating',
            PlayerStatus::Shelved => 'Shelved',
            PlayerStatus::OptingOut => 'Opting out'
        );
        $box = function($header, $text, $bgColor = '0008')
        {
            return '
                <label posn="-5.2 1.55 1" sizen="10.4 3" halign="left" valign="center" scale="0.8" text="$ddd' . $header . '" />
                <label posn="-5.2 -0.65 1" sizen="10.4 3" halign="left" valign="center" scale="1.0" text="$fff' . $text . '" />
                <quad posn="-6 0 0" sizen="12 6" halign="left" valign="center" bgcolor="' . $bgColor . '" />
            ';
        };
        return '
            <manialink id="420">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="0 35 -2000000">
                    <frame posn="-18 0 0">
                        ' . $box('Knockout', $texts[$knockoutStatus], $colors[$knockoutStatus]) . '
                    </frame>
                    <frame posn="-6 0 0">
                        ' . $box('Players', $numberOfPlayers) . '
                    </frame>
                    <quad posn="0 0 1" sizen="0.1 6" halign="center" valign="center" bgcolor="bbb8" />
                    <frame posn="6 0 0">
                        ' . $box('KOs this round', $numberOfKOs) . '
                    </frame>
                    <frame posn="18 0 0">
                        ' . $box('Status', $texts[$playerStatus], $colors[$playerStatus]) . '
                    </frame>
                </frame>
            </manialink>
        ';
    }

    /**
     * Shows and updates the knockout info manialink for the given players.
     *
     * @param KnockoutState $knockoutState The current state of the knockout.
     * @param int $roundNb The round number.
     * @param int $nbPlayers The number of players remaining.
     * @param int $nbKOs The number of KOs this round.
     * @param array $players The players to show the manialink for. Must contain fields 'Login',
     * 'Status'.
     */
    public static function updateStatusBar($knockoutState, $roundNb, $nbPlayers, $nbKOs, $players)
    {
        Log::debug(sprintf('updateStatusBar %d %d %d %d', $knockoutState, $roundNb, $nbPlayers, $nbKOs));
        // Consider generating UIs four times, one for each player state, and apply them to each group of players
        $queries = new QueryManager();
        foreach ($players as $player)
        {
            $login = $player['Login'];
            $xml = self::statusBarManialink($player['Status'], $knockoutState, $roundNb, $nbPlayers, $nbKOs);
            $queries->add('SendDisplayManialinkPageToLogin', $login, $xml, 0, false);
        }
        $queries->submit();
    }

    /**
     * Hides the status bar for the given players.
     *
     * @param array $logins [Optional] An array of logins. If null, the status bar is hidden for
     * everyone.
     */
    public static function hideStatusBar($logins = null)
    {
        $manialink = '<manialink id="420"></manialink>';
        if (is_null($logins))
        {
            QueryManager::query('SendDisplayManialinkPage', $manialink, 0, false);
        }
        else
        {
            $commaSeparatedLogins = implode(',', $logins);
            QueryManager::query('SendDisplayManialinkPageToLogin', $commaSeparatedLogins, $manialink, 0, false);
        }
    }

    private static function scoreboardManialink($scores, $gameMode, $numberOfKOs, $numberOfPlayers)
    {
        if (!is_array($scores)) Log::warning(sprintf('scoreboardManialink: expected array $scores but got %s (%s)', $scores, gettype($scores)));
        if (!is_int($gameMode)) Log::warning(sprintf('scoreboardManialink: expected int $gameMode but got %s (%s)', $gameMode, gettype($gameMode)));
        if (!is_int($numberOfKOs)) Log::warning(sprintf('scoreboardManialink: expected int $numberOfKOs but got %s (%s)', $numberOfKOs, gettype($numberOfKOs)));
        if (!is_int($numberOfPlayers)) Log::warning(sprintf('scoreboardManialink: expected int $numberOfPlayers but got %s (%s)', $numberOfPlayers, gettype($numberOfPlayers)));

        $pointOfNoReturn = $numberOfPlayers - $numberOfKOs;
        $getPlacementColor = function($score, $index) use($pointOfNoReturn)
        {
            if ($score <= 0) return 'f00f';
            elseif ($index == 0 || ($index < $pointOfNoReturn - 2)) return '0f0f';
            elseif ($index == $pointOfNoReturn - 2) return 'ff0f';
            elseif ($index == $pointOfNoReturn - 1) return 'f80f';
            elseif ($index >= $pointOfNoReturn) return 'f00f';
            else return '000f';
        };

        $formatTime = function($milliseconds)
        {
            $centiseconds = ($milliseconds / 10) % 100;
            $seconds = ($milliseconds / 1000) % 60;
            $minutes = ($milliseconds / 60000) % 60;
            $hours = ($milliseconds / 3600000) % 24;
            if ($hours >= 1)
            {
                return sprintf('%d:%02d:%02d.%02d', $hours, $minutes, $seconds, $centiseconds);
            }
            else
            {
                return sprintf('%d:%02d.%02d', $minutes, $seconds, $centiseconds);
            }
        };

        $format = function($timeOrScore) use($gameMode, $formatTime)
        {
            if ($gameMode === GameMode::Stunts)
            {
                return $timeOrScore;
            }
            else
            {
                if ($timeOrScore > 0) return $formatTime($timeOrScore);
                elseif ($timeOrScore === Scores::HasNotFinishedYet) return '0:00.00';
                elseif ($timeOrScore === Scores::DidNotFinish) return 'DNF';
                else return '';
            }
        };

        $DNFs = array_filter($scores, function($score) { return $score['Score'] < 0; });
        $nonDNFs = array_filter($scores, function($score) { return $score['Score'] >= 0; });

        // Pad the scores such that its length equals the number of players
        $notFinished = $numberOfPlayers - count($DNFs) - count($nonDNFs);
        $padding = $notFinished > 0 ? array_fill(0, $notFinished, null) : array();
        $scoresFormatted = array_merge($nonDNFs, $padding, $DNFs);

        $box = function($index, $row) use($scoresFormatted, $getPlacementColor, $format)
        {
            $score = $scoresFormatted[$index];
            $height = 20.25 - 4.5 * $row;
            if (isset($score))
            {
                return '
                    <frame posn="-12 ' . $height . ' 1">
                        <quad posn="-12 0 1" sizen="0.2 4" halign="left" valign="center" bgcolor="' . $getPlacementColor($score['Score'], $index) . '" />
                        <label posn="-5.5 0 1" sizen="5.5 4" halign="right" valign="center" scale="1.0" text="$fff' . $format($score['Score']) . '" />
                        <label posn="-4.5 0 1" sizen="15 4" halign="left" valign="center" scale="1.0" text="$fff' . $score['NickName'] . '" />
                        <quad posn="0 0 0" sizen="24 4" halign="center" valign="center" bgcolor="3338" />
                    </frame>
                ';
            }
            else
            {
                return '';
            }
        };

        // Filter top 3 and bottom 7. The + is an array union operator which, contrary to
        // array_merge, will avoid re-indexing numeric keys
        // https://www.php.net/manual/en/function.array-merge.php
        $scoresToDisplay = array_slice($scoresFormatted, 0, 3, true) + array_slice($scoresFormatted, -7, 7, true);

        $rows = '';
        $i = 0;
        foreach ($scoresToDisplay as $index => $scoreObj)
        {
            if (!is_null($scoreObj))
            {
                $rows .= $box($index, $i);
            }
            $i++;
        }

        // BgList or BgCardList
        // <frame posn="39.5 25 -2000000">
        return '
            <manialink id="430">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="64.5 -6.5 -100">
                    ' . $rows . '
                    <quad posn="0 0 -1" sizen="25 47" halign="right" valign="center" style="Bgs1InRace" substyle="BgList" />
                </frame>
            </manialink>
            <custom_ui>
                <notice visible="true"/>
                <challenge_info visible="true"/>
                <chat visible="true"/>
                <checkpoint_list visible="true"/>
                <round_scores visible="false"/>
                <scoretable visible="true"/>
                <global visible="true"/>
            </custom_ui>
        ';
    }

    /**
     * Updates the scoreboard for the given players.
     *
     * @param array $scores The scores object to use. Must contain fields 'NickName' and 'Score'.
     * @param GameMode $gameMode The current game mode.
     * @param int $numberOfKOs The number of KOs to be performed this round (shown as red bars).
     * @param int $numberOfPlayers The number of players currently in this round.
     * @param array $logins [Optional] The logins to display the scoreboard for. If null, the
     * scoreboard is displayed for everyone.
     */
    public static function updateScoreboard($scores, $gameMode, $numberOfKOs, $numberOfPlayers, $logins = null)
    {
        Log::debug('updating scoreboard...');
        $manialink = self::scoreboardManialink($scores, $gameMode, $numberOfKOs, $numberOfPlayers);
        if (is_null($logins))
        {
            QueryManager::query('SendDisplayManialinkPage', $manialink, 0, false);
        }
        else
        {
            $commaSeparatedLogins = implode(',', $logins);
            QueryManager::query('SendDisplayManialinkPageToLogin', $commaSeparatedLogins, $manialink, 0, false);
        }
    }

    private static function emptyScoreboardManialink()
    {
        return '
            <manialink id="430">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="64.5 -6.5 -100"></frame>
            </manialink>
            <custom_ui>
                <notice visible="true"/>
                <challenge_info visible="true"/>
                <chat visible="true"/>
                <checkpoint_list visible="true"/>
                <round_scores visible="false"/>
                <scoretable visible="true"/>
                <global visible="true"/>
            </custom_ui>
        ';
    }

    /**
     * Hides the scoreboard for the given players.
     *
     * @param array $logins [Optional] The logins to hide the scoreboard for. If null, the
     * scoreboard is hidden for everyone.
     */
    public static function hideScoreboard($logins = null)
    {
        Log::debug('hiding scoreboard...');
        $manialink = self::emptyScoreboardManialink();
        if (is_null($logins))
        {
            QueryManager::query('SendDisplayManialinkPage', $manialink, 0, false);
        }
        else
        {
            $commaSeparatedLogins = implode(',', $logins);
            QueryManager::query('SendDisplayManialinkPageToLogin', $commaSeparatedLogins, $manialink, 0, false);
        }
    }

    /**
     * Restores the default scoreboard.
     */
    public static function restoreDefaultScoreboard()
    {
        Log::debug('restoring default scoreboard...');
        $manialink = '
            <manialink id="430"></manialink>
            <custom_ui>
                <notice visible="true"/>
                <challenge_info visible="true"/>
                <chat visible="true"/>
                <checkpoint_list visible="true"/>
                <round_scores visible="true"/>
                <scoretable visible="true"/>
                <global visible="true"/>
            </custom_ui>
        ';
        QueryManager::query('SendDisplayManialinkPage', $manialink, 0, false);
    }

    /**
     * Shows a bigger info dialog with an OK button.
     *
     * @param string $text The text to display. Must be manually broken into lines, otherwise, the
     * text will become crammed.
     * @param array $logins The player(s) to display the dialog for.
     */
    public static function showInfoDialog($text, $logins)
    {
        $manialink = '
            <manialink id="440">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="-40 43 1">
                    <quad posn="-1 1 0" sizen="82 78" halign="top" valign="left" style="Bgs1" substyle="BgWindow3" />
                    <label posn="0 0 1" sizen="80 3" halign="left" style="TextStaticSmall">' . $text . '</label>
                    <label posn="40 -73 1" sizen="1 1" halign="center" valign="center" style="CardButtonMedium" action="99">Ok</label>
                </frame>
            </manialink>
        ';
        $commaSeparatedLogins = implode(',', $logins);
        QueryManager::query('SendDisplayManialinkPageToLogin', $commaSeparatedLogins, $manialink, 0, true);
    }

    /**
     * Shows a bigger info dialog with page count, arrows and an OK button.
     *
     * @param string $text The text to display. Must be manually broken into lines, otherwise, the
     * text will extend beyond the become crammed.
     * @param array $logins The player(s) to display the dialog for.
     * @param int $currentPageNumber The current page number (1-based).
     * @param int $totalPages The total number of pages.
     * @param int $prevPageActionId [Optional] The action id that results in the previous page. If
     * null, the button is greyed out.
     * @param int $nextPageActionId [Optional] The action id that results in the next page. If null,
     * the button is greyed out.
     */
    public static function showMultiPageDialog($text, $logins, $currentPageNumber, $totalPages, $prevPageActionId = null, $nextPageActionId = null)
    {
        $prevPage = is_null($prevPageActionId)
            ? '<quad posn="1.5 0 1" sizen="3 3" halign="center" valign="center" style="Icons64x64_1" substyle="StarGold" />'
            : '<quad posn="1.5 0 1" sizen="3 3" halign="center" valign="center" style="Icons64x64_1" substyle="ArrowPrev" action="' . $prevPageActionId . '" />';
        $nextPage = is_null($nextPageActionId)
            ? '<quad posn="5 0 1" sizen="3 3" halign="center" valign="center" style="Icons64x64_1" substyle="StarGold" />'
            : '<quad posn="5 0 1" sizen="3 3" halign="center" valign="center" style="Icons64x64_1" substyle="ArrowNext" action="' . $nextPageActionId . '" />';
        $manialink = '
            <manialink id="440">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="-40 43 1">
                    <quad posn="-1 1 0" sizen="82 78" halign="top" valign="left" style="Bgs1" substyle="BgWindow3" />
                    <label posn="0 0 1" sizen="80 3" halign="left" style="TextStaticSmall">' . $text . '</label>
                    <label posn="40 -73 1" sizen="1 1" halign="center" valign="center" style="CardButtonMedium" action="99">Ok</label>
                    <frame posn="72 -73 1">
                        <quad posn="0 0 0" sizen="14 4" halign="center" valign="center" style="Bgs1" substyle="BgButton" />
                        <label posn="-5 0.1 1" sizen="6 4" halign="left" valign="center">$o$444' . $currentPageNumber . '/' . $totalPages . '</label>
                        ' . $prevPage . '
                        ' . $nextPage . '
                    </frame>
                </frame>
            </manialink>
        ';
        $commaSeparatedLogins = implode(',', $logins);
        QueryManager::query('SendDisplayManialinkPageToLogin', $commaSeparatedLogins, $manialink, 0, true);
    }

    /**
     * Shows a small, scalable prompt with two buttons, one for confirmation (Yes) and one for
     * cancellation (No).
     *
     * @param string $text The text to display. Must be manually broken into lines, otherwise, the
     * text will become crammed.
     * @param int $actionId The ID to use in playerManialinkPageAnswer when clicking the Yes button.
     * @param array $logins The logins to display the prompt for.
     */
    public static function showPrompt($text, $actionId, $logins)
    {
        $nbLines = substr_count($text, "\n") + 1;
        $textboxHeight = $nbLines * 2.5;
        $print = function($value) { return sprintf('%1.1f', $value); };
        $manialink = '
            <manialink id="450">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="0 0 1">
                    <quad posn="0 0 0" sizen="64.8 ' . $print($textboxHeight + 12.0) . '" halign="center" valign="center" style="Bgs1" substyle="BgWindow3" />
                    <!-- <quad posn="-30.5 ' . $print(0.5 * $textboxHeight + 3.0) . ' 0.5" sizen="61 ' . $print($textboxHeight) . '" halign="left" valign="top" bgcolor="8888" /> -->
                    <label posn="0 ' . $print(0.5 * $textboxHeight + 3.0) . ' 1" sizen="61 ' . $print($textboxHeight) . '" halign="center" valign="top" style="TextStaticSmall">' . $text . '</label>
                    <label posn="-14.9 ' . $print(-0.5 * $textboxHeight - 1.0) . ' 1" halign="center" valign="center" style="CardButtonMedium" action="' . $actionId . '">Yes</label>
                    <label posn="14.9 ' . $print(-0.5 * $textboxHeight - 1.0) . ' 1" halign="center" valign="center" style="CardButtonMedium" action="99">No</label>
                </frame>
            </manialink>
        ';
        $commaSeparatedLogins = implode(',', $logins);
        QueryManager::query('SendDisplayManialinkPageToLogin', $commaSeparatedLogins, $manialink, 0, true);
    }
}


/**
 * Determines the number of KOs to perform.
 */
class KOMultiplier
{
    const None = 0;
    const Constant = 1;
    const Extra = 2;
    const Dynamic = 3;
    const Tiebreaker = 4;

    private $mode;
    private $value;
    private $prevMode;
    private $prevValue;

    public function __construct()
    {
        $this->mode = self::None;
        $this->value = null;
        $this->prevMode = self::None;
        $this->prevValue = null;
    }

    /**
     * Returns a string representation of this multiplier.
     */
    public function toString()
    {
        switch ($this->mode)
        {
            case self::None:
                return 'None';
            case self::Constant:
                return sprintf('Constant (%d %s per round)', $this->value, $this->value === 1 ? 'KO' : 'KOs');
            case self::Extra:
                return sprintf('Extra (KO per %d %s)', $this->value, $this->value === 1 ? 'player' : 'players');
            case self::Dynamic:
                return sprintf('Dynamic (aiming for %d %s)', $this->value, $this->value === 1 ? 'round' : 'rounds');
            case self::Tiebreaker:
                return sprintf('Tiebreaker (%d %s remaining)', $this->value, $this->value === 1 ? 'KO' : 'KOs');
            default:
                Log::warning(
                    'KO multiplier is in unknown state (%s, %d)',
                    getNameOfConstant($this->mode, 'KOMultiplier'),
                    $this->value
                );
                return null;
        }
    }

    /**
     * Gets the number of KOs to perform in this round.
     *
     * @param int $roundNumber The current round number.
     * @param int $numberOfPlayersLeft The number of players left in the KO.
     *
     * @return int The number of KOs to be applied this round.
     */
    public function getKOsThisRound($roundNumber, $numberOfPlayersLeft)
    {
        switch ($this->mode)
        {
            case self::None:
                return 1;
            case self::Constant:
                return $this->value;
            case self::Extra:
                return (int) ceil($numberOfPlayersLeft / $this->value);
            case self::Dynamic:
                $func = $this->solveCurve($this->baseCurve(), $roundNumber, $this->value, $numberOfPlayersLeft, 1);
                return $func($roundNumber);
            case self::Tiebreaker:
                return $this->value;
            default:
                Log::warning(
                    'KO multiplier is in unknown state (%s, %d)',
                    getNameOfConstant($this->mode, 'KOMultiplier'),
                    $this->value
                );
                return null;
        }
    }

    /**
     * Sine function of KOs per round minus 1.
     *
     * @link https://colab.research.google.com/drive/1QO312KzpRUfsSWfQjfpW3bC9hxxcr29v?usp=sharing
     *
     * @author Solux
     */
    private function baseCurve()
    {
        $totalRounds = $this->value;
        return function($round) use($totalRounds)
        {
            $transition = $totalRounds - 4.75; // round where the amount of kos reaches 1 ko/round again
            if ($round < $transition)
            {
                return 1 - cos($round * M_PI / ($transition / 2));
            }
            else
            {
                return 0;
            }
        };
    }

    /**
     * Returns the scaled curve + 1 -> the amount of kos per round for the
     * scaling factor f.
     *
     * @link https://colab.research.google.com/drive/1QO312KzpRUfsSWfQjfpW3bC9hxxcr29v?usp=sharing
     *
     * @author Solux
     */
    private function getNonDiscretizedScaledCurve($baseCurve, $f)
    {
        return function($round) use($baseCurve, $f)
        {
            return $f * $baseCurve($round) + 1;
        };
    }

    /**
     * Returns the scaled curve + 1 -> the amount of kos per round for the
     * scaling factor f, rounded to the nearest integer.
     *
     * @link https://colab.research.google.com/drive/1QO312KzpRUfsSWfQjfpW3bC9hxxcr29v?usp=sharing
     *
     * @author Solux
     */
    private function getScaledCurve($baseCurve, $f)
    {
        $nonDiscretizedCurve = $this->getNonDiscretizedScaledCurve($baseCurve, $f);
        return function($round) use($nonDiscretizedCurve)
        {
            return (int) round($nonDiscretizedCurve($round));
        };
    }

    /**
     * Calculates the target curve c(r) = a * base_curve(r) + 1 so that the sum
     * over c(r) for all rounds equals the target ko count.
     *
     * Optimized version of the solving algorithm that accounts for cases that
     * cannot be solved in discrete space and readjusts the curve by 1 ko for a
     * single round if needed.
     *
     * @link https://colab.research.google.com/drive/1QO312KzpRUfsSWfQjfpW3bC9hxxcr29v?usp=sharing
     *
     * @author Solux
     */
    private function solveCurve($curve, $currentRound, $totalRounds, $playersLeft, $playersToRemainAfterEnd)
    {
        $baseCurve = $this->baseCurve();
        $playersToKO = $playersLeft - $playersToRemainAfterEnd;
        if ($currentRound === $totalRounds - 1)
        {
            // Just directly give the curve so that all of the remaining kos are done
            return function($round) use($playersToKO) { return $playersToKO; };
        }
        elseif ($totalRounds - $currentRound - 1 === $playersToKO)
        {
            // Only 1 ko/round for the remaining ko, no optimization needed
            return function($round) { return 1; };
        }
        elseif ($playersToKO < $totalRounds - $currentRound - 1)
        {
            Log::warning('Cannot solve for scaling factor if target kos/round is less than 1');
            return function($round) { return 1; };
        }

        $calculateTotalRemainingKOs = function($scaledCurve, $currentRound, $totalRounds)
        {
            $totalKOs = 0;
            for ($i = $currentRound; $i < $totalRounds; $i++)
            {
                $totalKOs += $scaledCurve($i);
            }
            return $totalKOs;
        };

        // Initial scaling factor value to start with
        $f = 1;
        // The optimization step
        $fStep = 0.95;
        // Amount of remaining kos for current f, actually discretized instead of it being floating-point
        $kos = $calculateTotalRemainingKOs(
            $this->getScaledCurve($baseCurve, $f), $currentRound, $totalRounds
        );
        $sg = sign($playersToKO - $kos);
        Log::debug(sprintf('f: %f, fStep: %f, kos: %f, sg: %f, playersToKO: %f', $f, $fStep, $kos, $sg, $playersToKO));

        $optimizationStep = 0;
        while ($kos !== $playersToKO)
        {
            // Readjust f
            $f = $f + $sg * $fStep;
            $currentCurve = $this->getScaledCurve($baseCurve, $f);
            $kos = $calculateTotalRemainingKOs($currentCurve, $currentRound, $totalRounds);
            $sgNew = sign($playersToKO - $kos);
            if ($sgNew != $sg)
            {
                // Reduce optimization step size if we overshot target
                $fStep = 0.25 * $fStep;
            }
            $sg = $sgNew;
            Log::debug(sprintf('f: %f, fStep: %f, kos: %f, sg: %f, playersToKO: %f', $f, $fStep, $kos, $sg, $playersToKO));

            $optimizationStep++;
            if ($fStep <= 1E-15)
            {
                // See whether the curve is descending or ascending for the next step
                $sgCurve = sign($currentCurve($currentRound + 1) - $currentCurve($currentRound));
                if (sign($playersToKO - $kos) === -$sgCurve)
                {
                    // Adjust the calculated curve accordingly, increasing or decreasing it by 1 to compensate, but don't let it go down below 1
                    $adjustedCurve = function($round) use($currentCurve, $sgCurve)
                    {
                        return max(1, $currentCurve($round) - $sgCurve);
                    };
                    // Check deviation of adjusted curve relative to non-discretized target curve
                    if (abs($adjustedCurve($currentRound)) - $this->getNonDiscretizedScaledCurve($curve, $f) <= 0.5)
                    {
                        Log::error('solveCurve did not converge; fStep is too small');
                        return $adjustedCurve;
                    }
                }
            }
            if ($optimizationStep > 500)
            {
                Log::error('solveCurve did not converge; optimization step limit reached');
                return $currentCurve;
            }
        }

        return $this->getScaledCurve($curve, $f);
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function reset()
    {
        $this->mode = self::None;
        $this->value = null;
        $this->projection = array();
    }

    public function revert()
    {
        $this->mode = $this->prevMode;
        $this->value = $this->prevValue;
    }

    public function set($mode, $value)
    {
        Log::debug("KOMultiplier.set {$mode} {$value}");
        $this->prevMode = $this->mode;
        $this->prevValue = $this->value;
        $this->mode = $mode;
        $this->value = $value;
    }
}


/**
 * Tests if the given player is connected to the server.
 *
 * @param string $login The login of the player.
 * @return bool True if the player is currently on the server.
 */
function isOnServer($login)
{
    global $PlayerScript;
    return isset($PlayerScript[$login]);
}


/**
 * Tests if the given player has Tm-Gery HUD enabled. If the player is not found, false is returned.
 *
 * @param string $login The login of the player.
 * @return bool True if HUD is shown for the player.
 */
function hasHudOn($login)
{
    global $PlayerScript;
    if (isset($PlayerScript[$login]))
    {
        return $PlayerScript[$login] === '1';
    }
    else
    {
        Log::warning(sprintf('Could not find player %s in PlayerScript', $login));
        return false;
    }
}


/**
 * Forces given players into play.
 *
 * @param array $logins An array of logins.
 * @param bool $force Whether the players should be able to switch between play
 * and spec after forcing.
 *
 * @return bool True if the queries were sent successfully, false if an error
 * occurred or there are no players to be forced.
 */
function forcePlay($logins, $force)
{
    if (count($logins) > 0)
    {
        Log::debug(sprintf(
            'Forcing the following players into play (%s): %s',
            $force ? 'ForcePlay' : 'UserSelectable',
            print_r($logins, true)
        ));
        $queries = new QueryManager();
        foreach ($logins as $login)
        {
            $queries->add('ForceSpectator', $login, SpectatorMode::Player);
            if (!$force) $queries->add('ForceSpectator', $login, SpectatorMode::UserSelectable);
        }
        return $queries->submit();
    }
    else
    {
        return false;
    }
}


/**
 * Forces given players into being spectators.
 *
 * @param array $logins An array of logins.
 * @param bool $force Whether the players should be able to switch between play
 * and spec after forcing.
 *
 * @return bool True if the queries were sent successfully, false if an error
 * occurred or there are no players to be forced.
 */
function forceSpec($logins, $force)
{
    if (count($logins) > 0)
    {
        Log::debug(sprintf(
            'Forcing the following players into spec (%s): %s',
            $force ? 'ForceSpec' : 'UserSelectable',
            print_r($logins, true)
        ));
        $queries = new QueryManager();
        foreach ($logins as $login)
        {
            $queries->add('ForceSpectator', $login, SpectatorMode::Spectator);
            if (!$force) $queries->add('ForceSpectator', $login, SpectatorMode::UserSelectable);
        }
        return $queries->submit();
    }
    else
    {
        return false;
    }
}


/**
 * Returns the logins of a player array.
 *
 * @param array $players An array of player structs, each containing a field 'Login'.
 *
 * @return array An array of the logins of the players.
 */
function logins($players)
{
    return array_map(
        function($player) { return $player['Login']; },
        $players
    );
}


/**
 * Tests if a player is a spectator.
 *
 * @param array $player An SPlayerInfo struct of the given player, received from a callback or
 * retrieved using 'GetPlayerInfo' or 'GetDetailedPlayerInfo'.
 *
 * @return bool True if the player object is configured to be spectating, false otherwise.
 */
function isSpectator($player)
{
    if (is_null($player))
    {
        return false;
    }
    else
    {
        return (array_key_exists('IsSpectator', $player) && $player['IsSpectator'])
            || (array_key_exists('SpectatorStatus', $player) && substr($player['SpectatorStatus'], 4, 1) === '1')
            || (array_key_exists('Flags', $player) && substr($player['Flags'], 6, 1) === '1');
    }
}


/**
 * Tests if a player is currently forced to play or forced to spec.
 *
 * @param array $player An SPlayerInfo struct of the given player, received from a callback or
 * retrieved using 'GetDetailedPlayerInfo'.
 *
 * @return bool True if the player is not able to enter/exit spectator mode, false otherwise.
 */
function isForced($player)
{
    if (is_null($player))
    {
        return false;
    }
    elseif (array_key_exists('Flags', $player))
    {
        $forceSpectator = (int) substr($player['Flags'], 6, 1);
        return $forceSpectator !== SpectatorMode::UserSelectable;
    }
    else
    {
        return false;
    }
}


/**
 * The main runtime to be attached to Tm-Gery's plugin manager.
 */
class KnockoutRuntime
{
    // Knockout state
    private $koMode;
    private $playerList;
    private $scores;
    private $roundNumber;
    private $koStatus;
    private $roundStartTime; // The time of which the current round started (in seconds)
    private $falseStartCount;
    private $shouldCheckForFalseStarts;
    private $kosThisRound;

    // Server info
    private $isWarmup;
    private $isPodium;
    private $gameMode;

    // Defaults
    private $defaultVoteTimeout = 60;
    private $defaultPointsLimit = 10;
    private $defaultPointPartition = array(10, 8, 7, 6, 5, 4, 3, 2, 1);
    private $authorSkipLimit = 10;

    // Settings
    private $koMultiplier;
    private $lives;
    private $openWarmup;
    private $tiebreaker;
    private $maxFalseStarts;
    private $maxRounds;
    private $authorSkip;

    public function __construct()
    {
        $this->koMode = KnockoutMode::Normal;
        $this->playerList = new PlayerList();
        $this->scores = new Scores();
        $this->roundNumber = 0;
        $this->koStatus = KnockoutStatus::Idle;
        $this->roundStartTime = 0.0;
        $this->falseStartCount = 0;
        $this->shouldCheckForFalseStarts = false;
        $this->kosThisRound = 0;

        $this->isWarmup = false;
        $this->isPodium = false;
        $this->gameMode = -1;

        $this->koMultiplier = new KOMultiplier();
        $this->lives = 1;
        $this->openWarmup = true;
        $this->tiebreaker = true;
        $this->maxFalseStarts = 2;
        $this->maxRounds = 1;
        $this->authorSkip = 7;
    }

    /**
     * Callback method for when Tm-Gery is starting up.
     */
    public function onControllerStartup()
    {
        global $PlayerList;

        $this->isWarmup = QueryManager::queryWithResponse('GetWarmUp');
        $status = QueryManager::queryWithResponse('GetStatus');
        $this->isPodium = !$this->isWarmup && $status['Code'] === ServerStatus::Finish;
        $this->gameMode = QueryManager::queryWithResponse('GetGameMode');

        // In case the plugin crashed mid-KO
        UI::hideStatusBar();
        UI::restoreDefaultScoreboard();
        forcePlay(logins($PlayerList), false);
        QueryManager::query('SetCallVoteTimeOut', $this->defaultVoteTimeout);
        QueryManager::query('SetRoundCustomPoints', $this->defaultPointPartition);
        QueryManager::query('SetRoundPointsLimit', $this->defaultPointsLimit);

        Chat::info(sprintf('Knockout plugin $fff%s$g loaded', Version));
    }

    private function getPlayersWithHudOn()
    {
        global $PlayerScript;
        return array_intersect_key(
            $this->playerList->getAll(),
            array_filter($PlayerScript, function($val) {
                return $val === '1';
            })
        );
    }

    private function getPlayersWithHudOff()
    {
        global $PlayerScript;
        return array_intersect_key(
            $this->playerList->getAll(),
            array_filter($PlayerScript, function($val) {
                return $val !== '1';
            })
        );
    }

    private function updateKoCount()
    {
        $playerCount = $this->playerList->countPlaying();
        $this->kosThisRound = $this->koMultiplier->getKOsThisRound($this->roundNumber, $playerCount);
    }

    private function updateStatusBar($login = null)
    {
        // Make KO GUI optional together with Tm-Gery GUI
        $playersWithHudOn = array();
        if ($login !== null)
        {
            if (hasHudOn($login))
            {
                $playersWithHudOn = array($this->playerList->get($login));
            }
        }
        else
        {
            $playersWithHudOn = $this->getPlayersWithHudOn();
        }
        if (count($playersWithHudOn) > 0)
        {
            $playerCount = $this->playerList->countPlaying();
            UI::updateStatusBar($this->koStatus, $this->roundNumber, $playerCount, $this->kosThisRound, $playersWithHudOn);
        }
    }

    private function updateScoreboard($login = null)
    {
        $scores = $this->scores->getSortedScores();
        $nbKOs = $this->kosThisRound;
        $numberOfPlayers = $this->playerList->countPlaying();
        $logins = $login === null ? null : array($login);
        UI::updateScoreboard($scores, $this->gameMode, $nbKOs, $numberOfPlayers, $logins);
    }

    private function announceRoundInChat($login = null)
    {
        $playersWithHudOff = array();
        if ($login !== null)
        {
            if (!hasHudOn($login))
            {
                $playersWithHudOff = array($this->playerList->get($login));
            }
        }
        else
        {
            $playersWithHudOff = array_keys($this->getPlayersWithHudOff());
        }
        if (count($playersWithHudOff) > 0)
        {
            switch ($this->koStatus)
            {
                case KnockoutStatus::Warmup:
                    Chat::announce('Knockout Warmup', $playersWithHudOff, '$f80');
                    foreach ($playersWithHudOff as $login)
                    {
                        $player = $this->playerList->get($login);
                        if ($player !== false)
                        {
                            Chat::info(
                                sprintf('Status: %s', PlayerStatus::output($player['Status'])),
                                array($login)
                            );
                        }
                    }
                    break;
                case KnockoutStatus::Tiebreaker:
                    Chat::announce('Knockout Tiebreaker', $playersWithHudOff, '$f00');
                    break;
                case KnockoutStatus::Running:
                    Chat::announce(sprintf('Knockout Round $fff%d', $this->roundNumber), $playersWithHudOff);
                    Chat::info(
                        sprintf(
                            '$fff%d$g %s remaining, $fff%d$g %s this round',
                            count($this->playerList->getPlaying()),
                            count($this->playerList->getPlaying()) === 1 ? 'player' : 'players',
                            $this->kosThisRound,
                            $this->kosThisRound === 1 ? 'KO' : 'KOs'
                        ),
                        $playersWithHudOff
                    );
                    foreach ($playersWithHudOff as $login)
                    {
                        $player = $this->playerList->get($login);
                        if ($player !== false)
                        {
                            Chat::info(
                                sprintf('Status: %s', PlayerStatus::output($player['Status'])),
                                array($login)
                            );
                        }
                    }
                    break;
                case KnockoutStatus::RestartingRound:
                case KnockoutStatus::RestartingTrack:
                case KnockoutStatus::SkippingTrack:
                case KnockoutStatus::SkippingWarmup:
                case KnockoutStatus::Starting:
                case KnockoutStatus::StartingNow:
                case KnockoutStatus::Stopping:
                    break;
                default:
                    Log::warning(sprintf(
                        'AnnounceRoundInChat: knockout is in unexpected mode %s',
                        getNameOfConstant($this->koStatus, 'KnockoutStatus')
                    ));
                    break;
            }
        }
    }

    private function reflectScoringWithGameMode()
    {
        switch ($this->gameMode)
        {
            case GameMode::Stunts:
                $this->scores->setSortingOrder(false);
                break;

            case GameMode::Laps:
            case GameMode::Rounds:
            case GameMode::TimeAttack:
                $this->scores->setSortingOrder(true);
                break;
        }
    }

    private function hudReminder()
    {
        $playersWithHudOff = array_keys($this->getPlayersWithHudOff());
        Chat::info('To enable the HUD, click on the TMGery button in the top left', $playersWithHudOff);
    }

    /**
     * Starts the knockout.
     *
     * @param array $players  Players to start with (result of GetPlayerList query)
     * @param bool $now       True to skip the current track and start the KO immediately.
     */
    private function start($players, $now = false)
    {
        QueryManager::query('SetCallVoteTimeOut', 0);
        $points = QueryManager::queryWithResponse('GetRoundPointsLimit');
        $this->defaultPointsLimit = $points['NextValue'];
        Log::debug(sprintf('setting points limit to %d', $this->maxRounds));
        QueryManager::query('SetRoundPointsLimit', $this->maxRounds);
        $this->playerList->addAll($players, PlayerStatus::Playing, $this->lives);
        forcePlay(logins($this->playerList->getAll()), true);
        if ($now)
        {
            $this->koStatus = KnockoutStatus::StartingNow; // Will be set to Running in onEndRound
            if ($this->isPodium)
            {
                Chat::announce('Knockout starting!');
                $this->hudReminder();
            }
            else
            {
                QueryManager::query('NextChallenge');
            }
        }
        elseif ($this->isPodium)
        {
            $this->koStatus = KnockoutStatus::Running;
            Chat::announce('Knockout starting!');
            $this->hudReminder();
        }
        else
        {
            $this->koStatus = KnockoutStatus::Starting;
            Chat::info('Knockout scheduled to start on the next round');
        }
    }

    private function stop()
    {
        $this->roundNumber = 0;
        UI::hideStatusBar();
        UI::hideScoreboard();
        if ($this->koStatus === KnockoutStatus::Tiebreaker)
        {
            $this->koMultiplier->revert();
        }
        forcePlay(logins($this->playerList->getAll()), false);
        $this->playerList->reset();
        $this->scores->reset();
        QueryManager::query('SetCallVoteTimeOut', $this->defaultVoteTimeout);
        QueryManager::query('SetRoundCustomPoints', $this->defaultPointPartition);
        // Todo: set round points limit later (can't be done now)
        Log::debug(sprintf('setting points limit to %d', $this->defaultPointsLimit));
        QueryManager::query('SetRoundPointsLimit', $this->defaultPointsLimit);
        $this->koStatus = KnockoutStatus::Idle;
    }

    private function add($playersToAdd)
    {
        $isTiebreaker = $this->koStatus === KnockoutStatus::Tiebreaker;
        $status = $isTiebreaker ? PlayerStatus::Shelved : PlayerStatus::Playing;
        $toPlay = array();
        $toSpec = array();

        foreach ($playersToAdd as $player)
        {
            $login = $player['Login'];
            if (!$this->playerList->exists($login))
            {
                $nickname = $player['NickName'];
                $this->playerList->add($login, $nickname, $status, $this->lives);
                if ($isTiebreaker)
                {
                    $toSpec[] = $login;
                }
                else
                {
                    $toPlay[] = $login;
                }
            }
            else
            {
                $this->playerList->setStatus($login, $status);
                if ($isTiebreaker)
                {
                    $toSpec[] = $login;
                }
                else
                {
                    $toPlay[] = $login;
                }
                $this->playerList->setLives($login, $this->lives);
            }
        }
        forcePlay($toPlay, true);
        forceSpec($toSpec, true);
        $this->onKoStatusUpdate();
    }

    private function remove($playersToRemove, $status)
    {
        foreach ($playersToRemove as $player)
        {
            $login = $player['Login'];
            if (!$this->playerList->exists($login))
            {
                $nickname = $player['NickName'];
                $this->playerList->add($login, $nickname, $status, 0);
            }
            else
            {
                $target = $this->playerList->get($login);
                switch ($target['Status'])
                {
                    case PlayerStatus::Playing:
                        $shouldDNF =
                            ($this->koStatus === KnockoutStatus::Running || $this->koStatus === KnockoutStatus::Tiebreaker)
                            && PlayerStatus::isIn($target['Status']);
                        if ($shouldDNF)
                        {
                            $this->scores->set($login, $target['NickName'], Scores::DidNotFinish);
                            $this->playerList->setLives($login, 1);
                        }
                        else
                        {
                            $this->playerList->setStatus($login, $status);
                            $this->playerList->setLives($login, 0);
                        }
                        break;
                    case PlayerStatus::Shelved:
                        $this->playerList->setStatus($login, $status);
                        $this->playerList->setLives($login, 0);
                        break;

                    case PlayerStatus::KnockedOut:
                    case PlayerStatus::KnockedOutAndSpectating:
                        // Do nothing
                        break;

                    case PlayerStatus::PlayingAndDisconnected:
                    case PlayerStatus::ShelvedAndDisconnected:
                        $this->playerList->remove($login);
                        break;
                }
            }
        }

        if ($this->koStatus === KnockoutStatus::Tiebreaker)
        {
            $this->returnFromTiebreaker();
        }
        elseif ($status === PlayerStatus::KnockedOut && $this->openWarmup && ($this->isWarmup || $this->isPodium))
        {
            forcePlay(logins($playersToRemove), false);
        }
        else
        {
            forceSpec(logins($playersToRemove), true);
        }
        $this->onKoStatusUpdate();
    }

    private function adjustLivesRelatively($playersToUpdate, $lives, $persist)
    {
        // Assuming all player objects are from playerList
        $toForcePlay = array();
        $toPlay = array();
        $toSpec = array();
        foreach ($playersToUpdate as $player)
        {
            $remainingLives = $player['Lives'] + $lives;
            $this->playerList->setLives($player['Login'], max(0, $remainingLives));
            if ($remainingLives <= 0)
            {
                // Knocked out
                if ($this->openWarmup && ($this->isWarmup || $this->isPodium))
                {
                    $toPlay[] = $player['Login'];
                }
                else
                {
                    $toSpec[] = $player['Login'];
                }
            }
            elseif ($player['Lives'] === 0)
            {
                // Reinstated
                $status = $this->koStatus === KnockoutStatus::Tiebreaker ? PlayerStatus::Shelved : PlayerStatus::Playing;
                $this->playerList->setStatus($player['Login'], $status);
                $toForcePlay[] = $player['Login'];
            }
        }
        forcePlay($toForcePlay, true);
        forcePlay($toPlay, false);
        forcePlay($toSpec, false);
        if (count($this->playerList->getPlaying()) === 0 && $this->koStatus === KnockoutStatus::Tiebreaker)
        {
            $this->returnFromTiebreaker();
        }
        if ($persist) $this->lives += $lives;
        $this->onKoStatusUpdate();
    }

    private function adjustLives($playersToUpdate, $lives, $persist)
    {
        // Assuming that only playing and shelved players have their lives
        // adjusted, and $lives > 0
        foreach ($playersToUpdate as $player)
        {
            $this->playerList->setLives($player['Login'], $lives);
        }
        if ($persist) $this->lives = $lives;
        $this->onKoStatusUpdate();
    }

    private function onTrackChange()
    {
        if ($this->koStatus !== KnockoutStatus::Idle)
        {
            $this->koStatus = KnockoutStatus::Running;
            $this->falseStartCount = 0;
            $this->scores->reset();
            UI::hideScoreboard();
        }
    }

    private function letKnockedOutPlayersPlay()
    {
        forcePlay(
            logins($this->playerList->filterByStatus(PlayerStatus::KnockedOut)),
            false
        );
        forceSpec(
            logins($this->playerList->filterByStatus(PlayerStatus::KnockedOutAndSpectating)),
            false
        );
    }

    private function putKnockedOutPlayersIntoSpec()
    {
        forceSpec(
            logins($this->playerList->filterByStatus(PlayerStatus::KnockedOut)),
            true
        );
        forceSpec(
            logins($this->playerList->filterByStatus(PlayerStatus::KnockedOutAndSpectating)),
            true
        );
    }

    private function ko($login, $score)
    {
        $player = $this->playerList->get($login);
        $nickName = $player['NickName'];
        $isKO = $this->playerList->subtractLife($login);
        if ($isKO)
        {
            forceSpec(array($login), true);
            $msg = $score > 0
                ? sprintf('%s$z$s$g has been KO\'d by a worst place finish', $nickName)
                : sprintf('%s$z$s$g has been KO\'d by a DNF', $nickName);
            Chat::info($msg);
            if (PlayerStatus::isDisconnected($player['Status']))
            {
                $this->playerList->remove($login);
            }
        }
        else
        {
            $lives = $player['Lives'] - 1;
            $msg = $score > 0
                ? sprintf('%s$z$s$g lost a life by a worst place finish ($fff%d$g remaining)', $nickName, $lives)
                : sprintf('%s$z$s$g lost a life by a DNF ($fff%d$g remaining)', $nickName, $lives);
            Chat::info($msg);
        }
        $this->updateStatusBar($login);
    }

    // Recursive function that KOs the last player in the scores array until there are no more
    // KOs, or a tiebreaker is detected
    private function recursiveKO($scores, $nbKOs)
    {
        if (count($scores) === 0)
        {
            return false;
        }

        $i = count($scores) - 1;
        $lastPlayerLogin = $scores[$i]['Login'];
        $lastPlayerScore = $scores[$i]['Score'];

        if ($lastPlayerScore <= 0)
        {
            $this->ko($lastPlayerLogin, $lastPlayerScore);
            array_pop($scores);
            // $rest = array_slice($scores, 0, -1, true);
            return $this->recursiveKO($scores, $nbKOs - 1);
        }
        elseif ($nbKOs > 0)
        {
            // Check if there are players with the same score, and how many
            $j = $i - 1;
            while ($j >= 0 && isset($scores[$j]) && $scores[$j]['Score'] === $lastPlayerScore)
            {
                $j--;
            }
            if ($i - $j > $nbKOs)
            {
                // Return tied players
                $tiedPlayers = array_map(
                    function($score) { return $score['Login']; },
                    array_slice($scores, $j + 1, $i - $j, true)
                );
                return array(
                    'TiedPlayers' => $tiedPlayers,
                    'KOsRemaining' => $nbKOs
                );
            }
            else
            {
                $this->ko($lastPlayerLogin, $lastPlayerScore);
                array_pop($scores);
                return $this->recursiveKO($scores, $nbKOs - 1);
            }
        }
        else
        {
            return true;
        }
    }

    /**
     * Checks and eventually performs KOs.
     *
     * @return bool|array This function returns three possible types:
     * - If there is a tie among players who are getting knocked out and those who are not, an array
     * consisting of the logins of the tied players are returned.
     * - If there are no more players to KO, false is returned.
     * - Otherwise, returns true.
     */
    private function initiateKOs()
    {
        // In case someone is not in the scorelist
        $playerList = $this->playerList->getPlaying();
        foreach ($playerList as $login => $player)
        {
            if ($this->scores->get($login) === false)
            {
                $this->scores->submitScore($login, $player['NickName'], Scores::DidNotFinish);
            }
        }
        $scores = $this->scores->getSortedScores();
        Log::debug(sprintf('Standings after this round: %s', print_r($scores, true)));
        $nbKOs = $this->kosThisRound;
        return $this->recursiveKO($scores, $nbKOs);
    }

    /**
     * Skips the upcoming map(s) until the author is not present in the KO.
     */
    private function replaceNextTrackIfNeeded()
    {
        $nbSkips = 0;
        $nextChallenge = QueryManager::queryWithResponse('GetNextChallengeInfo');
        $authorIsStillIn = $this->playerList->hasStatus($nextChallenge['Author'], PlayerStatus::Playing);
        $maxSkips = $this->authorSkipLimit;

        while ($authorIsStillIn && $nbSkips < $maxSkips)
        {
            $nextAuthor = $this->playerList->get($nextChallenge['Author']);
            // 'NextChallenge' has no effect once we're in the podium, so we'll do a dirty hack
            // instead and shift the index that points to the upcoming track
            $nextChallengeIndex = QueryManager::queryWithResponse('GetNextChallengeIndex');
            QueryManager::query('SetNextChallengeIndex', $nextChallengeIndex + 1);
            $nbSkips++;
            Chat::info(sprintf(
                'Skipping %s$z$s$g as %s$z$s$g is still in the KO (%d/%d)',
                $nextChallenge['Name'],
                $nextAuthor['NickName'],
                $nbSkips,
                $maxSkips
            ));
            // Then we can grab the challenge coming after that and check the author again
            $nextChallenge = QueryManager::queryWithResponse('GetNextChallengeInfo');
            $authorIsStillIn = $this->playerList->hasStatus($nextChallenge['Author'], PlayerStatus::Playing);
        }
    }

    /**
     * Starts a tiebreaker.
     *
     * @param array $tiedPlayers Logins of tied players.
     * @param int $kosRemaining The number of KOs to be performed in the tiebreaker.
     */
    private function initiateTiebreaker($tiedPlayers, $kosRemaining)
    {
        $remainingPlayers = $this->playerList->getPlaying();
        $playersToPutToSpec = array();
        foreach ($remainingPlayers as $player)
        {
            $login = $player['Login'];
            if (!in_array($login, $tiedPlayers))
            {
                if ($player['Status'] === PlayerStatus::Playing)
                {
                    $this->playerList->setStatus($login, PlayerStatus::Shelved);
                    $playersToPutToSpec[] = $login;
                }
                elseif ($player['Status'] === PlayerStatus::PlayingAndDisconnected)
                {
                    $this->playerList->setStatus($login, PlayerStatus::ShelvedAndDisconnected);
                    $playersToPutToSpec[] = $login;
                }
                else
                {
                    Log::warning(sprintf(
                        'Expected player to have player status Playing or PlayingAndDisconnected but is %s',
                        getNameOfConstant($player['Status'], 'PlayerStatus')
                    ));
                }
            }
        }
        forceSpec($playersToPutToSpec, true);
        $nickNames = array();
        foreach ($tiedPlayers as $login)
        {
            $player = $this->playerList->get($login);
            if (!is_null($player))
            {
                $nickNames[] = sprintf('%s$z$s$g', $player['NickName']);
            }
        }
        Chat::info(sprintf('%s have tied for last place - initiating tiebreaker...', implode(', ', $nickNames)));

        // We might already be in a tiebreaker; in that case, don't overwrite prev KO multiplier
        if ($this->koMultiplier->getMode() === KOMultiplier::Tiebreaker) $this->koMultiplier->revert();
        $this->koMultiplier->set(KOMultiplier::Tiebreaker, $kosRemaining);
        $this->koStatus = KnockoutStatus::Tiebreaker;
        $this->updateKoCount();
        $this->updateStatusBar();
    }

    /**
     * Restores settings when ending a tiebreaker.
     */
    private function returnFromTiebreaker()
    {
        $remainingPlayers = $this->playerList->getPlayingOrShelved();
        $this->playerList->applyStatusTransition(PlayerStatus::Shelved, PlayerStatus::Playing);
        $this->playerList->applyStatusTransition(PlayerStatus::ShelvedAndDisconnected, PlayerStatus::PlayingAndDisconnected);
        $this->koMultiplier->revert();
        forcePlay(logins($remainingPlayers), true);
        $this->koStatus = KnockoutStatus::Running;
    }

    /**
     * Returns the player with the most points in this match.
     */
    private function getLeadingPlayer()
    {
        $scores = QueryManager::queryWithResponse('GetCurrentRanking', 255, 0);
        foreach ($scores as $score)
        {
            if ($score['Rank'] === 1)
            {
                return $score;
            }
        }
        return null;
    }

    /**
     * Skips the warmup phase. Assumes there is a warmup currently running.
     */
    private function skipWarmup()
    {
        switch ($this->gameMode)
        {
            case GameMode::Team:
            case GameMode::Stunts:
            case GameMode::TimeAttack:
                // Todo: ensure game mode settings are not changed
                QueryManager::query('RestartChallenge');
                break;

            case GameMode::Cup:
                QueryManager::query('RestartChallenge', true);
                break;

            case GameMode::Laps:
            case GameMode::Rounds:
                // Todo: ensure game mode settings are not changed
                QueryManager::query('RestartChallenge');
                break;
        }
    }

    /**
     * Restarts the current track.
     */
    private function restartRound()
    {
        // If we're in Rounds, we gotta make sure that we can restart the round. The default
        // behaviour is:
        // - 'RestartChallenge':
        //   - If round 0, restarts round
        //   - If round 1+, restarts challenge from round 0
        //   - If game mode settings have been changed, restarts challenge with warmups if any
        // - 'ForceEndRound':
        //   - If no one have scored: restarts round
        //   - If someone have finished: completes the round and starts the next one
        //
        // In addition, If the rounds point limit won't be reached, the end result of
        // 'RestartChallenge' is unpredictable by the time we get here (most likely won't be able to
        // issue it due to error -1000). The current workaround is to modify the points limit to
        // accomodate the change:
        // - If it is the last round, restart the challenge with one round left
        // - Otherwise, force end of the round while incrementing the points limit
        switch ($this->gameMode)
        {
            case GameMode::Laps:
            case GameMode::Stunts:
            case GameMode::TimeAttack:
                // Todo: ensure game mode settings are not changed
                QueryManager::query('RestartChallenge');
                break;

            case GameMode::Cup:
                QueryManager::query('RestartChallenge', true);
                break;

            case GameMode::Rounds:
            case GameMode::Team:
                // Get scores of the current round
                $scores = $this->scores->getSortedScores();
                if (isset($scores[0]) && $scores[0]['Score'] > 0)
                {
                    // Someone have finished; need to adjust points limit
                    $pointsLimit = QueryManager::queryWithResponse('GetRoundPointsLimit');
                    // Get points of the match
                    $leader = $this->getLeadingPlayer();
                    $isLastRound = is_null($leader) ? false : $pointsLimit['CurrentValue'] <= $leader['Score'] + 1;
                    if ($isLastRound)
                    {
                        Log::debug('setting points limit to 1');
                        QueryManager::query('SetRoundPointsLimit', 1);
                        QueryManager::query('RestartChallenge');
                    }
                    else
                    {
                        Log::debug(sprintf('setting points limit to %d', $pointsLimit['CurrentValue'] + 1));
                        QueryManager::query('SetRoundPointsLimit', $pointsLimit['CurrentValue'] + 1);
                        QueryManager::query('ForceEndRound');
                    }
                }
                else
                {
                    QueryManager::query('ForceEndRound');
                }
                break;
        }
    }

    /**
     * Restarts the current track with warmups if any.
     */
    private function restartTrack()
    {
        // Changing some setting that takes effect on next challenge (like setting a new game mode)
        // makes RestartChallenge restart the whole challenge, including warmup
        $chattime = QueryManager::queryWithResponse('GetChatTime');
        QueryManager::query('SetChatTime', 0);
        QueryManager::query('SetGameMode', GameMode::Team);
        QueryManager::query('SetGameMode', $this->gameMode);
        QueryManager::query('RestartChallenge');
        QueryManager::query('SetChatTime', $chattime['NextValue']);
    }

    /**
     * Adjusts the points partition and the round points limit.
     */
    private function adjustPoints()
    {
        $playerCount = count($this->playerList->getPlayingOrShelved());
        $nbKOs = $this->kosThisRound;
        // Changing game settings has immediate effect as long as you change them by the start of
        // the round
        if ($playerCount - $nbKOs <= 1 && $this->gameMode === GameMode::Rounds)
        {
            // Adjust the points limit such that this is the last round
            $leader = $this->getLeadingPlayer();
            $maxScore = is_null($leader) ? 0 : $leader['Score'];
            Log::debug(sprintf('setting points limit to %d', $maxScore + 1));
            QueryManager::query('SetRoundPointsLimit', $maxScore + 1);
        }
        $numberOfSurvivors = $playerCount <= 1 ? 1 : $playerCount - $nbKOs;
        $scoresPartition = array_merge(array_fill(0, $numberOfSurvivors, 1), array(0));
        QueryManager::query('SetRoundCustomPoints', $scoresPartition);
    }

    /**
     * Callback method for when the server changes its status.
     *
     * Throughout a challenge, the server goes through a lifecycle represented
     * through the following statuses:
     *
     * - 1: Waiting
     * - 2: Launching
     * - 3: Running - Synchronization
     * - 4: Running - Play
     * - 5: Running - Finish
     *
     * @param array $args An array passed by the server.
     *
     *     $args = [
     *         [0] => (int) The status code.
     *         [1] => (string) The corresponding status name.
     *     ]
     */
    public function onStatusChange($args)
    {
        Log::debug(sprintf('onStatusChange %s', implode(' ', $args)));

        switch ($args[0])
        {
            case ServerStatus::Launching:
                $this->isPodium = false;
                $this->onTrackChange();
                $this->gameMode = QueryManager::queryWithResponse('GetGameMode');
                $this->reflectScoringWithGameMode();
                break;

            case ServerStatus::Synchronization:
                $this->isPodium = false;
                $this->isWarmup = QueryManager::queryWithResponse('GetWarmUp');
                if ($this->koStatus !== KnockoutStatus::Idle && $this->koStatus !== KnockoutStatus::Tiebreaker)
                {
                    $this->koStatus = $this->isWarmup ? KnockoutStatus::Warmup : KnockoutStatus::Running;
                    $this->roundNumber++;
                }
                $this->onBeginSynchronization();
                break;

            case ServerStatus::Play:
                if ($this->koStatus === KnockoutStatus::Running || $this->koStatus === KnockoutStatus::Tiebreaker)
                {
                    if ($this->gameMode !== GameMode::Stunts && $this->gameMode !== GameMode::TimeAttack)
                    {
                        $this->shouldCheckForFalseStarts = true;
                        $this->roundStartTime = microtime(true);
                    }
                }
                break;

            case ServerStatus::Finish:
                break;
        }
    }

    /**
     * Callback method for when a race starts.
     *
     * This method is called when a track is loaded.
     *
     * @param array $args An array passed by the server.
     *
     *     $args = [
     *         [0] => (SChallengeInfo) The challenge being played.
     *     ]
     */
    public function onBeginRace($args)
    {
        Log::debug(sprintf('onBeginRace %s', implode(' ', $args[0])));
        // Note: round number and warmup status is not reflected at this point
    }

    /**
     * "Callback method" for when the synchronization phase (before each round) starts.
     */
    public function onBeginSynchronization()
    {
        Log::debug('onBeginSynchronization');

        if ($this->koStatus === KnockoutStatus::Idle)
        {
            UI::restoreDefaultScoreboard();
        }
        else
        {
            if ($this->koStatus !== KnockoutStatus::Tiebreaker)
            {
                if ($this->isWarmup)
                {
                    if ($this->openWarmup) $this->letKnockedOutPlayersPlay();
                }
                else
                {
                    $optingPlayers = logins($this->playerList->filterByStatus(PlayerStatus::OptingOut));
                    foreach ($optingPlayers as $login)
                    {
                        if (isOnServer($login))
                        {
                            $this->playerList->setStatus($login, PlayerStatus::KnockedOutAndSpectating);
                            $this->playerList->setLives($login, 0);
                        }
                        else
                        {
                            $this->playerList->remove($login);
                        }
                    }
                }
            }
            $this->announceRoundInChat();
            $this->updateKoCount();
            $this->updateStatusBar();
        }
    }

    /**
     * Callback method for when a round starts, after the synchronization phase.
     */
    public function onBeginRound()
    {
        Log::debug('onBeginRound');

        if ($this->koStatus !== KnockoutStatus::Idle)
        {
            if ($this->koStatus === KnockoutStatus::StartingNow)
            {
                QueryManager::query('NextChallenge');
            }
            else
            {
                UI::hideScoreboard();
                $this->scores->reset();
                if ($this->koStatus === KnockoutStatus::RestartingRound)
                {
                    $this->restartRound();
                }
                elseif ($this->koStatus === KnockoutStatus::RestartingTrack)
                {
                    $this->restartTrack();
                }
                elseif ($this->koStatus === KnockoutStatus::SkippingWarmup)
                {
                    QueryManager::query('ForceEndRound');
                }
                elseif ($this->koStatus === KnockoutStatus::SkippingTrack)
                {
                    QueryManager::query('NextChallenge');
                }
            }
        }
    }

    /**
     * Returns true if an unregistered player is eligible to join the KO.
     *
     * Cases where a player can join a KO while it's running include:
     * - The knockout is about to start, but has not started yet
     * - The knockout is in its first warmup
     * - The knockout is in its first round (for Time Attack and Stunts)
     *
     * @return bool True if the player is eligible to be forced to play.
     */
    private function isEligibleToJoin() {
        if ($this->koStatus === KnockoutStatus::Starting || $this->koStatus === KnockoutStatus::StartingNow)
        {
            return true;
        }
        elseif ($this->isWarmup && $this->roundNumber <= 1)
        {
            return true;
        }
        elseif (($this->gameMode === GameMode::TimeAttack || $this->gameMode === GameMode::Stunts) && $this->roundNumber <= 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Callback method for when a player joins the server.
     *
     * @param array $args An array passed by the server.
     *
     *     $args = [
     *         [0] => (string) The login of the player.
     *         [1] => (bool)   True if the player joins as a spectator.
     *     ]
     */
    public function onPlayerConnect($args)
    {
        Log::debug(sprintf('onPlayerConnect %s', implode(' ', $args)));
        if ($this->koStatus === KnockoutStatus::Idle) return;

        $login = $args[0];
        $joinsAsSpectator = $args[1];
        $playerInfo = QueryManager::queryWithResponse('GetPlayerInfo', $login);
        // Only disconnected players who are eligible to rejoin should be matched here; see
        // onPlayerDisconnect
        if ($this->playerList->exists($login))
        {
            $player = $this->playerList->get($login);
            $this->playerList->setNickname($login, $playerInfo['NickName']);
            switch ($player['Status'])
            {
                case PlayerStatus::PlayingAndDisconnected:
                    $this->playerList->setStatus($login, PlayerStatus::Playing);
                    forcePlay(array($login), true);
                    break;

                case PlayerStatus::ShelvedAndDisconnected:
                    $this->playerList->setStatus($login, PlayerStatus::Shelved);
                    forceSpec(array($login), true);
                    break;

                case PlayerStatus::OptingOut:
                    forceSpec(array($login), true);
                    break;

                default:
                    Log::warning(sprintf('Player connected with status %s', getNameOfConstant($player['Status'], 'PlayerStatus')));
                    forceSpec(array($login), true);
                    break;
            }
        }
        elseif ($this->isEligibleToJoin())
        {
            $this->playerList->add($playerInfo['Login'], $playerInfo['NickName'], PlayerStatus::Playing, $this->lives);
            forcePlay(array($login), true);
            $this->updateKoCount();
        }
        else
        {
            $this->playerList->add(
                $playerInfo['Login'],
                $playerInfo['NickName'],
                $joinsAsSpectator ? PlayerStatus::KnockedOutAndSpectating : PlayerStatus::KnockedOut,
                0
            );
            if ($this->openWarmup && ($this->isWarmup || $this->isPodium) && !$joinsAsSpectator)
            {
                forcePlay(array($login), false);
            }
            else
            {
                forceSpec(array($login), true);
            }
        }
        if (KnockoutStatus::isInProgress($this->koStatus) && $this->roundNumber > 0)
        {
            $this->updateStatusBar($login);
            $this->announceRoundInChat($login);
            Chat::info('You have entered a match in progress!', array($login));
        }
    }

    /**
     * Callback method for when a player leaves the server.
     *
     * @param array $args An array passed by the server.
     *
     *     $args = [
     *         [0] => (string) The login of the player.
     *     ]
     */
    public function onPlayerDisconnect($args)
    {
        Log::debug(sprintf('onPlayerDisconnect %s', implode(' ', $args)));
        if ($this->koStatus === KnockoutStatus::Idle) return;

        $login = $args[0];
        $player = $this->playerList->get($login);
        switch ($player['Status'])
        {
            case PlayerStatus::Playing:
                $this->playerList->setStatus($login, PlayerStatus::PlayingAndDisconnected);
                break;

            case PlayerStatus::Shelved:
                $this->playerList->setStatus($login, PlayerStatus::ShelvedAndDisconnected);
                break;

            case PlayerStatus::KnockedOut:
            case PlayerStatus::KnockedOutAndSpectating:
                $this->playerList->remove($login);
                break;

            case PlayerStatus::OptingOut:
                // Do nothing
                break;

            default:
                Log::warning(sprintf('Player disconnected with status %s', getNameOfConstant($player['Status'], 'PlayerStatus')));
                break;
        }
    }

    /**
     * Callback method for when someone passes a checkpoint.
     *
     * @param array $args An array passed by the server.
     *
     *     $args = [
     *         [0] => (int)    The UID of the player.
     *         [1] => (string) The login of the player.
     *         [2] => (int)    The time (in milliseconds) or score (in
     *                         points) performed by the player.
     *         [3] => (int)    The current lap number.
     *         [4] => (int)    The current checkpoint index.
     *     ]
     */
    public function onPlayerCheckpoint($args)
    {
        Log::debug(sprintf('onPlayerCheckpoint %s', implode(' ', $args)));
    }

    /**
     * Callback method for when someone retires or finishes a round.
     *
     * Ends the current round if a player retires before the countdown in Rounds.
     *
     * @param array $args An array passed by the server.
     *
     *     $args = [
     *         [0] => (int)    The UID of the player.
     *         [1] => (string) The login of the player.
     *         [2] => (int)    The time (in milliseconds) or score (in
     *                         points) performed by the player. If the player has
     *                         retired without finishing, a value of 0 is given.
     *     ]
     */
    public function onPlayerFinish($args)
    {
        Log::debug(sprintf('onPlayerFinish %s', implode(' ', $args)));

        $login = $args[1];
        $timeOrScore = $args[2];

        switch ($this->koStatus)
        {
            case KnockoutStatus::Idle:
                return;

            case KnockoutStatus::Running:
            case KnockoutStatus::Tiebreaker:
                // Check if it's the first player to retire and whether a false start
                // can be considered
                if ($this->shouldCheckForFalseStarts
                    && $timeOrScore === 0
                    && $this->falseStartCount < $this->maxFalseStarts)
                {
                    // Must be within 1 second of the start of the round
                    $currentTime = microtime(true);
                    if ($currentTime - $this->roundStartTime <= 1.)
                    {
                        // Must be a player in the KO who retires
                        if ($this->playerList->hasStatus($login, PlayerStatus::Playing))
                        {
                            $this->koStatus = KnockoutStatus::RestartingRound;
                            $this->falseStartCount++;
                            QueryManager::query('ForceEndRound');
                            Chat::info(sprintf(
                                'False start! Restarting the round... (%d/%d)',
                                $this->falseStartCount,
                                $this->maxFalseStarts
                            ));
                            return;
                        }
                    }
                    else
                    {
                        $this->shouldCheckForFalseStarts = false;
                    }
                }
                else
                {
                    $this->shouldCheckForFalseStarts = false;
                }

                if ($this->playerList->hasStatus($login, PlayerStatus::Playing))
                {
                    $playerObj = $this->playerList->get($login);
                    if ($timeOrScore === 0)
                    {
                        switch ($this->gameMode)
                        {
                            case GameMode::Stunts:
                            case GameMode::TimeAttack:
                                $timeOrScore = Scores::HasNotFinishedYet;
                                break;

                            case GameMode::Cup:
                            case GameMode::Team:
                            case GameMode::Laps:
                            case GameMode::Rounds:
                                $timeOrScore = Scores::DidNotFinish;
                                break;
                        }
                    }
                    $this->scores->submitScore($login, $playerObj['NickName'], $timeOrScore);
                    $this->updateScoreboard();
                }
                break;
        }
    }

    /**
     * Callback method for when a player's info changes.
     *
     * This function is called when a player's info changes, such as spectator status.
     *
     * @param array $params An array passed by the server.
     *
     *     $args = [
     *         [0] => (SPlayerInfo) The updated player object.
     *     ]
     */
    public function onPlayerInfoChange($args)
    {
        Log::debug(sprintf('onPlayerInfoChange %s', implode(' ', $args[0])));

        if (!KnockoutStatus::isInProgress($this->koStatus)) return;

        $login = $args[0]['Login'];
        if ($this->playerList->exists($login))
        {
            $player = $this->playerList->get($login);
            switch ($player['Status'])
            {
                case PlayerStatus::KnockedOut:
                    if (!isForced($args[0]) && isSpectator($args[0]))
                    {
                        $this->playerList->setStatus($login, PlayerStatus::KnockedOutAndSpectating);
                        $this->updateStatusBar($login);
                    }
                    break;

                case PlayerStatus::KnockedOutAndSpectating:
                    if (!isForced($args[0]) && !isSpectator($args[0]))
                    {
                        $this->playerList->setStatus($login, PlayerStatus::KnockedOut);
                        $this->updateStatusBar($login);
                    }
                    break;

                case PlayerStatus::Shelved:
                    break;

                default:
                    if (isSpectator($args[0]))
                    {
                        Log::warning(sprintf(
                            'Player %s with status %s is in spectator mode',
                            $login,
                            getNameOfConstant($player['Status'], 'PlayerStatus')
                        ));
                    }
            }
        }
    }

    /**
     * Callback method for when a round ends.
     */
    public function onEndRound()
    {
        Log::debug(sprintf('onEndRound %d', (int) $this->isWarmup));

        switch ($this->koStatus)
        {
            case KnockoutStatus::Idle:
            case KnockoutStatus::Stopping:
                return;

            case KnockoutStatus::RestartingRound:
            case KnockoutStatus::RestartingTrack:
            case KnockoutStatus::SkippingTrack:
                $this->roundNumber--;
                return;

            case KnockoutStatus::Starting:
            case KnockoutStatus::StartingNow:
                Chat::announce('Knockout starting!');
                $this->hudReminder();
                $this->koStatus = KnockoutStatus::Running;
                $this->adjustPoints(); // SetRoundPointsLimit has to be set before onBeginRound
                return;

            case KnockoutStatus::Warmup:
            case KnockoutStatus::SkippingWarmup:
                $this->roundNumber--;
                $this->adjustPoints(); // SetRoundPointsLimit has to be set before onBeginRound
                return;

            case KnockoutStatus::Running:
            case KnockoutStatus::Tiebreaker:
                $scores = $this->scores->getSortedScores();
                $noOneFinished = true;
                foreach ($scores as $score)
                {
                    print_r($score);
                    if ($score['Score'] > 0)
                    {
                        $noOneFinished = false;
                        break;
                    }
                }
                $playersInTheKO = $this->playerList->getPlayingOrShelved();

                if ($noOneFinished)
                {
                    // Do nothing; round will restart (Rounds) or proceed to next map (Time Attack)
                    $this->roundNumber--;
                    Chat::info('No one finished - proceeding to next round');
                }
                elseif (count($playersInTheKO) < 1)
                {
                    Chat::info('Everyone seems to have been knocked out!');
                    $this->stop();
                }
                elseif (count($playersInTheKO) === 1)
                {
                    $winner = array_pop($playersInTheKO);
                    Chat::info(sprintf('%s$z$s$g is the Champ!', $winner['NickName']));
                    $this->stop();
                }
                else
                {
                    $result = $this->initiateKOs();
                    if (is_array($result))
                    {
                        Log::debug('initateKO returned ' . print_r($result, true));
                        if ($this->tiebreaker)
                        {
                            $this->initiateTiebreaker($result['TiedPlayers'], $result['KOsRemaining']);
                            $this->restartRound();
                        }
                        else
                        {
                            // Knock out last submitted times
                            $nbKOs = $result['KOsRemaining'];
                            while (count($result) > 0 && $nbKOs > 0)
                            {
                                $login = array_pop($result);
                                $this->ko($login, $this->scores->get($login));
                                $nbKOs--;
                            }
                        }
                    }
                    else
                    {
                        $remainingPlayers = $this->playerList->getPlayingOrShelved();
                        if ($result === false)
                        {
                            Chat::info('Everyone seems to have been knocked out!');
                            $this->stop();
                        }
                        elseif (count($remainingPlayers) === 1)
                        {
                            $winner = array_pop($remainingPlayers);
                            Chat::info(sprintf('%s$z$s$g is the Champ!', $winner['NickName']));
                            $this->stop();
                        }
                        else
                        {
                            // If doing tiebreakers, return to normal
                            if ($this->koStatus === KnockoutStatus::Tiebreaker)
                            {
                                $this->returnFromTiebreaker();
                            }
                            $this->updateKoCount();
                            $this->updateStatusBar();
                            $this->adjustPoints(); // SetRoundPointsLimit has to be set before onBeginRound
                        }
                    }
                }
                return;
        }
    }

    /**
     * Callback method for when a race ends.
     *
     * This function is called
     *
     * - when the warump ends
     * - when restarting the match
     * - when skipping to the next track
     * - when normally proceeding to the next track
     *
     * @param array $args An array passed by the server.
     *
     *     $args = [
     *         [0] => (SPlayerRanking[]) The final rankings.
     *         [1] => (SChallengeInfo) The current challenge.
     *     ]
     */
    public function onEndRace($args)
    {
        if (!$this->isWarmup || $this->koStatus === KnockoutStatus::Running || $this->koStatus === KnockoutStatus::SkippingTrack)
        {
            $this->isPodium = true;
        }
        Log::debug(sprintf('onEndRace %d %d', (int) $this->isWarmup, (int) $this->isPodium));

        switch ($this->koStatus)
        {
            case KnockoutStatus::Warmup:
                $this->putKnockedOutPlayersIntoSpec();
                UI::hideScoreboard();
                break;

            case KnockoutStatus::Running:
                if ($this->isPodium)
                {
                    $this->scores->reset();
                    UI::hideScoreboard();
                    $playerCount = count($this->playerList->getAll());
                    if ($playerCount <= $this->authorSkip)
                    {
                        $this->replaceNextTrackIfNeeded();
                    }
                    Log::debug(sprintf('setting points limit to %d', $this->maxRounds));
                    QueryManager::query('SetRoundPointsLimit', $this->maxRounds);
                }
                break;

            case KnockoutStatus::RestartingRound:
            case KnockoutStatus::RestartingTrack:
                $this->scores->reset();
                UI::hideScoreboard();
                break;

            case KnockoutStatus::SkippingWarmup:
                break;

            case KnockoutStatus::SkippingTrack:
                $this->scores->reset();
                UI::hideScoreboard();
                if ($this->isPodium)
                {
                    Log::debug(sprintf('setting points limit to %d', $this->maxRounds));
                    QueryManager::query('SetRoundPointsLimit', $this->maxRounds);
                }
                break;

            case KnockoutStatus::Stopping:
                // After KO stop
                Log::debug(sprintf('setting points limit to %d', $this->defaultPointsLimit));
                QueryManager::query('SetRoundPointsLimit', $this->defaultPointsLimit);
                $this->koStatus = KnockoutStatus::Idle;
                break;
        }
    }

    private function printSettings()
    {
        $printBool = function($bool)
        {
            return $bool ? 'on' : 'off';
        };
        $settings = array(
            sprintf('KO mode: $fff%s$g', getNameOfConstant($this->koMode, 'KnockoutMode')),
            sprintf('KO multiplier: $fff%s$g', $this->koMultiplier->toString()),
            sprintf('Lives: $fff%d$g', $this->lives),
            sprintf('Open warmup: $fff%s$g', $printBool($this->openWarmup)),
            sprintf('Tiebreakers: $fff%s$g', $printBool($this->tiebreaker)),
            sprintf('False starts: $fff%s$g', ($this->maxFalseStarts === 0 ? 'off' : var_export($this->maxFalseStarts, true))),
            sprintf('Author skip: $fff%s$g', ($this->authorSkip < 2 ? 'off' : 'for top ' . var_export($this->authorSkip, true)))
        );
        $mode = QueryManager::queryWithResponse('GetNextGameInfo');
        if ($mode['GameMode'] === GameMode::Rounds)
        {
            array_splice($settings, 3, 0, array(
                sprintf('Rounds per track: $fff%d$g', $this->maxRounds)
            ));
        }
        return implode(' | ', $settings);
    }

    private function onKoStatusUpdate()
    {
        switch ($this->koStatus)
        {
            case KnockoutStatus::Idle:
            case KnockoutStatus::Starting:
            case KnockoutStatus::StartingNow:
            case KnockoutStatus::SkippingWarmup:
                // Do nothing
                break;

            case KnockoutStatus::Warmup:
            case KnockoutStatus::RestartingRound:
            case KnockoutStatus::RestartingTrack:
            case KnockoutStatus::SkippingTrack:
                if ($this->roundNumber >= 1)
                {
                    $this->updateKoCount();
                    $this->updateStatusBar();
                }
                break;

            case KnockoutStatus::Running:
            case KnockoutStatus::Tiebreaker:
                if ($this->roundNumber >= 1)
                {
                    $this->updateKoCount();
                    $this->updateStatusBar();
                    if (count($this->scores->getSortedScores()) > 0)
                    {
                        $this->updateScoreboard();
                    }
                }
                break;
        }
    }

    /**
     * CLI for interacting with the knockout system.
     *
     * This function is called when a user sends a chat message starting with '/ko'.
     *
     * @param array $args Arguments to the command.
     * @param array $issuer A single-element array.
     *
     *     $issuer = [
     *         [0] => (string) The login of the player who issued the command.
     *         [1] => (string) The nickname of the player who issued the command.
     *     ]
     */
    public function adminChatCommands($args, $issuer)
    {
        Log::debug(sprintf('adminChatCommands %s %s', implode(' ', $args), implode(' ', $issuer)));
        // Note: $args may not be split properly
        //   Input string: /ko multi constant 2
        //   $args:
        //     [0] => 'multi'
        //     [1] => 'constant 2'
        if (isset($args[1]))
        {
            $subArgs = explode(' ', $args[1]);
            array_pop($args);
            foreach ($subArgs as $arg)
            {
                $args[] = $arg;
            }
        }

        $issuerLogin = $issuer[0];
        $onError = function($msg) use($issuerLogin)
        {
            Chat::error($msg, array($issuerLogin));
        };

        if (!isadmin($issuerLogin) && !isadmin2($issuerLogin))
        {
            $onError('Access denied: you do not have the required privileges to use this command');
        }
        elseif (count($args) === 0)
        {
            $onError('Syntax error: expected an argument (see $fff/ko help$g for usages)');
        }
        else
        {
            switch (strtolower($args[0]))
            {
                // /ko start [now]
                case 'start':
                    if ($this->koStatus !== KnockoutStatus::Idle)
                    {
                        $onError('There is already a knockout in progress');
                        return;
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments ($fff%s$g, expected $fff/ko start$g or $fff/ko start now$g)');
                    }
                    elseif (is_null($args[1]) || strtolower($args[1]) === 'now')
                    {
                        // Todo: look up on next round's infos with GetNextGameInfo
                        $mode = QueryManager::queryWithResponse('GetGameMode');
                        if ($mode === GameMode::Team)
                        {
                            $onError('Knockout does not work in Team mode');
                            return;
                        }
                        elseif ($mode === GameMode::Cup)
                        {
                            $onError('Knockout does not work in Cup mode');
                            return;
                        }

                        $players = QueryManager::queryWithResponse('GetPlayerList', 255, 0, 1);
                        // only 1 player? WTF!?! MrA demands moarrr
                        // if (count($players) <= 1) {
                        //     $onError('Knockout requires multiple players');
                        //     return;
                        // }

                        $this->start($players, $args[1] === 'now');
                        Chat::info2('Knockout starting with the following settings:', array($issuerLogin));
                        Chat::info2($this->printSettings(), array($issuerLogin));
                    }
                    else
                    {
                        $onError(sprintf('Syntax error: unexpected argument $fff%s$g (expected $fff/ko start$g or $fff/ko start now$g)', $args[1]));
                    }
                    break;

                // /ko stop
                case 'stop':
                    if (isset($args[1]))
                    {
                        $onError('Syntax error: too many arguments ($fff%s$g, expected $fff/ko stop$g)');
                    }
                    elseif ($this->koStatus !== KnockoutStatus::Idle)
                    {
                        $this->stop();
                        Log::debug(sprintf('setting points limit to %d', $this->defaultPointsLimit));
                        QueryManager::query('SetRoundPointsLimit', $this->defaultPointsLimit);
                        UI::restoreDefaultScoreboard();
                        $this->koStatus = KnockoutStatus::Idle;
                        Chat::info('Knockout has been stopped');
                    }
                    else
                    {
                        $onError('The knockout must be running before this command can be used');
                    }
                    break;

                // /ko skip [warmup]
                case 'skip':
                    if ($this->koStatus === KnockoutStatus::Idle)
                    {
                        $onError('The knockout must be running before this command can be used');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko skip$g or $fff/ko skip warmup$g)');
                    }
                    elseif (is_null($args[1]))
                    {
                        if ($this->koStatus === KnockoutStatus::Tiebreaker)
                        {
                            $this->returnFromTiebreaker();
                        }
                        if ($this->koStatus !== KnockoutStatus::Starting && $this->koStatus !== KnockoutStatus::StartingNow)
                        {
                            $this->koStatus = KnockoutStatus::SkippingTrack;
                            $this->updateStatusBar();
                        }
                        QueryManager::query('NextChallenge');
                        Chat::info('Skipping the current track');
                    }
                    elseif (strtolower($args[1]) === 'warmup')
                    {
                        if ($this->isWarmup)
                        {
                            if ($this->koStatus !== KnockoutStatus::Starting && $this->koStatus !== KnockoutStatus::StartingNow)
                            {
                                $this->koStatus = KnockoutStatus::SkippingWarmup;
                                $this->updateStatusBar();
                            }
                            $this->skipWarmup();
                            Chat::info('Skipping the warmup');
                        }
                        else
                        {
                            $onError('There is currently no warmup to skip');
                        }
                    }
                    else
                    {
                        $onError(sprintf('Unexpected argument $fff%s$g (expected $fff/ko skip$g or $fff/ko skip warmup$g)', $args[1]));
                    }
                    break;

                // /ko restart [warmup]
                case 'restart':
                    if ($this->koStatus === KnockoutStatus::Idle)
                    {
                        $onError('The knockout must be running before this command can be used');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko restart$g or $fff/ko restart warmup$g)');
                    }
                    elseif (is_null($args[1]))
                    {
                        if ($this->koStatus !== KnockoutStatus::Starting && $this->koStatus !== KnockoutStatus::StartingNow)
                        {
                            if ($this->koStatus === KnockoutStatus::Tiebreaker)
                            {
                                $this->scores->reset();
                            }
                            else
                            {
                                $this->koStatus = KnockoutStatus::RestartingRound;
                                $this->updateStatusBar();
                            }
                        }
                        $this->restartRound();
                        Chat::info('Restarting the current round');
                    }
                    elseif (strtolower($args[1]) === 'warmup')
                    {
                        if ($this->koStatus !== KnockoutStatus::Starting && $this->koStatus !== KnockoutStatus::StartingNow)
                        {
                            if ($this->koStatus === KnockoutStatus::Tiebreaker)
                            {
                                $this->returnFromTiebreaker();
                            }
                            $this->koStatus = KnockoutStatus::RestartingTrack;
                            $this->updateStatusBar();
                        }
                        $this->restartTrack();
                        Chat::info('Restarting the track with a warmup');
                    }
                    else
                    {
                        $onError(sprintf('Syntax error: unexpected argument $fff%s$g (expected $fff/ko restart$g or $fff/ko restart warmup$g)', $args[1]));
                    }
                    break;

                // /ko add (<login> | *)
                case 'add':
                    if ($this->koStatus === KnockoutStatus::Idle)
                    {
                        $onError('The knockout must be running before this command can be used');
                    }
                    elseif (is_null($args[1]))
                    {
                        $onError('Syntax error: expected an argument (usage: $fff/ko add (<login> | *)$g)');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko add (<login> | *)$g)');
                    }
                    else
                    {
                        $playersToAdd = array();
                        if ($args[1] === '*')
                        {
                            $playersToAdd = $this->playerList->getAll();
                        }
                        elseif ($this->playerList->exists($args[1]))
                        {
                            $playersToAdd = array($this->playerList->get($args[1]));
                        }
                        else
                        {
                            $onError(sprintf('Error: login $fff%s$g could not be found', $args[1]));
                            return;
                        }

                        $playersToAdd = array_filter(
                            $playersToAdd,
                            function($player) { return !PlayerStatus::isIn($player['Status']); }
                        );
                        if (count($playersToAdd) === 0)
                        {
                            if ($args[1] === '*')
                            {
                                $onError('All players are already playing');
                            }
                            else
                            {
                                $onError(sprintf('$fff%s$g is already playing', $args[1]));
                            }
                        }
                        else
                        {
                            $this->add($playersToAdd);
                            if ($args[1] === '*')
                            {
                                Chat::info('All players have been added to the KO');
                            }
                            else
                            {
                                Chat::info(sprintf('%s$z$s$g has been added to the KO', $playersToAdd[0]['NickName']));
                            }
                        }
                    }
                    break;

                // /ko remove (<login> | *)
                // /ko spec (<login> | *)
                case 'remove':
                case 'spec':
                    if ($this->koStatus === KnockoutStatus::Idle)
                    {
                        $onError('The knockout must be running before this command can be used');
                    }
                    elseif (is_null($args[1]))
                    {
                        $onError('Syntax error: expected an argument (usage: $fff/ko remove (<login> | *)$g)');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko remove (<login> | *)$g)');
                    }
                    else
                    {
                        $playersToRemove = array();
                        if ($args[1] === '*')
                        {
                            $playersToRemove = $this->playerList->getAll();
                        }
                        elseif ($this->playerList->exists($args[1]))
                        {
                            $playersToRemove = array($this->playerList->get($args[1]));
                        }
                        else
                        {
                            $onError(sprintf('Error: login $fff%s$g could not be found', $args[1]));
                            return;
                        }

                        if ($args[0] === 'remove')
                        {
                            $playersToRemove = array_filter(
                                $playersToRemove,
                                function($player) { return $player['Status'] !== PlayerStatus::KnockedOut; }
                            );
                            if (count($playersToRemove) === 0)
                            {
                                if ($args[1] === '*')
                                {
                                    $onError('All players are already knocked out');
                                }
                                else
                                {
                                    $onError(sprintf('$fff%s$g is already knocked out', $args[1]));
                                }
                            }
                            else
                            {
                                $this->remove($playersToRemove, PlayerStatus::KnockedOut);
                                if ($args[1] === '*')
                                {
                                    Chat::info('All players have been removed from the KO');
                                }
                                else
                                {
                                    Chat::info(sprintf('%s$z$s$g has been removed from the KO', $playersToRemove[0]['NickName']));
                                }
                            }
                        }
                        else
                        {
                            $playersToRemove = array_filter(
                                $playersToRemove,
                                function($player) { return $player['Status'] !== PlayerStatus::KnockedOutAndSpectating; }
                            );
                            if (count($playersToRemove) === 0)
                            {
                                if ($args[1] === '*')
                                {
                                    $onError('All players are already spectating');
                                }
                                else
                                {
                                    $onError(sprintf('$fff%s$g is already spectating', $args[1]));
                                }
                            }
                            else
                            {
                                $this->remove($playersToRemove, PlayerStatus::KnockedOutAndSpectating);
                                if ($args[1] === '*')
                                {
                                    Chat::info('All players have been put to spec');
                                }
                                else
                                {
                                    Chat::info(sprintf('%s$z$s$g has been put to spec', $playersToRemove[0]['NickName']));
                                }
                            }
                        }
                    }
                    break;

                // /ko lives (<login> | *) [[+ | -]<lives>]
                case 'lives':
                    if (is_null($args[1]))
                    {
                        $onError('Syntax error: expected an argument (usage: $fff/ko lives (<login> | *) [[+ | -]<lives>]$g)');
                    }
                    elseif (isset($args[3]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko lives (<login> | *) [[+ | -]<lives>]$g)');
                    }
                    else
                    {
                        $playersToUpdate = array();
                        if ($args[1] === '*')
                        {
                            $playersToUpdate = $this->playerList->getPlayingOrShelved();
                        }
                        elseif ($this->koStatus === KnockoutStatus::Idle)
                        {
                            $onError('The knockout must be running before this command can be used');
                            return;
                        }
                        elseif ($this->playerList->exists($args[1]))
                        {
                            $playersToUpdate = array($this->playerList->get($args[1]));
                        }
                        else
                        {
                            $onError(sprintf('Error: login $fff%s$g could not be found', $args[1]));
                            return;
                        }

                        if (is_null($args[2]))
                        {
                            // Display
                            if ($this->koStatus === KnockoutStatus::Idle)
                            {
                                $onError('The knockout must be running before this command can be used');
                            }
                            else
                            {
                                $msg = implode(', ', array_map(
                                    function ($player) { return sprintf('%s$z$s$g (%s)', $player['NickName'], $player['Lives']); },
                                    $playersToUpdate
                                ));
                                Chat::info2($msg, array($issuerLogin));
                            }
                        }
                        elseif (!is_numeric($args[2]))
                        {
                            $onError(sprintf('Error: argument $fff%s$g is not a number', $args[2]));
                        }
                        elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
                        {
                            $onError(sprintf('Error: floating point numbers ($fff%s$g) are not supported', $args[2]));
                        }
                        else
                        {
                            $sign = substr($args[2], 0, 1);
                            $value = abs((int) $args[2]);
                            $livesStr = $value === 1 ? 'life' : 'lives';
                            if ($value === 0)
                            {
                                $onError(sprintf('Error: argument $fff%d$g must be a non-zero value', $value));
                            }
                            elseif ($sign === '+' || $sign === '-')
                            {
                                // Relative
                                $this->adjustLivesRelatively($playersToUpdate, $value, $args[1] === '*');
                                $actionStr = $sign === '+' ? 'given' : 'deducted';
                                if ($args[1] === '*')
                                {
                                    if (KnockoutStatus::isInProgress($this->koStatus))
                                    {
                                        Chat::info(sprintf('All players have been %s %d %s', $actionStr, $value, $livesStr));
                                    }
                                    else
                                    {
                                        $actionStr = $sign === '+' ? 'increased' : 'decreased';
                                        Chat::info(sprintf('Lives per player has been %s by %d (is now %d)', $actionStr, $value, $this->lives));
                                    }
                                }
                                else
                                {
                                    $target = $this->playerList->get($args[1]);
                                    Chat::info(sprintf('%s$z$s$g has been %s %d %s (currently at %d)', $target['NickName'], $actionStr, $value, $livesStr, $target['Lives']));
                                }
                            }
                            else
                            {
                                // Absolute
                                $this->adjustLives($playersToUpdate, $value, $args[1] === '*');
                                if ($args[1] === '*')
                                {
                                    if (KnockoutStatus::isInProgress($this->koStatus))
                                    {
                                        Chat::info(sprintf('All players have now %d %s', $value, $livesStr));
                                    }
                                    else
                                    {
                                        Chat::info(sprintf('Lives per player has been set to %d', $value));
                                    }
                                }
                                else
                                {
                                    Chat::info(sprintf('%s$z$s$g has now %d %s', $playersToUpdate[0]['NickName'], $value, $livesStr));
                                }
                            }
                        }
                    }
                    break;

                // /ko multi (constant <kos> | extra <per_x_players> | dynamic <total_rounds> | none)
                case 'multi':
                    if ($this->koStatus === KnockoutStatus::Tiebreaker)
                    {
                        $onError('Error: not allowed to change multi KO value during tiebreaker');
                    }
                    else
                    {
                        switch ($args[1])
                        {
                            case 'none':
                                if (isset($args[2]))
                                {
                                    $onError('Syntax error: too many arguments (usage: $fff/ko multi none$g)');
                                }
                                else
                                {
                                    $this->koMultiplier->set(KOMultiplier::None, null);
                                    Chat::info(sprintf('KO multiplier set to $fff%s$g', $this->koMultiplier->toString()));
                                    $this->onKoStatusUpdate();
                                }
                                break;

                            case 'constant':
                                if (is_null($args[2]))
                                {
                                    $onError('Syntax error: expected an argument (usage: $fff/ko multi constant <x KOs per round>$g)');
                                }
                                elseif (isset($args[3]))
                                {
                                    $onError('Syntax error: too many arguments (usage: $fff/ko multi constant <x KOs per round>$g)');
                                }
                                elseif (!is_numeric($args[2]))
                                {
                                    $onError(sprintf('Syntax error: argument $fff%s$g must be a number (usage: $fff/ko multi constant <x KOs per round>$g)', $args[1]));
                                }
                                elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
                                {
                                    $onError(sprintf('Error: floating point numbers ($fff%s$g) are not supported', $args[2]));
                                }
                                else
                                {
                                    $val = (int) $args[2];
                                    if ($val <= 0)
                                    {
                                        $onError(sprintf('Syntax error: argument $fff%d$g must be greater than 0 (usage: $fff/ko multi constant <x KOs per round>$g)', $val));
                                    }
                                    else
                                    {
                                        $this->koMultiplier->set(KOMultiplier::Constant, $val);
                                        Chat::info(sprintf('KO multiplier set to $fff%s$g', $this->koMultiplier->toString()));
                                        $this->onKoStatusUpdate();
                                    }
                                }
                                break;

                            case 'extra':
                                if (is_null($args[2]))
                                {
                                    $onError('Syntax error: expected an argument (usage: $fff/ko multi extra <per X players>$g)');
                                }
                                elseif (isset($args[3]))
                                {
                                    $onError('Syntax error: too many arguments (usage: $fff/ko multi extra <per X players>$g)');
                                }
                                elseif (!is_numeric($args[2]))
                                {
                                    $onError(sprintf('Syntax error: argument $fff%s$g must be a number (usage: $fff/ko multi extra <per x players>$g)', $args[1]));
                                }
                                elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
                                {
                                    $onError(sprintf('Error: floating point numbers ($fff%s$g) are not supported', $args[2]));
                                }
                                else
                                {
                                    $val = (int) $args[2];
                                    if ($val <= 0)
                                    {
                                        $onError(sprintf('Syntax error: argument $fff%d$g must be greater than 0 (usage: $fff/ko multi extra <per x players>$g)', $val));
                                    }
                                    else
                                    {
                                        $this->koMultiplier->set(KOMultiplier::Extra, $val);
                                        Chat::info(sprintf('KO multiplier set to $fff%s$g', $this->koMultiplier->toString()));
                                        $this->onKoStatusUpdate();
                                    }
                                }
                                break;

                            case 'dynamic':
                                if (is_null($args[2]))
                                {
                                    $onError('Syntax error: expected an argument (usage: $fff/ko multi dynamic <X rounds>$g)');
                                }
                                elseif (isset($args[3]))
                                {
                                    $onError('Syntax error: too many arguments (usage: $fff/ko multi dynamic <X rounds>$g)');
                                }
                                elseif (!is_numeric($args[2]))
                                {
                                    $onError(sprintf('Syntax error: argument $fff%s$g must be a number (usage: $fff/ko multi dynamic <x rounds>$g)', $args[1]));
                                }
                                elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
                                {
                                    $onError(sprintf('Error: floating point numbers ($fff%s$g) are not supported', $args[2]));
                                }
                                else
                                {
                                    $val = (int) $args[2];
                                    if ($val <= 0)
                                    {
                                        $onError(sprintf('Syntax error: argument $fff%d$g must be greater than 0 (usage: $fff/ko multi dynamic <x rounds>$g)', $val));
                                    }
                                    else
                                    {
                                        $this->koMultiplier->set(KOMultiplier::Dynamic, $val);
                                        Chat::info(sprintf('KO multiplier set to $fff%s$g', $this->koMultiplier->toString()));
                                        $this->onKoStatusUpdate();
                                    }
                                }
                                break;

                            default:
                                if (!is_null($args[1]))
                                {
                                    $onError(sprintf('Syntax error: unexpected argument $fff%s$g (expected $fffconstant$g, $fffextra$g or $fffnone$g)', $args[1]));
                                }
                                else
                                {
                                    $onError('Syntax error: expected an argument (usage: $fff/ko multi (constant <x KOs per round> | extra <per x players> | dynamic <x rounds> | none)$g)');
                                }
                                break;
                        }
                    }
                    break;

                // /ko rounds <rounds>
                case 'rounds':
                    if (is_null($args[1]))
                    {
                        $onError('Syntax error: expected an argument (usage: $fff/ko rounds <X rounds per track>$g)');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko rounds <X rounds per track>$g)');
                    }
                    elseif (!is_numeric($args[1]))
                    {
                        $onError(sprintf('Error: argument $fff%s$g is not a number', $args[1]));
                    }
                    elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
                    {
                        $onError(sprintf('Error: floating point numbers ($fff%s$g) are not supported', $args[2]));
                    }
                    else
                    {
                        $val = (int) $args[1];
                        if ($val <= 0)
                        {
                            $onError(sprintf('Error: argument $fff%d$g must be greater than 0', $val));
                        }
                        else
                        {
                            $prev = $this->maxRounds;
                            $this->maxRounds = $val;
                            $diff = $val - $prev;
                            $pointsLimit = QueryManager::queryWithResponse('GetRoundPointsLimit');
                            if (KnockoutStatus::isInProgress($this->koStatus))
                            {
                                QueryManager::query('SetRoundPointsLimit', $pointsLimit['CurrentValue'] + $diff);
                            }
                            Chat::info(sprintf('Rounds per track has been set to $fff%d$g (previously $fff%d$g)', $val, $prev));
                        }
                    }
                    break;

                // /ko openwarmup (on | off)
                case 'openwarmup':
                    if (is_null($args[1]))
                    {
                        $onError('Syntax error: expected an argument (usage: $fff/ko openwarmup (on | off)$g)');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko openwarmup (on | off)$g)');
                    }
                    elseif ($args[1] === 'on')
                    {
                        $this->openWarmup = true;
                        if ($this->isWarmup) $this->letKnockedOutPlayersPlay();
                        Chat::info('Open warmup has been enabled');
                    }
                    elseif ($args[1] === 'off')
                    {
                        $this->openWarmup = false;
                        if ($this->isWarmup) $this->putKnockedOutPlayersIntoSpec();
                        Chat::info('Open warmup has been disabled');
                    }
                    else
                    {
                        $onError(sprintf('Error: unexpected argument $fff%s$g (expected $fffon$g or $fffoff$g)', $args[1]));
                    }
                    break;

                // /ko falsestart <max_tries>
                case 'falsestart':
                    if (is_null($args[1]))
                    {
                        $onError('Syntax error: expected an argument (usage: $fff/ko falsestart <max tries>$g)');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko falsestart <max tries>$g)');
                    }
                    elseif (!is_numeric($args[1]))
                    {
                        $onError(sprintf('Error: argument $fff%s$g is not a number', $args[1]));
                    }
                    elseif (str_contains($args[1], '.') || str_contains($args[1], ','))
                    {
                        $onError(sprintf('Error: floating point numbers ($fff%s$g) are not supported', $args[2]));
                    }
                    else
                    {
                        $val = (int) $args[1];
                        if ($val < 0)
                        {
                            $onError(sprintf('Error: argument $fff%d$g must be 0 or greater', $val));
                        }
                        else
                        {
                            $prev = $this->maxFalseStarts;
                            $this->maxFalseStarts = $val;
                            $msg = $val === 0
                                ? sprintf('False start detection have been disabled (previously set to $fff%d$g)', $prev)
                                : sprintf('False start limit has been set to $fff%d$g (previously $fff%d$g)', $val, $prev);
                            Chat::info($msg);
                        }
                    }
                    break;

                // /ko tiebreaker (on | off)
                case 'tiebreaker':
                    if (is_null($args[1]))
                    {
                        $onError('Syntax error: expected an argument (usage: $fff/ko tiebreaker (on | off)>$g)');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko tiebreaker (on | off)$g)');
                    }
                    elseif ($args[1] === 'on')
                    {
                        $this->tiebreaker = true;
                        Chat::info('Tiebreakers have been enabled');
                    }
                    elseif ($args[1] === 'off')
                    {
                        $this->tiebreaker = false;
                        Chat::info('Tiebreakers have been disabled');
                    }
                    else
                    {
                        $onError(sprintf('Error: unexpected argument $fff%s$g (expected $fffon$g or $fffoff$g)', $args[1]));
                    }
                    break;

                // /ko authorskip <for_top_x_players>
                case 'authorskip':
                    if (is_null($args[1]))
                    {
                        $onError('Syntax error: expected an argument (usage: $fff/ko authorskip <for top X players>$g)');
                    }
                    elseif (isset($args[2]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko authorskip <for top X players>$g)');
                    }
                    elseif (!is_numeric($args[1]))
                    {
                        $onError(sprintf('Error: argument $fff%s$g is not a number', $args[1]));
                    }
                    elseif (str_contains($args[1], '.') || str_contains($args[1], ','))
                    {
                        $onError(sprintf('Error: floating point numbers ($fff%s$g) are not supported', $args[2]));
                    }
                    else
                    {
                        $val = (int) $args[1];
                        if ($val < 0)
                        {
                            $onError(sprintf('Error: argument $fff%d$g must be 0 or greater', $val));
                        }
                        else
                        {
                            $prev = $this->authorSkip;
                            $this->authorSkip = $val;
                            $msg = $val === 0
                                ? sprintf('Author skips have been disabled (previously set to $fff%d$g)', $prev)
                                : sprintf('Author skip has been enabled for top $fff%d$g (previously $fff%d$g)', $val, $prev);
                            Chat::info($msg);
                        }
                    }
                    break;

                // /ko settings
                case 'settings':
                    if (isset($args[1]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko settings$g)');
                    }
                    else
                    {
                        Chat::info2($this->printSettings(), array($issuerLogin));
                    }
                    break;

                // /ko status
                case 'status':
                    if (isset($args[1]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko status$g)');
                    }
                    else
                    {
                        $playerList = array_map(
                            function($player)
                            {
                                return sprintf('%s (%s %s)',
                                    getNameOfConstant($player['Status'], 'PlayerStatus'),
                                    $player['Lives'],
                                    $player['Lives'] === 1 ? 'life' : 'lives'
                                );
                            },
                            $this->playerList->getAll()
                        );
                        $scores = array_map(
                            function($score) { return sprintf('%s (%s)', $score['Login'], $score['Score']); },
                            $this->scores->getSortedScores()
                        );
                        $text = implode("\n", array(
                            sprintf('KO status: %s', getNameOfConstant($this->koStatus, 'KnockoutStatus')),
                            sprintf('KO mode: %s', getNameOfConstant($this->koMode, 'KnockoutMode')),
                            sprintf('KOs this round: %d', $this->kosThisRound),
                            sprintf('Is warmup: %d', $this->isWarmup),
                            sprintf('Is podium: %d', $this->isPodium),
                            sprintf('Game mode: %s', getNameOfConstant($this->gameMode, 'GameMode')),
                            sprintf('Player list: %s', print_r($playerList, true)),
                            sprintf('Scores: %s', print_r($scores, true))
                        ));
                        UI::showInfoDialog($text, array($issuerLogin));
                    }
                    break;

                // ko help
                case 'help':
                    if (isset($args[1]))
                    {
                        $onError('Syntax error: too many arguments (usage: $fff/ko help$g)');
                    }
                    else
                    {
                        $this->cliReference(1, array($issuerLogin));
                    }
                    break;

                default:
                    $onError(sprintf('Syntax error: unexpected argument $fff%s$g (see $fff/ko help$g for usages)', $args[0]));
                    break;
            }
        }
    }

    private function cliReference($pageNumber, $logins)
    {
        $prefix = " \n\$s";
        $sep1 = "\n\n";
        $sep2 = "\n    ";
        $var = '$i';
        $endvar = '$i';
        $totalPages = 3;

        switch ($pageNumber)
        {
            case 1:
                $text = implode($sep1, array(
                    implode($sep2, array(
                        "/ko start [now]",
                        'Starts the knockout. If "now" is given, the current track will be skipped.'
                    )),

                    implode($sep2, array(
                        "/ko stop",
                        'Stops the knockout with immediate effect.'
                    )),

                    implode($sep2, array(
                        "/ko skip [warmup]",
                        'Skips the current track. If "warmup" is given, only the warmup is skipped.'
                    )),

                    implode($sep2, array(
                        "/ko restart [warmup]",
                        'Restarts the current track, or the current round if in Rounds. If "warmup" is given, the track is',
                        'restarted with a warmup.'
                    )),

                    implode($sep2, array(
                        "/ko add ({$var}login{$endvar} | *)",
                        'Adds a player to the knockout. If the wildcard * is used, then everyone on the server is added.'
                    )),

                    implode($sep2, array(
                        "/ko remove ({$var}login{$endvar} | *)",
                        'Removes a player from the knockout, regardless of how many lives they have.'
                    )),

                    implode($sep2, array(
                        "/ko spec ({$var}login{$endvar} | *)",
                        'Same as /ko remove but instead puts the player into spectator status.'
                    )),

                    implode($sep2, array(
                        "/ko lives ({$var}login{$endvar} | *) [[+ | -]{$var}lives{$endvar}]",
                        'Displays or adjusts the number of lives to use for the knockout.'
                    ))
                ));
                UI::showMultiPageDialog("{$prefix}{$text}", $logins, 1, $totalPages, null, 462);
                break;

            case 2:
                $text = implode($sep1, array(
                    implode($sep2, array(
                        "/ko multi (constant {$var}kos{$endvar} | extra {$var}per_x_players{$endvar} | dynamic {$var}total_rounds{$endvar} | none)",
                        'Sets the KO multiplier mode.',
                        '- Constant: x KOs per round',
                        '- Extra: +1 KO for every x\'th player',
                        '- Dynamic: Aims for a total of x rounds',
                        '- None: 1 KO per round'
                    )),

                    implode($sep2, array(
                        "/ko rounds {$var}rounds{$endvar}",
                        'Sets the number of rounds per track to play in Rounds.'
                    )),

                    implode($sep2, array(
                        "/ko openwarmup (on | off)",
                        'Enables or disables open warmup which lets knocked out players play during warmup.'
                    )),

                    implode($sep2, array(
                        "/ko falsestart {$var}max_tries{$endvar}",
                        'Sets the limit for how many times the round will be restarted if someone retires before the countdown.'
                    )),

                    implode($sep2, array(
                        "/ko tiebreaker (on | off)",
                        'Enables or disables tiebreakers, a custom mode which takes effect when multiple players tie and at not all of them would be knocked out.'
                    )),

                    implode($sep2, array(
                        "/ko authorskip {$var}for_top_x_players{$endvar}",
                        'Automatically skips a track when its author is present, once a given player count has been reached.'
                    )),

                    implode($sep2, array(
                        "/ko settings",
                        'Displays knockout settings such as multiplier, lives, open warmup, etc in the chat.'
                    ))
                ));
                UI::showMultiPageDialog("{$prefix}{$text}", $logins, 2, $totalPages, 461, 463);
                break;

            case 3:
                $text = implode($sep1, array(
                    implode($sep2, array(
                        "/ko status",
                        'Shows knockout mode, knockout status, player list and scores in a dialog window.'
                    )),

                    implode($sep2, array(
                        "/ko help",
                        'Shows the list of commands.'
                    )),

                    '$4af$l[http://github.com/ManiaExchange/GeryKnockout/blob/main/docs/cli.md]CLI reference$l'
                ));
                UI::showMultiPageDialog("{$prefix}{$text}", $logins, 3, $totalPages, 462, null);
                break;

            default:
                Log::warning(sprintf('Tried to retrieve non-existing page $d of CLI reference', $pageNumber));
                break;
        }
    }

    /**
     * Displays a dialog with information about the KO.
     *
     * @param array $args Arguments to the command.
     * @param array $issuer A single-element array.
     *
     *     $issuer = [
     *         [0] => (string) The login of the player who issued the command.
     *         [1] => (string) The nickname of the player who issued the command.
     *     ]
     */
    public function infoChatCommand($args, $issuer)
    {
        $login = $issuer[0];
        $text = implode("\n\n", array(
            implode("\n", array(
                '$s',
                '$oAbout the TM$f00X$g Knockout United$o'
            )),

            implode("\n", array(
                'The TMX Knockout United is a weekly knockout event designed to be fun! It provides casual gameplay',
                'and an element of unpredictable results. No training of tracks is required - just join the server in time and',
                'play.'
            )),

            implode("\n", array(
                'The event is held every Friday at 22:00 CET/CEST. We usually play KO9 (Stunts) the first Friday of the',
                'month and other track groups the other weeks.'
            )),

            implode("\n", array(
                'When the knockout starts, everyone is put to play. If the HUD is enabled, a status bar is shown at the top',
                '(click on the TMGery button on the top left to enable/disable). We usually play Rounds with one',
                'warmup and one live round for each track. At the end of each round, the last placed driver, or all those',
                'who do not finish the race, are knocked out until one player remains as the winner.'
            )),

            implode("\n", array(
                'If you experience a disconnection, you may get reinstated only if you haven\'t been eliminated yet and,',
                'without missing any rounds,',
                '    - rejoin during the warmup (Rounds, Laps)',
                '    - rejoin with time to spare (Time Attack, Stunts)'
            )),

            implode("\n", array(
                'If you do get knocked out, don\'t fret! You can still play during warmups, and there are multiple knockouts',
                'to be played; you can participate again if you wait for the next knockout.'
            )),

            'The most important part is: never give up! Someone may have retired :)',

            implode("\n", array(
                '$4af$l[http://bit.ly/TMXSpreadsheet]Results and map list$l',
                '$l[http://discordapp.com/invite/Ttkw54Y]TMX Discord$l'
            )),
        ));
        UI::showInfoDialog($text, array($login));
    }

    /**
     * Invoked when a player does not want to participate in the KO.
     *
     * @param array $args Arguments to the command.
     * @param array $issuer A single-element array.
     *
     *     $issuer = [
     *         [0] => (string) The login of the player who issued the command.
     *         [1] => (string) The nickname of the player who issued the command.
     *     ]
     */
    public function optChatCommand($args, $issuer)
    {
        $issuerLogin = $issuer[0];
        if (!isset($args[0]))
        {
            Chat::error('Syntax error: expected an argument (usage: $fff/opt out$g)', array($issuerLogin));
        }
        elseif (isset($args[1]))
        {
            Chat::error('Syntax error: too many arguments (usage: $fff/opt out$g)', array($issuerLogin));
        }
        elseif (strtolower($args[0]) === 'in')
        {
            if ($this->koStatus === KnockoutStatus::Idle)
            {
                $msg = "You can not opt in to a knockout if it's not running";
                Chat::error($msg, array($issuerLogin));
            }
            else
            {
                $playerObj = $this->playerList->get($issuerLogin);
                if (PlayerStatus::isIn($playerObj['Status']) || PlayerStatus::isShelved($playerObj['Status']))
                {
                    $msg = "You can not opt in to a knockout you're already participating in";
                    Chat::error($msg, array($issuerLogin));
                }
                elseif (PlayerStatus::isOut($playerObj['Status']))
                {
                    $msg = "You can not opt in to a knockout you're been knocked out of";
                    Chat::error($msg, array($issuerLogin));
                }
                else
                {
                    if ($this->koStatus === KnockoutStatus::Tiebreaker)
                    {
                        $this->playerList->setStatus($issuerLogin, PlayerStatus::Shelved);
                        forceSpec(array($issuerLogin), true);
                    }
                    else
                    {
                        $this->playerList->setStatus($issuerLogin, PlayerStatus::Playing);
                        forcePlay(array($issuerLogin), true);
                    }
                    $this->onKoStatusUpdate();
                    Chat::info(sprintf('%s$z$s$g has opted in to the knockout', $playerObj['NickName']));
                }
            }
        }
        elseif (strtolower($args[0]) === 'out')
        {
            if ($this->koStatus === KnockoutStatus::Idle)
            {
                $msg = "You can not opt out of a knockout if it's not running";
                Chat::error($msg, array($issuerLogin));
            }
            {
                $playerObj = $this->playerList->get($issuerLogin);
                if (PlayerStatus::isIn($playerObj['Status']) || PlayerStatus::isShelved($playerObj['Status']))
                {
                    $text = 'Are you sure you want to opt out of the knockout?';
                    UI::showPrompt($text, 451, array($issuerLogin));
                }
                else
                {
                    $msg = "You can not opt out of a knockout you're not participating in";
                    Chat::error($msg, array($issuerLogin));
                }
            }
        }
        else
        {
            $msg = sprintf('Syntax error: unexpected argument $fff%s$g (expected $fff/opt out$g)', $args[0]);
            Chat::error($msg, array($issuerLogin));
        }
    }

    /**
     * @param array $args Arguments to the command.
     * @param array $issuer A single-element array.
     *
     *     $issuer = [
     *         [0] => (string) The login of the player who issued the command.
     *         [1] => (string) The nickname of the player who issued the command.
     *     ]
     */
    public function test1ChatCommand($args, $issuer)
    {
        if ($issuer[0] === 'voyager006')
        {
            Log::debug('test1 1');
            $this->playerList->applyStatusTransition(PlayerStatus::Playing, PlayerStatus::Shelved);
            Log::debug('test1 2');
        }
        else
        {
            Chat::error(" UNKNOWN COMMAND !", array($issuer['Login']));
        }
    }

    /**
     * @param array $args Arguments to the command.
     * @param array $issuer A single-element array.
     *
     *     $issuer = [
     *         [0] => (string) The login of the player who issued the command.
     *         [1] => (string) The nickname of the player who issued the command.
     *     ]
     */
    public function test2ChatCommand($args, $issuer)
    {
        if ($issuer[0] === 'voyager006')
        {
            Log::debug('test2 1');
            $this->playerList->applyStatusTransition(PlayerStatus::Shelved, PlayerStatus::Playing);
            Log::debug('test2 2');
        }
        else
        {
            Chat::error(" UNKNOWN COMMAND !", array($issuer['Login']));
        }
    }

    /**
     * @param array $args Arguments to the command.
     * @param array $issuer A single-element array.
     *
     *     $issuer = [
     *         [0] => (string) The login of the player who issued the command.
     *         [1] => (string) The nickname of the player who issued the command.
     *     ]
     */
    public function test3ChatCommand($args, $issuer)
    {
        if ($issuer[0] === 'voyager006')
        {
            Log::debug('test3 1');
            $this->playerList->filterByStatus(PlayerStatus::Playing);
            Log::debug('test3 2');
            $this->playerList->filterByStatus(PlayerStatus::OptingOut);
            Log::debug('test3 3');
        }
        else
        {
            Chat::error(" UNKNOWN COMMAND !", array($issuer['Login']));
        }
    }

    /**
     * Called when a player clicks on a button.
     *
     * @param array $args Arguments to the command.
     *
     *     $args = [
     *         [0] => (int) The player UID.
     *         [1] => (string) The player login.
     *         [2] => (int) The ID of the manialink element being clicked.
     *     ]
     */
    public function playerManialinkPageAnswer($args)
    {
        global $PlayerScript;
        Log::debug(sprintf('playerManialinkPageAnswer %s', implode(' ', $args)));

        $login = $args[1];
        switch ($args[2])
        {
            // Tm-Gery GUI button
            case 98:
                // TmGery has already changed the state of PlayerScript by now
                if (KnockoutStatus::isInProgress($this->koStatus) && $this->roundNumber > 0)
                {
                    if ($PlayerScript[$login] === '1')
                    {
                        $this->updateStatusBar($login);
                    }
                    else
                    {
                        UI::hideStatusBar(array($login));
                    }
                }
                break;

            // /opt out
            case 451:
                $playerObj = $this->playerList->get($login);
                if (($this->koStatus === KnockoutStatus::Running && !$this->isPodium)
                    || ($this->koStatus === KnockoutStatus::Tiebreaker && $playerObj['Status'] === PlayerStatus::Playing))
                {
                    $this->scores->set($login, $playerObj['NickName'], Scores::DidNotFinish);
                }
                else
                {
                    $this->playerList->setStatus($login, PlayerStatus::OptingOut);
                }
                forceSpec(array($login), true);
                $this->onKoStatusUpdate();
                Chat::info(sprintf('%s$z$s$g has opted out of the knockout', $playerObj['NickName']));
                break;

            case 461:
                $this->cliReference(1, array($login));
                break;

            case 462:
                $this->cliReference(2, array($login));
                break;

            case 463:
                $this->cliReference(3, array($login));
                break;
        }
    }
}

$this->AddPlugin(new KnockoutRuntime());

$this->AddEvent('onStartup', 'onControllerStartup');
$this->AddEvent('BeginRound', 'onBeginRound');
$this->AddEvent('BeginRace', 'onBeginRace');
$this->AddEvent('EndRound', 'onEndRound');
$this->AddEvent('EndRace', 'onEndRace');
$this->AddEvent('PlayerConnect', 'onPlayerConnect');
$this->AddEvent('PlayerDisconnect', 'onPlayerDisconnect');
$this->AddEvent('PlayerCheckpoint', 'onPlayerCheckpoint');
$this->AddEvent('PlayerFinish', 'onPlayerFinish');
$this->AddEvent('PlayerInfoChanged', 'onPlayerInfoChange');
$this->AddEvent('StatusChanged', 'onStatusChange');
$this->AddEvent('PlayerManialinkPageAnswer', 'playerManialinkPageAnswer');

$this->addChatCommand('ko', true, 'adminChatCommands');
$this->addChatCommand('info', true, 'infoChatCommand');
$this->addChatCommand('opt', true, 'optChatCommand');
$this->addChatCommand('test1', false, 'test1ChatCommand');
$this->addChatCommand('test2', false, 'test2ChatCommand');
$this->addChatCommand('test3', false, 'test3ChatCommand');

?>
