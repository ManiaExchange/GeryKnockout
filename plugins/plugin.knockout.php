<?php
/*
 * Knockout plugin for TMGery by Voyager006.
 * Dynamic KO multiplier algorithm by Solux.
 * Based on original plugin by CavalierDeVache. Idea by Mikey.
 */
namespace Knockout;

const Version = '3.0.0 (beta)';
const MinimumLogLevel = Log::Information;
const AuthorSkipLimit = 10;

require_once 'includes/MethodsTmf.php';
require_once 'includes/StructsTmf.php';

use Tmf\Client as Client;
use Tmf\Multicall as Multicall;

use Tmf\CameraType as CameraType;
use Tmf\GameMode as GameMode;
use Tmf\ServerStatus as ServerStatus;
use Tmf\SpectatorMode as SpectatorMode;
use Tmf\StructVersion as StructVersion;


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
    $fooClass = new \ReflectionClass($className);
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
    $possibleClasses = array(
        __NAMESPACE__."\\".$className,
        "\\Tmf\\".$className,
        $className
    );
    foreach ($possibleClasses as $class)
    {
        try
        {
            return array_search($value, getConstants($class), true);
        }
        catch (\ReflectionException $e)
        {
            // Could not find a class of the given name
            // Try with next class name
        }
    }
    return false;
}


/**
 * Compares two numbers.
 *
 * @param int|float $a A number.
 * @param int|float $b The number to compare `$a` against.
 *
 * @return int -1 if `$a` is less than `$b`, 1 if `$a` is greater than `$b`, 0 otherwise.
 */
function compare($a, $b)
{
    if ($a < $b) return -1;
    elseif ($a > $b) return 1;
    else return 0;
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
    return compare($number, 0);
}


/**
 * Outputs a quantity plus the singular or plural form of its unit, depending on its quantity.
 *
 * @param int|float $value The quantity to check against.
 * @param string $singular The unit to use in singular form.
 * @param string $plural The unit to use in plural form.
 *
 * @return string The quantity, plus the singular or plural form of its unit.
 */
function pluralize($value, $singular, $plural)
{
    return ($value == 1) ? "{$value} {$singular}" : "{$value} {$plural}";
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


if (!function_exists('array_key_last')) {
    /**
     * Gets the last key of an array.
     *
     * @param array $array An array.
     *
     * @return mixed The last key of `array` if the array is not empty; `null` otherwise.
     */
    function array_key_last($array)
    {
        if (!isset($array) || empty($array)) return null;
        else
        {
            $keys = array_keys($array);
            return $keys[count($array) - 1];
        }
    }
}


/**
 * Tests if the given player is connected to the server.
 *
 * @param string $login The login of the player.
 *
 * @return bool True if the player is currently on the server.
 */
function isOnServer($login)
{
    global $PlayerScript;

    return isset($PlayerScript[$login]);
}


/**
 * Tests if the given player has TMGery HUD enabled. If the player is not found, false is returned.
 *
 * @param string $login The login of the player.
 *
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


function filterMulticallErrors($result)
{
    return array_filter($result, function($item)
    {
        return isset($item['faultCode']) && isset($item['faultString']);
    });
}


/**
 * Forces given players into play.
 *
 * @param string|array $logins A login or an array of logins.
 * @param bool $force Whether the player(s) should be unable to switch between play and spec after
 * forcing. Set to false to let player(s) go to spec afterwards.
 *
 * @return bool True if the queries were sent successfully, false if an error occurred or there are
 * no players to be forced.
 */
function forcePlay($logins, $force)
{
    global $client;

    if (!is_array($logins)) $logins = array($logins);
    if (count($logins) > 0)
    {
        Log::debug(sprintf(
            'Forcing the following players into play (%s): %s',
            $force ? 'ForcePlay' : 'UserSelectable',
            print_r($logins, true)
        ));
        $multicall = new Multicall($client);
        foreach ($logins as $login)
        {
            $multicall->forceSpectator($login, SpectatorMode::Player);
            if (!$force) $multicall->forceSpectator($login, SpectatorMode::UserSelectable);
        }
        $result = $multicall->submit();
        if (is_null($result)) return false;
        else return count(filterMulticallErrors($result)) === 0;
    }
    else
    {
        return false;
    }
}


/**
 * Forces given players into being spectators.
 *
 * @param string|array $logins A login or an array of logins.
 * @param bool $force Whether the player(s) should be unable to switch between play and spec after
 * forcing. Set to false to let player(s) go to play afterwards.
 *
 * @return bool True if the queries were sent successfully, false if an error occurred or there are
 * no players to be forced.
 */
function forceSpec($logins, $force)
{
    global $client;

    if (!is_array($logins)) $logins = array($logins);
    if (count($logins) > 0)
    {
        Log::debug(sprintf(
            'Forcing the following players into spec (%s): %s',
            $force ? 'ForceSpec' : 'UserSelectable',
            print_r($logins, true)
        ));
        $multicall = new Multicall($client);
        foreach ($logins as $login)
        {
            $multicall->forceSpectator($login, SpectatorMode::Spectator);
            if (!$force) $multicall->forceSpectator($login, SpectatorMode::UserSelectable);
        }
        $result = $multicall->submit();
        if (is_null($result)) return false;
        else return count(filterMulticallErrors($result)) === 0;
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


abstract class KnockoutMode
{
    const Normal = 11;
    // const Countdown = 12; // Beat the slowest surviving player's time
    // const Endurance = 13; // Time from leader to KO gets shorter for each checkpoint
    // const Softcore = 14; // TA; skips track once x amount of players finish or time runs out
    // const AdvanceCup = 15; // Each round, the fastest players advance to the next map
    // const Combine = 16; // Total time across several rounds
}


abstract class KnockoutStatus
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


abstract class PlayerStatus
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
 * Used to determine what happens in the event of someone getting knocked out
 */
abstract class KnockoutBehaviour
{
    const PlayDuringWarmup = 51;
    const ForceSpec = 52;
    const KickUntilTop5 = 53;
}


/**
 * Utility class for logging in the console window.
 */
abstract class Log
{
    const Debug = 61;
    const Information = 62;
    const Warning = 63;
    const Error = 64;

    private static function write($level, $message)
    {
        printf('[%s %s] plugin.knockout.php: %s%s', date('H:i:s'), $level, $message, "\n");
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
 * Links to manialink IDs
 */
abstract class Actions
{
    // Defined by TMGery
    const ToggleHUD = 98;
    const Dismiss = 99;

    // Knockout UI
    const ConfirmOptOut = 420001;
    const CliReferencePage1 = 420011;
    const CliReferencePage2 = 420012;
    const CliReferencePage3 = 420013;

    // Ranges
    const SpectatePlayerMin = 421000;
    const SpectatePlayerMax = 421255;
}


/**
 * Utility class for text formatting (not associated with chat).
 */
abstract class Text
{
    const Announce = '$0f0';
    const AnnounceHighlight = '$fff';
    const Info = '$fff';
    const InfoHighlight = '$ff0';
    const Info2 = '$aaa';
    const Info2Highlight = '$fff';
    const Error = '$f00';
    const ErrorHighlight = '$fff';

    /**
     * Finds and replaces formatting tags in a string using a callback function.
     *
     * @param string $text The text to modify.
     * @param Callable $callback A function to replace found tags with.
     *
     * It must support at least one argument, the argument being the tag string that was found. Its
     * capitalization is unaltered. For `$h`, `$l` and `$p` tags, an optional link is included in
     * the argument (e.g. `$l[website.com]`).
     *
     * For tags except `$000`-`$fff`, `$g`, `$m` and `$z`, a second argument is used; a boolean
     * indicating whether it is the opening tag or not.
     *
     * `$<` and `$>` are also supported, although they will have no effect if unaltered.
     *
     * The function should return a string; the replacement for the given tag.
     *
     * @return string The input string with tags replaced according to the callback function.
     */
    public static function findAndReplaceCallback($text, $callback)
    {
        $index = 0;
        $length = strlen($text);
        $result = '';
        $openedTags = array();
        $registerTag = function($tag, $checkIfItsAnOpeningTag) use(&$result, $callback, &$index, &$openedTags)
        {
            if ($checkIfItsAnOpeningTag)
            {
                $key = substr($tag, 0, 2);
                $openedTags[$key] = isset($openedTags[$key]) ? !$openedTags[$key] : true;
                $result .= $callback($tag, $openedTags[$key]);
            }
            else
            {
                $result .= $callback($tag, null);
            }
            $index += strlen($tag);
        };

        while ($index < $length)
        {
            $current = $text[$index];
            if ($current === '$')
            {
                if (!isset($text[$index + 1]))
                {
                    $registerTag('$', false);
                }
                else
                {
                    $next = $text[$index + 1];
                    if (preg_match('/[0-9a-f]/i', $next))
                    {
                        $tag = substr($text, $index, 4);
                        $registerTag($tag, false);
                    }
                    elseif (preg_match('/[gmz]/i', $next))
                    {
                        if ($next === 'z') $openedTags = array();
                        $tag = '$' . $next;
                        $registerTag($tag, false);
                    }
                    elseif (preg_match('/[hlp]/i', $next))
                    {
                        if (isset($text[$index + 2]) && $text[$index + 2] === '[')
                        {
                            $endIndex = strpos($text, ']', $index + 3);
                            if ($endIndex !== false)
                            {
                                $tag = substr($text, $index, $endIndex + 1 - $index);
                                $registerTag($tag, true);
                            }
                            else
                            {
                                $tag = '$' . $next;
                                $registerTag($tag, true);
                            }
                        }
                        else
                        {
                            $tag = '$' . $next;
                            $registerTag($tag, true);
                        }
                    }
                    elseif ($next === '$')
                    {
                        $result .= '$$';
                        $index += 2;
                    }
                    elseif ($next === '<')
                    {
                        $tag = '$' . $next;
                        $result .= $callback($tag, true);
                        $index += 2;
                    }
                    elseif ($next === '>')
                    {
                        $tag = '$' . $next;
                        $result .= $callback($tag, false);
                        $index += 2;
                    }
                    else
                    {
                        $tag = '$' . $next;
                        $registerTag($tag, true);
                    }
                }
            }
            else
            {
                $result .= $text[$index];
                $index += 1;
            }
        }
        return $result;
    }

    /**
     * Finds and replaces the given formatting tag in a string.
     *
     * @param string $text The text to modify.
     * @param string $search The formatting tag to find.
     * @param string $replace The string to replace the tag with.
     *
     * @return string The input string with occurrences of the tag replaced with the given
     * replacement.
     */
    public static function findAndReplace($text, $search, $replace)
    {
        $search = strtolower($search);
        $callback = function($tag) use($search, $replace)
        {
            if (strtolower($tag) === $search) return $replace;
            else return $tag;
        };
        return self::findAndReplaceCallback($text, $callback);
    }

    /**
     * Removes all formatting in a string, leaving all formatting tags out.
     *
     * @param string $text The text to remove formatting from.
     *
     * @return string The unformatted equivalent of the input text.
     */
    public static function clean($text)
    {
        return self::findAndReplaceCallback($text, function() { return ''; });
    }

    /**
     * Sanitizes a string such that formatting tags are explicitly shown (e.g. `$g` -> `$$g`).
     *
     * @param string $text The text to sanitize.
     *
     * @return string The sanitized input text.
     */
    public static function sanitize($text)
    {
        return self::findAndReplaceCallback($text, function($tag) { return "\$$tag"; });
    }

    /**
     * Prevent the formatting of a string from bleeding over to subsequent text. Use this function
     * for nick names as they may have certain formatting causing this issue.
     *
     * @param string $text The text to match tags within.
     *
     * @return string The text, encapsulated to prevent bleeding.
     */
    public static function trim($text)
    {
        return self::findAndReplace($text, '$', '');
    }

    /**
     * Formats a string that contains formatting tags `$<` and `$>`. Nested bounds are supported.
     *
     * Important: adding formatting to the string output after calling this function may yield bad
     * results.
     *
     * @param string $text The text to format.
     * @param string $baseFormatting [Optional] A string containing the formatting applied prior to
     * this text. This formatting is not prepended to the final string, but is used to apply the
     * default style after a `$>` tag. Most commonly overridden for chat messages with `$s`. Leave
     * empty to default to no formatting.
     *
     * @return string The formatted text.
     */
    public static function format($text, $baseFormatting = '')
    {
        // Mutable stack of formattings. When a new scope is entered ($<), the last element is
        // duplicated and pushed to the stack. Pop the last element off when leaving a scope.
        //
        // Example: "$sHello $<$i$f0fworld$>!" yields, after "world":
        //
        //     $formatStack = array(
        //         [0] => array(
        //             'shadow' => '$s'
        //         )
        //         [1] => array(
        //             'color' => '$f0f',
        //             'italic' => '$i',
        //             'shadow' => '$s'
        //         )
        //     )
        $formatStack = array(array());
        $callback = function($tag) use(&$formatStack)
        {
            $tag = strtolower($tag);
            $toggle = function($type, $tag) use(&$formatStack)
            {
                if (!isset($formatStack[array_key_last($formatStack)][$type]))
                    $formatStack[array_key_last($formatStack)][$type] = $tag;
                else
                    unset($formatStack[array_key_last($formatStack)][$type]);
            };
            if (preg_match('/\$[0-9a-f].{2}/', $tag))
            {
                $formatStack[array_key_last($formatStack)]['color'] = $tag;
                return $tag;
            }
            else
            {
                switch (substr($tag, 1, 1))
                {
                    case 'g':
                        unset($formatStack[array_key_last($formatStack)]['color']);
                        return $tag;
                    case 'm':
                        unset($formatStack[array_key_last($formatStack)]['width']);
                        return $tag;
                    case 'w':
                    case 'n':
                        $formatStack[array_key_last($formatStack)]['width'] = $tag;
                        return $tag;
                    case 'h':
                    case 'l':
                    case 'p':
                        // $l can cancel a $h tag and vice versa
                        $toggle('hyperlink', $tag);
                        return $tag;
                    case 'i':
                        $toggle('italic', $tag);
                        return $tag;
                    case 'o':
                        $toggle('bold', $tag);
                        return $tag;
                    case 's':
                        $toggle('shadow', $tag);
                        return $tag;
                    case 't':
                        $toggle('uppercase', $tag);
                        return $tag;
                    case 'z':
                        $formatStack[array_key_last($formatStack)] = array();
                        return $tag;
                    case '<':
                        array_push($formatStack, $formatStack[array_key_last($formatStack)]);
                        return '';
                    case '>':
                        array_pop($formatStack);
                        return sprintf('$z%s', implode('', $formatStack[array_key_last($formatStack)]));
                    default:
                        return $tag;
                }
            }
        };
        // Populate the stack with prior formatting
        self::findAndReplaceCallback($baseFormatting, $callback);
        // Format the text and return the result
        return self::findAndReplaceCallback($text, $callback);
    }

    /**
     * Highlights a string with the given style. The output must then be processed in Text::format
     * in order to have an effect. Subsequent text will then remain unaltered.
     *
     * @param string $text The text to highlight.
     * @param string $formatting The formatting to use for the highlighted text.
     *
     * @return string The text, highlighted according to the given formatting.
     */
    public static function highlight($text, $formatting)
    {
        return "$<{$formatting}{$text}$>";
    }
}


/**
 * Utility class for in-game chat messaging.
 */
abstract class Chat
{
    const Prefix = '$ff0>> ';

    /**
     * Writes a message to the chat.
     *
     * @param string $message The message to be written. May contain in-game formatting.
     * @param string|array $logins [Optional] The login or logins of the players to send the message
     * to. If null, the message is sent to everyone.
     */
    public static function write($message, $logins = null)
    {
        global $gbxclient;

        $formatted = Text::format(sprintf('%s%s', self::Prefix, $message), '$s');
        if (is_null($logins))
        {
            $gbxclient->chatSendServerMessage($formatted);
        }
        else
        {
            if (is_string($logins)) $logins = array($logins);
            $commaSeparatedLogins = implode(',', $logins);
            $gbxclient->chatSendServerMessageToLogin($formatted, $commaSeparatedLogins);
        }
    }

    /**
     * Sends a formatted announcement message to the chat.
     *
     * The text may contain in-game formatting itself. Some extra rules are applied:
     *
     * - `$<` and `$>`: create a 'scope' of enclosed formatting with the color of
     *   `Text::AnnounceHighlight` added. The formatting is then restored when exiting the scope.
     *
     * Note: using `$g` and `$z` will reset the formatting applied by this function.
     *
     * @param string $message The message to be written.
     * @param string|array $logins [Optional] The login or logins of the players to send the message
     * to. If null, the message is sent to everyone.
     * @param string $baseColor [Optional] The base color to be used. If null, `Text::Announce` is
     * used.
     */
    public static function announce($message, $logins = null, $baseColor = null)
    {
        if (is_null($baseColor)) $baseColor = Text::Announce;
        $message = Text::findAndReplace($message, '$<', '$<' . Text::AnnounceHighlight);
        self::write("{$baseColor}{$message}", $logins);
    }

    /**
     * Sends a red-colored, formatted error message to the chat.
     *
     * The text may contain in-game formatting itself. Some extra rules are applied:
     *
     * - `$<` and `$>`: create a 'scope' of enclosed formatting with the color of
     *   `Text::ErrorHighlight` added. The formatting is then restored when exiting the scope.
     *
     * Note: using `$g` and `$z` will reset the formatting applied by this function.
     *
     * @param string $message The message to be written.
     * @param string|array $logins [Optional] The login or logins of the players to send the message
     * to. If null, the message is sent to everyone.
     */
    public static function error($message, $logins = null)
    {
        $baseColor = Text::Error;
        $message = Text::findAndReplace($message, '$<', '$<' . Text::ErrorHighlight);
        self::write("{$baseColor}{$message}", $logins);
    }

    /**
     * Sends a white-colored, formatted information message to the chat.
     *
     * The text may contain in-game formatting itself. Some extra rules are applied:
     *
     * - `$<` and `$>`: create a 'scope' of enclosed formatting with the color of
     *   `Text::InfoHighlight` added. The formatting is then restored when exiting the scope.
     *
     * Note: using `$g` and `$z` will reset the formatting applied by this function.
     *
     * @param string $message The message to be written.
     * @param string|array $logins [Optional] The login or logins of the players to send the message
     * to. If null, the message is sent to everyone.
     */
    public static function info($message, $logins = null)
    {
        $baseColor = Text::Info;
        $message = Text::findAndReplace($message, '$<', '$<' . Text::InfoHighlight);
        self::write("{$baseColor}{$message}", $logins);
    }

    /**
     * Sends a grey-colored, formatted information message to the chat.
     *
     * The text may contain in-game formatting itself. Some extra rules are applied:
     *
     * - `$<` and `$>`: create a 'scope' of enclosed formatting with the color of
     *   `Text::Info2Highlight` added. The formatting is then restored when exiting the scope.
     *
     * Note: using `$g` and `$z` will reset the formatting applied by this function.
     *
     * @param string $message The message to be written.
     * @param string|array $logins [Optional] The login or logins of the players to send the message
     * to. If null, the message is sent to everyone.
     */
    public static function info2($message, $logins = null)
    {
        $baseColor = Text::Info2;
        $message = Text::findAndReplace($message, '$<', '$<' . Text::Info2Highlight);
        self::write("{$baseColor}{$message}", $logins);
    }
}


/**
 * Class to hold the overall result of a knockout
 */
class Results
{
    private $results;
    private $startTime;

    public function __construct()
    {
        $this->results = array();
        $this->startTime = time();
    }

    public function insert($login, $nickName, $roundNumber, $score)
    {
        $this->results[] = array(
            'Login' => $login,
            'NickName' => $nickName,
            'RoundNumber' => $roundNumber,
            'Score' => $score
        );
    }

    private function compare($index1, $index2)
    {
        $a = $this->results[$index1];
        $b = $this->results[$index2];
        $roundsComparison = compare($a['RoundNumber'], $b['RoundNumber']);
        if ($roundsComparison !== 0) return $roundsComparison;
        else return compare($a['Score'], $b['Score']);
    }

    public function export()
    {
        $directory = 'results';
        $fileName = sprintf('%s\\Knockout-%s.txt', $directory, date('ymd-Hi', $this->startTime));
        $data = sprintf('Knockout event — %s%s', date('jS F Y, H:i', $this->startTime), "\n");

        $finalResults = array_reverse($this->results);
        $i = 0;
        $count = count($finalResults);

        foreach ($finalResults as $player)
        {
            $index = $i;
            while ($index > 0 && $this->compare($index - 1, $i) === 0)
            {
                $index -= 1;
            }
            $isTiedWithPlayerAbove = $i > 0 && $this->compare($i - 1, $i) === 0;
            $isTiedWithPlayerBelow = $i < $count - 1 && $this->compare($i, $i + 1) === 0;
            $isTie = $isTiedWithPlayerAbove || $isTiedWithPlayerBelow;
            if ($isTie) $data .= '=';
            $data .= sprintf(
                '%d. %s (%s) (%d)',
                $index + 1,
                Text::clean($player['NickName']),
                $player['Login'],
                $player['RoundNumber']
            );
            $i += 1;
        }

        if (!file_exists($directory)) {
            mkdir($directory);
        }

        if (file_put_contents($fileName, $data) === false)
        {
            Log::information("Export of results failed, instead, results are written to terminal: \n{$data}");
        }
        else
        {
            Log::information("Results exported to {$fileName}");
        }
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
    private $isAscending; // True for Stunts (points), false for TA and Rounds (round times)

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
        $ascending = $this->isAscending;
        return function($a, $b) use($ascending)
        {
            if ($a['Checkpoint'] < $b['Checkpoint']) return 1;
            elseif ($a['Checkpoint'] > $b['Checkpoint']) return -1;
            else
            {
                if ($b['Score'] <= 0) return -1;
                elseif ($a['Score'] <= 0) return 1;
                elseif ($a['Score'] === $b['Score']) return 0;
                elseif ($ascending) return $a['Score'] < $b['Score'] ? -1 : 1;
                else return $a['Score'] > $b['Score'] ? -1 : 1;
            }
        };
    }

    /**
     * Initializes the scores by setting the scores of the specified logins to 0. Usually done for
     * Time Attack and Stunts.
     *
     * @param array $logins An array of players, each with fields 'Login', 'PlayerId' and
     * 'NickName'.
     */
    public function initialize($players)
    {
        $init = function($player)
        {
            return array(
                'Login' => $player['Login'],
                'PlayerId' => $player['PlayerId'],
                'NickName' => $player['NickName'],
                'Checkpoint' => 0,
                'IsFinish' => true,
                'Score' => 0
            );
        };
        $this->scores = array_map($init, array_values($players));
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
            $isBetter = $this->isAscending ? ($current['Score'] < $next['Score']) : ($current['Score'] > $next['Score']);
            $shouldMoveUp = $next['Score'] <= 0 || $isBetter;
            if ($shouldMoveUp)
            {
                $this->scores[$i - 1] = $current;
                $this->scores[$i] = $next;
            }
        }
    }

    /**
     * Submits a score set by the given player.
     *
     * If the score is better than the previous one set by $login, the record will be updated. Worse
     * scores are ignored.
     *
     * @param string $login The login of the player.
     * @param int $playerId The player UID.
     * @param string $nickName The nickname of the player.
     * @param int $score The player's score.
     * @param int $checkpoint The checkpoint index currently crossed.
     * @param bool $isFinish Whether the score represents a finished run.
     */
    public function submitScore($login, $playerId, $nickName, $score, $checkpoint, $isFinish = true)
    {
        $logins = array_map(
            function($score) { return $score['Login']; },
            $this->scores
        );
        $index = array_search($login, $logins, true);
        if ($index === false)
        {
            Log::debug("New score by {$login}: CP {$checkpoint}, Score {$score}");
            $this->scores[] = array(
                'Login' => $login,
                'PlayerId' => $playerId,
                'NickName' => $nickName,
                'Checkpoint' => $checkpoint,
                'IsFinish' => $isFinish,
                'Score' => $score
            );
            if ($score > 0) $this->sort(count($this->scores) - 1);
        }
        elseif ($score > 0)
        {
            $previousCheckpoint = $this->scores[$index]['Checkpoint'];
            $previousScore = $this->scores[$index]['Score'];
            $isImprovement =
                ($checkpoint > $previousCheckpoint)
                || ($checkpoint === $previousCheckpoint && $this->isAscending ? ($score < $previousScore) : ($score > $previousScore));
            if ($isImprovement)
            {
                Log::debug("Improvement by {$login}: CP {$previousCheckpoint}->{$checkpoint}, Score {$previousScore}->{$score}");
                $this->scores[$index] = array(
                    'Login' => $login,
                    'PlayerId' => $playerId,
                    'NickName' => $nickName,
                    'Checkpoint' => $checkpoint,
                    'IsFinish' => $isFinish,
                    'Score' => $score
                );
                $this->sort($index);
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
     * Removes the score (if set) of the given player. The player will not be appearing in
     * scoreboards.
     *
     * @param string $login The login of the player.
     *
     * @return bool True if the player was found and their score was removed.
     */
    public function remove($login)
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
            unset($this->scores[$index]);
            $this->scores = array_values($this->scores);
            return true;
        }
    }

    /**
     * Explicitly sets a score that might be lower than the current score. Note that this should
     * only be used for administrative operations as it is less performant than submitScore.
     *
     * @param string $login The login of the player.
     * @param int $playerId The player UID.
     * @param string $nickName The nickname of the player.
     * @param int $score The time or score of the player.
     */
    public function set($login, $playerId, $nickName, $score, $checkpoint)
    {
        $logins = array_map(
            function($player) { return $player['Login']; },
            $this->scores
        );
        $index = array_search($login, $logins, true);
        if ($index === false)
        {
            $this->submitScore($login, $playerId, $nickName, $score, $checkpoint);
        }
        else
        {
            $this->scores[$index] = array(
                'Login' => $login,
                'PlayerId' => $playerId,
                'NickName' => $nickName,
                'Checkpoint' => $checkpoint,
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
    public function add($login, $playerId, $nickName, $status = PlayerStatus::Playing, $lives = 1)
    {
        $this->players[$login] = array(
            'Login' => $login,
            'PlayerId' => $playerId,
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
     * the fields 'Login', 'PlayerId' and 'NickName'.
     * @param PlayerStatus $status [Optional] The status of the players. Default is
     * PlayerStatus::Playing.
     * @param int $lives [Optional] The number of lives each player should have. Default is 1.
     */
    public function addAll($players, $status = PlayerStatus::Playing, $lives = 1)
    {
        foreach ($players as $player)
        {
            $this->add($player['Login'], $player['PlayerId'], $player['NickName'], $status, $lives);
        }
    }

    /**
     * Gets a player object by their login.
     *
     * @param string $login The login of the player.
     *
     * @return mixed[]|null The player object if found, null otherwise.
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
     * Updates the player UID of the given player.
     *
     * @param string $login The login of the player.
     * @param int $playerId The new player ID.
     */
    public function setPlayerId($login, $playerId)
    {
        if (isset($this->players[$login]))
        {
            $this->players[$login]['PlayerId'] = $playerId;
        }
        else
        {
            Log::warning(sprintf('Player %s is not in the player list', $login));
        }
    }

    /**
     * Updates the nickname of the given player.
     *
     * @param string $login The login of the player.
     * @param string $nickname The new nickname.
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
     * Returns the players who are in the knockout.
     */
    public function getPlaying()
    {
        return array_filter(
            $this->players,
            function($player) { return PlayerStatus::isIn($player['Status']); }
        );
    }

    /**
     * Returns the players who are in the knockout.
     */
    public function getPlayingOrShelved()
    {
        return array_filter(
            $this->players,
            function($player) { return PlayerStatus::isIn($player['Status']) || PlayerStatus::isShelved($player['Status']); }
        );
    }

    /**
     * Returns the number of players who are in the knockout.
     */
    public function countPlaying()
    {
        return count($this->getPlaying());
    }
}


/**
 * Utility class for manialinks.
 */
abstract class UI
{
    const StatusBar = 420;
    const Scoreboard = 430;
    const Dialog = 440;
    const MultiPageDialog = 450;
    const Prompt = 460;
    const Message = 470;

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
            <manialink id="' . self::StatusBar . '">
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
     * @param array $players The players to show the manialink for. Must contain fields 'Login' and
     * 'Status'.
     */
    public static function updateStatusBar($knockoutState, $roundNb, $nbPlayers, $nbKOs, $players)
    {
        global $client;

        Log::debug(sprintf('updateStatusBar %d %d %d %d', $knockoutState, $roundNb, $nbPlayers, $nbKOs));

        $multicall = new Multicall($client);
        foreach ($players as $player)
        {
            $login = $player['Login'];
            $xml = self::statusBarManialink($player['Status'], $knockoutState, $roundNb, $nbPlayers, $nbKOs);
            $multicall->sendDisplayManialinkPageToLogin($login, $xml, 0, false);
        }
        $multicall->submit();
    }

    /**
     * Hides the status bar for the given players.
     *
     * @param string|array $logins [Optional] A login or an array of logins. If null, the status bar
     * is hidden for everyone.
     */
    public static function hideStatusBar($logins = null)
    {
        global $gbxclient;

        $manialink = '<manialink id="' . self::StatusBar . '"></manialink>';
        if (is_null($logins))
        {
            $gbxclient->sendDisplayManialinkPage($manialink, 0, false);
        }
        else
        {
            if (is_string($logins)) $logins = array($logins);
            $commaSeparatedLogins = implode(',', $logins);
            $gbxclient->sendDisplayManialinkPageToLogin($commaSeparatedLogins, $manialink, 0, false);
        }
    }

    private static function scoreboardManialink($scores, $gameMode, $numberOfKOs, $numberOfPlayers, $bestCPs)
    {
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
            if ($hours >= 1) return sprintf('%d:%02d:%02d.%02d', $hours, $minutes, $seconds, $centiseconds);
            else return sprintf('%d:%02d.%02d', $minutes, $seconds, $centiseconds);
        };
        $formatDistanceToLeader = function($yourMs, $leaderMs)
        {
            $difference = $yourMs - $leaderMs;
            $sign = '';
            if ($difference > 0) $sign = '+';
            elseif ($difference < 0) $sign = '-';
            $distance = abs($difference);
            $centiseconds = ($distance / 10) % 100;
            $seconds = ($distance / 1000) % 60;
            $minutes = ($distance / 60000) % 60;
            $hours = ($distance / 3600000) % 24;
            if ($hours >= 1) return sprintf('%s%d:%02d:%02d.%02d', $sign, $hours, $minutes, $seconds, $centiseconds);
            elseif ($minutes >= 1) return sprintf('%s%d:%02d.%02d', $sign, $minutes, $seconds, $centiseconds);
            else return sprintf('%s%d.%02d', $sign, $seconds, $centiseconds);
        };
        $positionWidth = function($position)
        {
            switch (strlen($position))
            {
                case 0: return 0.6;
                case 1: return 1.5;    // 1.
                case 2: return 2.4;    // 10.
                case 3: return 3.3;    // 100.
                default: return 4.2;   // 1000. +
            }
        };
        $scoreWidth = function($score)
        {
            $length = strlen($score);
            if ($length >= 10) return 8.6;      // 1:00:00.00 +
            elseif ($length >= 9) return 7.8;   //  +10:00.00
            elseif ($length >= 8) return 7.0;   //   10:00.00
            elseif ($length >= 7) return 6.2;   //    1:00.00
            elseif ($length >= 6) return 5.4;   //     +10.00
            elseif ($length >= 5) return 4.6;   //      +1.00
            elseif ($length >= 4) return 3.8;   //       0.00
            else return 3.0;                    //        DNF
        };
        $checkpointWidth = function($cp)
        {
            switch (strlen($cp))
            {
                case 1: return 0.6;    // 1
                case 2: return 1.5;    // 10
                case 3: return 2.4;    // 100
                case 4: return 3.3;    // 1000
                default: return 4.2;   // 10000 +
            }
        };

        $format = function($placement, $checkpoint, $timeOrScore, $isFinish) use($gameMode, $formatTime, $formatDistanceToLeader, $bestCPs)
        {
            if ($isFinish)
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
            }
            else
            {
                if ($gameMode === GameMode::Stunts)
                {
                    return "\$i{$timeOrScore}";
                }
                elseif ($placement === 1)
                {
                    return '$i' . $formatTime($timeOrScore);
                }
                else
                {
                    $bestCP = array_key_exists($checkpoint, $bestCPs) ? $bestCPs[$checkpoint] : $timeOrScore;
                    return '$i' . $formatDistanceToLeader($timeOrScore, $bestCP);
                }
            }
        };

        $DNFs = array_filter($scores, function($score) { return $score['Score'] < 0; });
        $nonDNFs = array_filter($scores, function($score) { return $score['Score'] >= 0; });

        // Pad the scores such that its length equals the number of players
        $notFinished = $numberOfPlayers - count($DNFs) - count($nonDNFs);
        $padding = $notFinished > 0 ? array_fill(0, $notFinished, null) : array();
        $scoresFormatted = array_merge($nonDNFs, $padding, $DNFs);

        $box = function($index, $row) use($scoresFormatted, $getPlacementColor, $format, $positionWidth, $scoreWidth, $checkpointWidth, $gameMode)
        {
            if (array_key_exists($index, $scoresFormatted))
            {
                $score = $scoresFormatted[$index];
                $height = 20.25 - 4.5 * $row;
                $color = $getPlacementColor($score['Score'], $index);
                $placement = $index + 1;
                $posWidth = $positionWidth($placement);
                $nickName = $score['NickName'];
                $scoreText = $format($placement, $score['Checkpoint'], $score['Score'], $score['IsFinish']);
                $scrWidth = $scoreWidth($scoreText);
                // Encode manialink ID with the target playerID
                $action = Actions::SpectatePlayerMin + $score['PlayerId'];
                if ($gameMode === GameMode::Laps)
                {
                    $cpText = $score['Checkpoint'];
                    $cpWidth = $checkpointWidth($cpText);
                    return '
                        <frame posn="-12 ' . $height . ' 1">
                            <quad posn="-12 0 1" sizen="0.2 4" halign="left" valign="center" bgcolor="' . $color . '" />
                            <label posn="-11 0 1" sizen="' . $posWidth . ' 4" halign="left" valign="center" scale="1.0" text="$fff' . $placement . '." />
                            <label posn="' . (-10.5 + $posWidth) . ' 0 1" sizen="' . (21 - $posWidth - $scrWidth - $cpWidth) . ' 4" halign="left" valign="center" scale="1.0" text="$fff' . $nickName . '" />
                            <label posn="' . (10.5 - $cpWidth) . ' 0 1" sizen="' . $scrWidth . ' 4" halign="right" valign="center" scale="1.0" text="$fff' . $scoreText . '" />
                            <label posn="11 0 1" sizen="' . $cpWidth . ' 4" halign="right" valign="center" scale="1.0" text="$fff' . $cpText . '" />
                            <quad posn="0 0 0" sizen="24 4" halign="center" valign="center" bgcolor="3338" action="' . $action . '" />
                        </frame>
                    ';
                }
                else
                {
                    return '
                        <frame posn="-12 ' . $height . ' 1">
                            <quad posn="-12 0 1" sizen="0.2 4" halign="left" valign="center" bgcolor="' . $color . '" />
                            <label posn="-11 0 1" sizen="' . $posWidth . ' 4" halign="left" valign="center" scale="1.0" text="$fff' . $placement . '." />
                            <label posn="' . (-10.5 + $posWidth) . ' 0 1" sizen="' . (21 - $posWidth - $scrWidth) . ' 4" halign="left" valign="center" scale="1.0" text="$fff' . $nickName . '" />
                            <label posn="11 0 1" sizen="' . $scrWidth . ' 4" halign="right" valign="center" scale="1.0" text="$fff' . $scoreText . '" />
                            <quad posn="0 0 0" sizen="24 4" halign="center" valign="center" bgcolor="3338" action="' . $action . '" />
                        </frame>
                    ';
                }
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

        $separatorLine = $numberOfPlayers > 10
            ? '<quad posn="0 9 2" sizen="24 0.2" halign="right" valign="center" bgcolor="bbbf" />'
            : '';

        // BgList or BgCardList
        // <frame posn="39.5 25 -2000000">
        return '
            <manialink id="' . self::Scoreboard . '">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="64.5 -6.5 -100">
                    ' . $rows . '
                    ' . $separatorLine . '
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
     * @param string|array $logins [Optional] The login or logins to display the scoreboard for. If
     * null, the scoreboard is displayed for everyone.
     */
    public static function updateScoreboard($scores, $gameMode, $numberOfKOs, $numberOfPlayers, $bestCPs, $logins = null)
    {
        global $gbxclient;
        Log::debug('updating scoreboard...');

        $manialink = self::scoreboardManialink($scores, $gameMode, $numberOfKOs, $numberOfPlayers, $bestCPs);
        if (is_null($logins))
        {
            $gbxclient->sendDisplayManialinkPage($manialink, 0, false);
        }
        else
        {
            if (is_string($logins)) $logins = array($logins);
            $commaSeparatedLogins = implode(',', $logins);
            $gbxclient->sendDisplayManialinkPageToLogin($commaSeparatedLogins, $manialink, 0, false);
        }
    }

    private static function emptyScoreboardManialink()
    {
        return '
            <manialink id="' . self::Scoreboard . '">
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
     * @param string|array $logins [Optional] The login or logins to hide the scoreboard for. If
     * null, the scoreboard is hidden for everyone.
     */
    public static function hideScoreboard($logins = null)
    {
        global $gbxclient;
        Log::debug('hiding scoreboard...');

        $manialink = self::emptyScoreboardManialink();
        if (is_null($logins))
        {
            $gbxclient->sendDisplayManialinkPage($manialink, 0, false);
        }
        else
        {
            if (is_string($logins)) $logins = array($logins);
            $commaSeparatedLogins = implode(',', $logins);
            $gbxclient->sendDisplayManialinkPageToLogin($commaSeparatedLogins, $manialink, 0, false);
        }
    }

    /**
     * Restores the default scoreboard.
     */
    public static function restoreDefaultScoreboard()
    {
        global $gbxclient;
        Log::debug('restoring default scoreboard...');

        $manialink = '
            <manialink id="' . self::Scoreboard . '"></manialink>
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
        $gbxclient->sendDisplayManialinkPage($manialink, 0, false);
    }

    /**
     * Shows a bigger info dialog with an OK button.
     *
     * @param string $text The text to display. Must be manually broken into lines, otherwise, the
     * text will become crammed.
     * @param string|array $logins The login or logins to display the dialog for.
     */
    public static function showInfoDialog($text, $logins)
    {
        global $gbxclient;

        $manialink = '
            <manialink id="' . self::Dialog . '">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="-40 43 1">
                    <quad posn="-1 1 0" sizen="82 78" halign="top" valign="left" style="Bgs1" substyle="BgWindow3" />
                    <label posn="0 0 1" sizen="80 3" halign="left" style="TextStaticSmall">' . $text . '</label>
                    <label posn="40 -73 1" sizen="1 1" halign="center" valign="center" style="CardButtonMedium" action="' . Actions::Dismiss . '">Ok</label>
                </frame>
            </manialink>
        ';
        if (is_string($logins)) $logins = array($logins);
        $commaSeparatedLogins = implode(',', $logins);
        $gbxclient->sendDisplayManialinkPageToLogin($commaSeparatedLogins, $manialink, 0, true);
    }

    /**
     * Shows a bigger info dialog with page count, arrows and an OK button.
     *
     * @param string $text The text to display. Must be manually broken into lines, otherwise, the
     * text will extend beyond the become crammed.
     * @param string|array $logins The login or logins to display the dialog for.
     * @param int $currentPageNumber The current page number (1-based).
     * @param int $totalPages The total number of pages.
     * @param int $prevPageActionId [Optional] The action id that results in the previous page. If
     * null, the button is greyed out.
     * @param int $nextPageActionId [Optional] The action id that results in the next page. If null,
     * the button is greyed out.
     */
    public static function showMultiPageDialog($text, $logins, $currentPageNumber, $totalPages, $prevPageActionId = null, $nextPageActionId = null)
    {
        global $gbxclient;

        $prevPage = is_null($prevPageActionId)
            ? '<quad posn="1.5 0 1" sizen="3 3" halign="center" valign="center" style="Icons64x64_1" substyle="StarGold" />'
            : '<quad posn="1.5 0 1" sizen="3 3" halign="center" valign="center" style="Icons64x64_1" substyle="ArrowPrev" action="' . $prevPageActionId . '" />';
        $nextPage = is_null($nextPageActionId)
            ? '<quad posn="5 0 1" sizen="3 3" halign="center" valign="center" style="Icons64x64_1" substyle="StarGold" />'
            : '<quad posn="5 0 1" sizen="3 3" halign="center" valign="center" style="Icons64x64_1" substyle="ArrowNext" action="' . $nextPageActionId . '" />';
        $manialink = '
            <manialink id="' . self::MultiPageDialog . '">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="-40 43 1">
                    <quad posn="-1 1 0" sizen="82 78" halign="top" valign="left" style="Bgs1" substyle="BgWindow3" />
                    <label posn="0 0 1" sizen="80 3" halign="left" style="TextStaticSmall">' . $text . '</label>
                    <label posn="40 -73 1" sizen="1 1" halign="center" valign="center" style="CardButtonMedium" action="' . Actions::Dismiss . '">Ok</label>
                    <frame posn="72 -73 1">
                        <quad posn="0 0 0" sizen="14 4" halign="center" valign="center" style="Bgs1" substyle="BgButton" />
                        <label posn="-5 0.1 1" sizen="6 4" halign="left" valign="center">$o$444' . $currentPageNumber . '/' . $totalPages . '</label>
                        ' . $prevPage . '
                        ' . $nextPage . '
                    </frame>
                </frame>
            </manialink>
        ';
        if (is_string($logins)) $logins = array($logins);
        $commaSeparatedLogins = implode(',', $logins);
        $gbxclient->sendDisplayManialinkPageToLogin($commaSeparatedLogins, $manialink, 0, true);
    }

    /**
     * Shows a small, scalable prompt with two buttons, one for confirmation (Yes) and one for
     * cancellation (No).
     *
     * @param string $text The text to display. Must be manually broken into lines, otherwise, the
     * text will become crammed.
     * @param int $actionId The ID to use in playerManialinkPageAnswer when clicking the Yes button.
     * @param string|array $logins The login or logins to display the prompt for.
     */
    public static function showPrompt($text, $actionId, $logins)
    {
        global $gbxclient;

        $nbLines = substr_count($text, "\n") + 1;
        $textboxHeight = $nbLines * 2.5;
        $print = function($value) { return sprintf('%1.1f', $value); };
        $manialink = '
            <manialink id="' . self::Prompt . '">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="0 0 1">
                    <quad posn="0 0 0" sizen="64.8 ' . $print($textboxHeight + 12.0) . '" halign="center" valign="center" style="Bgs1" substyle="BgWindow3" />
                    <label posn="0 ' . $print(0.5 * $textboxHeight + 3.0) . ' 1" sizen="61 ' . $print($textboxHeight) . '" halign="center" valign="top" style="TextStaticSmall">' . $text . '</label>
                    <label posn="-14.9 ' . $print(-0.5 * $textboxHeight - 1.0) . ' 1" halign="center" valign="center" style="CardButtonMedium" action="' . $actionId . '">Yes</label>
                    <label posn="14.9 ' . $print(-0.5 * $textboxHeight - 1.0) . ' 1" halign="center" valign="center" style="CardButtonMedium" action="' . Actions::Dismiss . '">No</label>
                </frame>
            </manialink>
        ';
        if (is_string($logins)) $logins = array($logins);
        $commaSeparatedLogins = implode(',', $logins);
        $gbxclient->sendDisplayManialinkPageToLogin($commaSeparatedLogins, $manialink, 0, true);
    }

    /**
     * Shows a small, scalable message with larger text and no buttons.
     *
     * @param string $text The text to display. Must be manually broken into lines, otherwise, the
     * text will become crammed.
     * @param int $duration A timeout (in seconds) to hide the message. Set to 0 to show
     * permanently.
     * @param string|array $logins [Optional] The login or logins to display the prompt for. If
     * null, the message is shown for all players on the server.
     */
    public static function showMessage($text, $timeout, $logins = null)
    {
        global $gbxclient;

        $nbLines = substr_count($text, "\n") + 1;
        $textboxHeight = $nbLines * 2.5;
        $print = function($value) { return sprintf('%1.1f', $value); };
        $manialink = '
            <manialink id="' . self::Message . '">
                <format style="TextRaceChat" textsize="1.0" />
                <frame posn="0 -2 1">
                    <quad posn="0 0 0" sizen="56 ' . $print($textboxHeight + 11.5) . '" halign="center" valign="center" style="Bgs1InRace" substyle="BgWindow2" />
                    <label posn="0 1.0   1" sizen="56 ' . $print($textboxHeight) . '" halign="center" valign="center" scale="1.5" style="TextStaticSmall">$s' . $text . '</label>
                </frame>
            </manialink>
        ';
        if (is_null($logins))
        {
            $gbxclient->sendDisplayManialinkPage($manialink, $timeout * 1000, true);
        }
        else
        {
            if (is_string($logins)) $logins = array($logins);
            $commaSeparatedLogins = implode(',', $logins);
            $gbxclient->sendDisplayManialinkPageToLogin($commaSeparatedLogins, $manialink, $timeout * 1000, true);
        }
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
                return sprintf('Constant (%s per round)', pluralize($this->value, 'KO', 'KOs'));
            case self::Extra:
                return sprintf('Extra (KO per %s)', pluralize($this->value, 'player', 'players'));
            case self::Dynamic:
                return sprintf('Dynamic (aiming for %s)', pluralize($this->value, 'round', 'rounds'));
            case self::Tiebreaker:
                return sprintf('Tiebreaker (%s remaining)', pluralize($this->value, 'KO', 'KOs'));
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
     * @param int $numberOfPlayersLeft The number of players left in the knockout.
     *
     * @return int The number of KOs to be applied this round.
     */
    public function getKOsThisRound($roundNumber, $numberOfPlayersLeft)
    {
        if ($numberOfPlayersLeft <= 1)
        {
            return 0;
        }
        else
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
                    $func = $this->solveCurve($this->baseCurve(), $roundNumber, $this->value + 1, $numberOfPlayersLeft, 1);
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
            if ($sgNew !== $sg)
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
                    $nonDiscretizedCurve = $this->getNonDiscretizedScaledCurve($curve, $f);
                    if (abs($adjustedCurve($currentRound)) - $nonDiscretizedCurve($currentRound) <= 0.5)
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
 * The main runtime to be attached to the plugin manager.
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
    /** @var Results $results */
    private $results;

    // Laps state
    private $bestCPs;
    private $scheduleKo;

    // Server info
    private $isWarmup;
    private $isPodium;
    private $gameMode;
    private $serverStatus;

    // Server settings
    private $defaultVoteTimeout;
    private $defaultPointPartition;

    // Knockout settings
    private $koMultiplier;
    private $lives;
    private $openWarmup;
    private $tiebreaker;
    private $maxFalseStarts;
    private $authorSkip;
    private $koBehaviour;

    public function __construct($client)
    {
        // Due to how the plugin manager works, global variables become unset as they are out of
        // scope of classes/functions passed on to the plugin manager. However, a workaround is to
        // inject the variable in a constructor that is always called first (in other words, here)
        // and define it in global space there.
        global $gbxclient;
        $gbxclient = $client;

        $this->koMode = KnockoutMode::Normal;
        $this->playerList = new PlayerList();
        $this->scores = new Scores();
        $this->roundNumber = 0;
        $this->koStatus = KnockoutStatus::Idle;
        $this->roundStartTime = 0.0;
        $this->falseStartCount = 0;
        $this->shouldCheckForFalseStarts = false;
        $this->kosThisRound = 0;
        $this->results = null;

        $this->bestCPs = array();
        $this->scheduleKo = false;

        $this->isWarmup = false;
        $this->isPodium = false;
        $this->gameMode = -1;
        $this->serverStatus = -1;

        $this->defaultVoteTimeout = -1;
        $this->defaultPointPartition = array();

        $this->koMultiplier = new KOMultiplier();
        $this->lives = 1;
        $this->openWarmup = true;
        $this->tiebreaker = true;
        $this->maxFalseStarts = 2;
        $this->authorSkip = 7;
        $this->koBehaviour = KnockoutBehaviour::PlayDuringWarmup;
    }

    /**
     * Callback method for when TMGery is starting up.
     */
    public function onControllerStartup()
    {
        global $PlayerList;
        global $gbxclient;

        $this->isWarmup = $gbxclient->getWarmUp();
        $status = $gbxclient->getStatus();
        $this->serverStatus = $status['Code'];
        $this->isPodium = !$this->isWarmup && $this->serverStatus === ServerStatus::Finish;
        $this->gameMode = $gbxclient->getGameMode();

        // In case the plugin crashed mid-KO
        UI::hideStatusBar();
        UI::restoreDefaultScoreboard();
        forcePlay(logins($PlayerList), false);

        Chat::info(sprintf('Knockout plugin %s loaded', Version));
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
        // Make KO GUI optional together with TMGery GUI
        $playersWithHudOn = array();
        if ($login !== null)
        {
            if (isOnServer($login) && hasHudOn($login))
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
        if ($this->serverStatus === ServerStatus::Play || $this->serverStatus === ServerStatus::Finish)
        {
            $scores = $this->scores->getSortedScores();
            $nbKOs = $this->kosThisRound;
            $numberOfPlayers = $this->playerList->countPlaying();
            UI::updateScoreboard($scores, $this->gameMode, $nbKOs, $numberOfPlayers, $this->bestCPs, $login);
        }
    }

    private function announceRoundInChat($login = null)
    {
        $playersWithHudOff = array();
        if ($login !== null)
        {
            if ($this->playerList->exists($login) && !hasHudOn($login))
            {
                $playersWithHudOff = array($this->playerList->get($login));
            }
        }
        else
        {
            $playersWithHudOff = $this->getPlayersWithHudOff();
        }
        if (count($playersWithHudOff) > 0)
        {
            $printPlayerStatus = function() use($playersWithHudOff)
            {
                foreach ($playersWithHudOff as $player)
                {
                    Chat::info(
                        sprintf('Status: %s', PlayerStatus::output($player['Status'])),
                        $player['Login']
                    );
                }
            };
            $logins = logins($playersWithHudOff);
            $printKoStatus = function($players, $kosThisRound) use($logins)
            {
                Chat::info(
                    sprintf(
                        '%s remaining, %s this round',
                        pluralize(count($players), 'player', 'players'),
                        pluralize($kosThisRound, 'KO', 'KOs')
                    ),
                    $logins
                );
            };
            switch ($this->koStatus)
            {
                case KnockoutStatus::Warmup:
                    Chat::announce('Knockout Warmup', $logins, '$f80');
                    $printPlayerStatus();
                    break;
                case KnockoutStatus::Tiebreaker:
                    Chat::announce('Knockout Tiebreaker', $logins, '$f00');
                    $printKoStatus($this->playerList->getPlaying(), $this->kosThisRound);
                    $printPlayerStatus();
                    break;
                case KnockoutStatus::Running:
                    Chat::announce("Knockout Round $<{$this->roundNumber}$>", $logins);
                    $printKoStatus($this->playerList->getPlaying(), $this->kosThisRound);
                    $printPlayerStatus();
                    break;
                case KnockoutStatus::RestartingRound:
                case KnockoutStatus::RestartingTrack:
                case KnockoutStatus::SkippingTrack:
                case KnockoutStatus::SkippingWarmup:
                case KnockoutStatus::Starting:
                case KnockoutStatus::StartingNow:
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
     * Adjusts the points partition by awarding each finishing player with the
     * given value.
     *
     * Changing game settings has immediate effect as long as they are changed
     * by the start of the round.
     */
    private function adjustPoints()
    {
        global $gbxclient;

        $playerCount = count($this->playerList->getPlaying());
        $nbKOs = $this->kosThisRound;
        $numberOfSurvivors = $playerCount - $nbKOs;
        $scoresPartition = array_merge(
            array_fill(0, $numberOfSurvivors, 1),
            array(0)
        );
        $gbxclient->setRoundCustomPoints($scoresPartition, true);
    }

    /**
     * Starts the knockout.
     *
     * @param array $players Players to start with (result of GetPlayerList query)
     * @param bool $now True to skip the current track and start the knockout immediately.
     */
    private function start($players, $now = false)
    {
        global $gbxclient;

        $this->defaultPointPartition = $gbxclient->getRoundCustomPoints();
        $callVoteTimeout = $gbxclient->getCallVoteTimeOut();
        $this->defaultVoteTimeout = $callVoteTimeout['NextValue'];
        $gbxclient->setCallVoteTimeOut(0);
        $this->playerList->addAll($players, PlayerStatus::Playing, $this->lives);
        forcePlay(logins($this->playerList->getAll()), true);
        if ($now)
        {
            $this->koStatus = KnockoutStatus::StartingNow; // Will be set to Running in onEndRound
            if ($this->isPodium)
            {
                Chat::announce('Knockout starting!');
                Log::information('Knockout starting');
                $this->hudReminder();
                $this->results = new Results();
            }
            else
            {
                $gbxclient->nextChallenge();
            }
        }
        elseif ($this->isPodium)
        {
            $this->koStatus = KnockoutStatus::Running;
            Chat::announce('Knockout starting!');
            Log::information('Knockout starting');
            $this->hudReminder();
            $this->results = new Results();
        }
        else
        {
            $this->koStatus = KnockoutStatus::Starting;
            Chat::info('Knockout scheduled to start on the next round');
        }
    }

    private function stop()
    {
        global $gbxclient;

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
        $gbxclient->setCallVoteTimeOut($this->defaultVoteTimeout);
        $gbxclient->setRoundCustomPoints($this->defaultPointPartition);
        $this->defaultVoteTimeout = -1;
        $this->defaultPointPartition = array();
        $this->koStatus = KnockoutStatus::Idle;
        $this->results = null;
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
                $playerId = $player['PlayerId'];
                $this->playerList->add($login, $playerId, $nickname, $status, $this->lives);
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

    /**
     * Returns true if there is a live round ongoing. Will return false if the knockout is not
     * running, there is currently a warmup or the podium is displayed.
     */
    private function isLive()
    {
        return ($this->koStatus === KnockoutStatus::Running || $this->koStatus === KnockoutStatus::Tiebreaker)
            && !$this->isWarmup
            && !$this->isPodium;
    }

    private function remove($playersToRemove, $status)
    {
        foreach ($playersToRemove as $player)
        {
            $login = $player['Login'];
            if (!$this->playerList->exists($login))
            {
                $nickname = $player['NickName'];
                $playerId = $player['PlayerId'];
                $this->playerList->add($login, $playerId, $nickname, $status, 0);
            }
            else
            {
                $target = $this->playerList->get($login);
                switch ($target['Status'])
                {
                    case PlayerStatus::Playing:
                    case PlayerStatus::Shelved:
                        $this->scores->remove($login);
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

        if (count($playersToRemove) > 1 && $this->koStatus === KnockoutStatus::Tiebreaker)
        {
            $this->returnFromTiebreaker();
        }

        if ($status === PlayerStatus::KnockedOut && $this->openWarmup && $this->isWarmup)
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
            $login = $player['Login'];
            $remainingLives = $player['Lives'] + $lives;
            $this->playerList->setLives($login, max(0, $remainingLives));
            if ($remainingLives <= 0)
            {
                // Knocked out
                $this->playerList->setStatus($login, PlayerStatus::KnockedOut);
                if ($this->openWarmup && $this->isWarmup)
                {
                    $toPlay[] = $login;
                }
                else
                {
                    $toSpec[] = $login;
                    $this->scores->remove($login);
                }
            }
            elseif ($player['Lives'] <= 0)
            {
                // Reinstated
                $status = $this->koStatus === KnockoutStatus::Tiebreaker ? PlayerStatus::Shelved : PlayerStatus::Playing;
                $this->playerList->setStatus($login, $status);
                $toForcePlay[] = $login;
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

    /**
     * Removes a life from the given player. If the player loses their last life, they get knocked
     * out and their result logged.
     *
     * @return bool True if the player was knocked out as a result.
     */
    private function ko($login, $score)
    {
        global $gbxclient;

        $player = $this->playerList->get($login);
        $nickName = Text::highlight(Text::trim($player['NickName']), '');
        $isKO = $this->playerList->subtractLife($login);
        if ($isKO)
        {
            $msg = $score > 0
                ? "{$nickName} is KO by a worst place finish"
                : "{$nickName} is KO by a DNF";
            Chat::info($msg);
            if (PlayerStatus::isDisconnected($player['Status']))
            {
                $this->playerList->remove($login);
            }
            else
            {
                switch ($this->koBehaviour)
                {
                    case KnockoutBehaviour::PlayDuringWarmup:
                        $this->playerList->setStatus($login, PlayerStatus::KnockedOut);
                        forceSpec($login, true);
                        break;
                    case KnockoutBehaviour::ForceSpec:
                        $this->playerList->setStatus($login, PlayerStatus::KnockedOutAndSpectating);
                        forceSpec($login, true);
                        break;
                    case KnockoutBehaviour::KickUntilTop5:
                        if (count($this->playerList->getPlayingOrShelved()) > 5)
                        {
                            $gbxclient->kick($login, 'You have been knocked out');
                        }
                        break;
                }
                if ($this->gameMode === GameMode::Laps)
                {
                    $this->scores->remove($login);
                }
            }
            $this->results->insert($login, $nickName, $this->roundNumber, $score);
        }
        else
        {
            $lives = $player['Lives'] - 1;
            $msg = $score > 0
                ? "{$nickName} lost a life by a worst place finish ({$lives} remaining)"
                : "{$nickName} lost a life by a DNF ({$lives} remaining)";
            Chat::info($msg);
        }
        $this->updateStatusBar($login);
        return $isKO;
    }

    /**
     * Recursive function that KOs the last player in the scores array until there are no more KOs,
     * or a tiebreaker is detected
     *
     * @return bool|array True if KOs were performed successfully, false if there are not enough
     * players to KO, or, if a tiebreaker is detected, an array consisting of two elements;
     * `TiedPlayers`, an array with logins of tied players; and `KOsRemaining`, an integer
     * indicating how many KOs that are yet to be performed.
     */
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
                $this->scores->submitScore($login, $player['PlayerId'], $player['NickName'], Scores::DidNotFinish, 0);
            }
        }
        $scores = $this->scores->getSortedScores();
        Log::debug(sprintf('Standings after this round: %s', print_r($scores, true)));
        $nbKOs = $this->kosThisRound;
        return $this->recursiveKO($scores, $nbKOs);
    }

    /**
     * Skips the upcoming map(s) until the author is not present in the knockout.
     */
    private function replaceNextTrackIfNeeded()
    {
        global $gbxclient;

        $nbSkips = 0;
        $nextChallenge = $gbxclient->getNextChallengeInfo();
        $authorIsStillIn = $this->playerList->hasStatus($nextChallenge['Author'], PlayerStatus::Playing);

        while ($authorIsStillIn && $nbSkips < AuthorSkipLimit)
        {
            $nextAuthor = $this->playerList->get($nextChallenge['Author']);
            // 'NextChallenge' has no effect once we're in the podium, so we'll do a dirty hack
            // instead and shift the index that points to the upcoming track
            $gbxclient->setNextChallengeIndex($gbxclient->getNextChallengeIndex() + 1);
            $nbSkips++;

            $maxSkips = AuthorSkipLimit;
            $challengeName = Text::trim($nextChallenge['Name']);
            $authorName = Text::trim($nextAuthor['NickName']);
            Chat::info(
                "Skipping $<{$challengeName}$> as $<{$authorName}$> is still participating ({$nbSkips}/{$maxSkips})"
            );
            // Then we can grab the challenge coming after that and check the author again
            $nextChallenge = $gbxclient->getNextChallengeInfo();
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
                $nickNames[] = sprintf('$<%s$>', Text::trim($player['NickName']));
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
     * Skips the warmup phase. Assumes there is a warmup currently running.
     *
     * If the game mode has changed, it will take effect as this function is called.
     */
    private function skipWarmup()
    {
        global $gbxclient;

        $gbxclient->setWarmUp(false);
    }

    /**
     * Restarts the current track.
     */
    private function restartRound()
    {
        global $gbxclient;

        // If we're in Rounds, we gotta make sure that we can restart the round. The default
        // behaviour is:
        // - 'RestartChallenge':
        //   - If round 0, restarts round
        //   - If round 1+, restarts challenge from round 0
        //   - If game mode settings have been changed, restarts challenge with warmups if any
        // - 'ForceEndRound':
        //   - If no one have finished: restarts round
        //   - If someone have finished: completes the round and starts the next one
        switch ($this->gameMode)
        {
            case GameMode::Stunts:
            case GameMode::TimeAttack:
                // No way to restart without warmup if settings have changed
                $gbxclient->restartChallenge();
                break;

            case GameMode::Cup:
            case GameMode::Laps:
            case GameMode::Rounds:
            case GameMode::Team:
                // Get scores of the current round
                $scores = $this->scores->getSortedScores();
                if (isset($scores[0]) && $scores[0]['Score'] > 0)
                {
                    // If someone have finished, the only way to restart the round (without points
                    // being applied) is to start from round 1
                    if ($this->gameMode === GameMode::Cup) $gbxclient->restartChallenge(true);
                    else $gbxclient->restartChallenge();
                }
                else
                {
                    $gbxclient->forceEndRound();
                }
                break;
        }
    }

    /**
     * Restarts the current track with warmups if any.
     */
    private function restartTrack()
    {
        global $client;
        global $gbxclient;

        // Changing some setting that takes effect on next challenge (like setting a new game mode)
        // makes RestartChallenge restart the whole challenge, including warmup
        $chattime = $gbxclient->getChatTime();
        $multicall = new Multicall($client);
        $multicall
            ->setChatTime(0)
            ->setGameMode(GameMode::Team)
            ->setGameMode($this->gameMode)
            ->restartChallenge()
            ->setChatTime($chattime['NextValue'])
            ->submit();
    }

    /**
     * Callback method for when the server changes its status.
     *
     * Throughout a challenge, the server goes through a lifecycle represented through the following
     * statuses:
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

        $this->serverStatus = $args[0];
        // Call callback method explicitly as it's not an event supported by TMGery/plugin manager
        if ($args[0] === ServerStatus::Synchronization) $this->onBeginSynchronization();
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
        global $gbxclient;
        Log::debug(sprintf('onBeginRace %s', implode(' ', $args[0])));

        $this->isPodium = false;
        $this->onTrackChange();
        $this->gameMode = $gbxclient->getGameMode();
        $this->reflectScoringWithGameMode();
    }

    /**
     * "Callback method" for when the synchronization phase (before each round) starts.
     */
    public function onBeginSynchronization()
    {
        global $gbxclient;
        Log::debug('onBeginSynchronization');

        $this->isPodium = false;
        $this->isWarmup = $gbxclient->getWarmUp();

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
                    $this->koStatus = KnockoutStatus::Warmup;
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
                    $this->koStatus = KnockoutStatus::Running;
                }
                $this->roundNumber++;
            }
            $this->announceRoundInChat();
            $this->updateKoCount();
            $this->updateStatusBar();
            $this->adjustPoints();
        }
    }

    /**
     * Callback method for when a round starts, after the synchronization phase.
     */
    public function onBeginRound()
    {
        global $gbxclient;
        Log::debug('onBeginRound');

        if ($this->koStatus !== KnockoutStatus::Idle)
        {
            if ($this->koStatus === KnockoutStatus::StartingNow)
            {
                $gbxclient->nextChallenge();
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
                    $gbxclient->forceEndRound();
                }
                elseif ($this->koStatus === KnockoutStatus::SkippingTrack)
                {
                    $gbxclient->nextChallenge();
                }
            }
        }

        $this->bestCPs = array();

        if ($this->koStatus === KnockoutStatus::Running || $this->koStatus === KnockoutStatus::Tiebreaker)
        {
            if ($this->gameMode === GameMode::Stunts || $this->gameMode === GameMode::TimeAttack || $this->gameMode === GameMode::Laps)
            {
                $this->scores->initialize($this->playerList->getPlaying());
                $this->updateScoreboard();
            }
            else
            {
                $this->shouldCheckForFalseStarts = true;
                $this->roundStartTime = microtime(true);
            }
        }
    }

    /**
     * Returns true if an unregistered player is eligible to join the knockout.
     *
     * Cases where a player can join a knockout while it's running include:
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
        elseif ($this->isPodium && $this->roundNumber <= 0)
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
        global $gbxclient;
        Log::debug(sprintf('onPlayerConnect %s', implode(' ', $args)));
        if ($this->koStatus === KnockoutStatus::Idle) return;

        $login = $args[0];
        $joinsAsSpectator = $args[1];
        $playerInfo = $gbxclient->getPlayerInfo($login);
        $didJoin = false;
        $didRejoin = false;
        // Only disconnected players who are eligible to rejoin should be matched here; see
        // onPlayerDisconnect
        if ($this->playerList->exists($login))
        {
            $player = $this->playerList->get($login);
            $this->playerList->setNickname($login, $playerInfo['NickName']);
            $this->playerList->setPlayerId($login, $playerInfo['PlayerId']);
            $logUnexpectedStatus = function() use($player)
            {
                Log::warning(sprintf('Player connected with status %s', getNameOfConstant($player['Status'], 'PlayerStatus')));
            };
            switch ($player['Status'])
            {
                case PlayerStatus::Playing:
                    $logUnexpectedStatus();
                    // Flow into next case
                case PlayerStatus::PlayingAndDisconnected:
                    $this->playerList->setStatus($login, PlayerStatus::Playing);
                    forcePlay($login, true);
                    $didRejoin = true;
                    break;

                case PlayerStatus::Shelved:
                    $logUnexpectedStatus();
                    // Flow into next case
                case PlayerStatus::ShelvedAndDisconnected:
                    $this->playerList->setStatus($login, PlayerStatus::Shelved);
                    forceSpec($login, true);
                    $didRejoin = true;
                    break;

                case PlayerStatus::OptingOut:
                    forceSpec($login, true);
                    break;

                default:
                    $logUnexpectedStatus();
                    forceSpec($login, true);
                    break;
            }
        }
        elseif ($this->isEligibleToJoin())
        {
            $this->playerList->add($playerInfo['Login'], $playerInfo['PlayerId'], $playerInfo['NickName'], PlayerStatus::Playing, $this->lives);
            forcePlay($login, true);
            $this->updateKoCount();
            $didJoin = true;
        }
        else
        {
            $this->playerList->add(
                $playerInfo['Login'],
                $playerInfo['PlayerId'],
                $playerInfo['NickName'],
                $joinsAsSpectator ? PlayerStatus::KnockedOutAndSpectating : PlayerStatus::KnockedOut,
                0
            );
            if ($this->openWarmup && $this->isWarmup && !$joinsAsSpectator)
            {
                forcePlay($login, false);
            }
            else
            {
                forceSpec($login, true);
            }
        }

        if (KnockoutStatus::isInProgress($this->koStatus) && $this->roundNumber > 0)
        {
            if ($didJoin)
            {
                $this->updateStatusBar();
                if (!$this->isWarmup && !$this->isPodium && $this->roundNumber === 1)
                {
                    Chat::info('The knockout has started! Gogogo', $login);
                }
                else
                {
                    Chat::info('The knockout is about to start! Gogogo', $login);
                }
            }
            else
            {
                $this->updateStatusBar($login);
                if ($didRejoin)
                {
                    Chat::info('You rejoined in time! Gogogo', $login);
                }
                else
                {
                    Chat::info('You have entered a match in progress', $login);
                }
            }
            $this->announceRoundInChat($login);
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
                $this->scores->submitScore($login, $player['PlayerId'], $player['NickName'], Scores::DidNotFinish, 0);
                $this->updateScoreboard();
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
        if (!$this->isLive()) return;

        $playerId = $args[0];
        $login = $args[1];
        $timeOrScore = $args[2];
        $cpIndex = $args[4];
        $playerObj = $this->playerList->get($login);
        $nickName = $playerObj['NickName'];

        switch ($this->gameMode)
        {
            case GameMode::Stunts:
                $this->bestCPs[$cpIndex] = max($this->bestCPs[$cpIndex], $timeOrScore);
                break;

            default:
                $this->bestCPs[$cpIndex] = min($this->bestCPs[$cpIndex], $timeOrScore);
                break;
        }

        if ($this->gameMode === GameMode::Laps)
        {
            $this->scores->submitScore($login, $playerId, $nickName, $timeOrScore, $cpIndex, false);
            $scores = $this->scores->getSortedScores();
            $length = count($scores);

            // Once this becomes true, every subsequent player is knocked out
            $shouldKnockOutPlayersBelow = false;

            // Check for each player if they have reached enough laps to KO those below
            // Example with 10 players and 5 laps:
            // - 1st-4th: no KOs
            // - 5th: KO scheduled when completing lap 5
            // - 6th: KO scheduled when completing lap 4
            // - 7th: KO scheduled when completing lap 3
            // - 8th: KO scheduled when completing lap 2
            // - 9th: KO scheduled when completing lap 1
            for ($i = 0; $i < $length; $i += 1)
            {
                $score = $scores[$i];

                if (true /* If we know everyone is in next lap and we can KO immediately */)
                {
                    $this->ko($login, $this->scores->get($login));
                    $this->scheduleKo = false;
                }
                else
                {
                    $this->scheduleKo = microtime(true) + 1;
                }
            }
            if (true /* If only one player remain */)
            {
                $leader = array_pop($remainingPlayers);
                $this->results->insert($leader['Login'], $leader['NickName'], $this->roundNumber, $scores[0]['Score']);
                Chat::info(sprintf("$<%s$> is the Champ!", Text::trim($leader['NickName'])));
                $this->results->export();
                $this->stop();
                Log::information('Knockout completed');
            }
            else
            {
                $this->updateScoreboard();
            }
        }
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
        global $gbxclient;
        Log::debug(sprintf('onPlayerFinish %s', implode(' ', $args)));
        if (!$this->isLive()) return;

        $login = $args[1];
        $timeOrScore = $args[2];

        // Check if it's the first player to retire and whether a false start can be
        // considered
        if ($this->shouldCheckForFalseStarts
            && $this->gameMode !== GameMode::Stunts
            && $this->gameMode !== GameMode::TimeAttack
            && $timeOrScore === 0
            && $this->falseStartCount < $this->maxFalseStarts)
        {
            // Must be within 2.5 seconds of the start of the round
            $currentTime = microtime(true);
            if ($currentTime - $this->roundStartTime <= 2.5)
            {
                // Must be a player in the knockout who retires
                if ($this->playerList->hasStatus($login, PlayerStatus::Playing))
                {
                    $this->koStatus = KnockoutStatus::RestartingRound;
                    $this->falseStartCount++;
                    $gbxclient->forceEndRound();
                    $text = "False start! Restarting the round... ({$this->falseStartCount}/{$this->maxFalseStarts})";
                    Chat::info($text);
                    UI::showMessage($text, 5);
                    return;
                }
            }
        }
        $this->shouldCheckForFalseStarts = false;

        if (($this->serverStatus === ServerStatus::Play || $this->serverStatus === ServerStatus::Finish)
            && $this->playerList->hasStatus($login, PlayerStatus::Playing))
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

                    default:
                        $timeOrScore = Scores::DidNotFinish;
                        break;
                }
            }
            $this->scores->submitScore($login, $playerObj['PlayerId'], $playerObj['NickName'], $timeOrScore, $this->nbCheckpoints * $this->nbLaps);
            $this->updateScoreboard();
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
                case PlayerStatus::OptingOut:
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
                break;

            case KnockoutStatus::RestartingRound:
            case KnockoutStatus::RestartingTrack:
            case KnockoutStatus::SkippingTrack:
                $this->roundNumber--;
                break;

            case KnockoutStatus::Starting:
            case KnockoutStatus::StartingNow:
                Chat::announce('Knockout starting!');
                Log::information('Knockout starting');
                $this->hudReminder();
                $this->koStatus = KnockoutStatus::Running;
                $this->results = new Results();
                break;

            case KnockoutStatus::Warmup:
            case KnockoutStatus::SkippingWarmup:
                $this->roundNumber--;
                break;

            case KnockoutStatus::Running:
            case KnockoutStatus::Tiebreaker:
                if ($this->gameMode === GameMode::Laps) return;
                $scores = $this->scores->getSortedScores();
                $noOneFinished = true;
                foreach ($scores as $score)
                {
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
                    $this->results->export();
                    $this->stop();
                    Log::information('Knockout completed with no winner');
                }
                elseif (count($playersInTheKO) === 1)
                {
                    $winner = array_pop($playersInTheKO);
                    $this->results->insert($winner['Login'], $winner['NickName'], $this->roundNumber, $scores[0]['Score']);
                    Chat::info(sprintf("$<%s$> is the Champ!", Text::trim($winner['NickName'])));
                    $this->results->export();
                    $this->stop();
                    Log::information('Knockout completed');
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
                            $this->results->export();
                            $this->stop();
                            Log::information('Knockout completed with no winner');
                        }
                        elseif (count($remainingPlayers) === 1)
                        {
                            $winner = array_pop($remainingPlayers);
                            $this->results->insert($winner['Login'], $winner['NickName'], $this->roundNumber, $scores[0]['Score']);
                            Chat::info(sprintf("$<%s$> is the Champ!", Text::trim($winner['NickName'])));
                            $this->results->export();
                            $this->stop();
                            Log::information('Knockout completed');
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
                        }
                    }
                }
                break;
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
                    $playerCount = count($this->playerList->getPlaying());
                    if ($playerCount <= $this->authorSkip)
                    {
                        $this->replaceNextTrackIfNeeded();
                    }
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
                break;
        }
    }

    /**
     * Callback method for the main loop, called continuously.
     */
    public function onMainLoop()
    {
        if (!$this->isLive()) return;

        if ($this->gameMode === GameMode::Laps && $this->scheduleKo !== false)
        {
            // If it's been a second since a KO was issued, start knocking out bottom players
            $now = microtime(true);
            if ($now > $this->scheduleKo)
            {
                $index = $this->scheduleKoBy;
                $scoresGettingKnockedOut = array_reverse(array_slice($this->scores->getSortedScores(), $index));
                foreach ($scoresGettingKnockedOut as $score)
                {
                    $this->ko($score['Login'], $score['Score']);
                }
                $this->scheduleKo = false;
            }
        }
    }

    private function printSettings()
    {
        $printBool = function($bool)
        {
            return $bool ? 'on' : 'off';
        };
        $settings = array(
            sprintf('KO mode: $<%s$>', getNameOfConstant($this->koMode, 'KnockoutMode')),
            sprintf('KO multiplier: $<%s$>', $this->koMultiplier->toString()),
            sprintf('Lives: $<%d$>', $this->lives),
            sprintf('Open warmup: $<%s$>', $printBool($this->openWarmup)),
            sprintf('Tiebreakers: $<%s$>', $printBool($this->tiebreaker)),
            sprintf('False starts: $<%s$>', ($this->maxFalseStarts === 0 ? 'off' : var_export($this->maxFalseStarts, true))),
            sprintf('Author skip: $<%s$>', ($this->authorSkip < 2 ? 'off' : 'for top ' . var_export($this->authorSkip, true)))
        );
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
     * Command to start a knockout if it's not running. Called with admin privileges; arguments are
     * not validated.
     *
     * Syntax: `/ko start [now]`
     */
    private function cliStart($args, $onError, $issuerLogin)
    {
        global $gbxclient;

        if ($this->koStatus !== KnockoutStatus::Idle)
        {
            $onError('There is already a knockout in progress');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko start$> or $</ko start now$>)');
        }
        elseif (!isset($args[1]) || strtolower($args[1]) === 'now')
        {
            $mode = $gbxclient->getGameMode();
            $players = $gbxclient->getPlayerList(255, 0, StructVersion::Forever);
            if ($mode === GameMode::Team)
            {
                $onError('Knockout does not work in Team mode');
            }
            elseif ($mode === GameMode::Cup)
            {
                $onError('Knockout does not work in Cup mode');
            }
            else
            {
                $this->start($players, isset($args[1]) && $args[1] === 'now');
                Chat::info2('Knockout starting with the following settings:', $issuerLogin);
                Chat::info2($this->printSettings(), $issuerLogin);
            }
        }
        else
        {
            $onError(sprintf(
                'Syntax error: unexpected argument $<%s$> (expected $</ko start$> or $</ko start now$>)',
                Text::sanitize($args[1])
            ));
        }
    }

    /**
     * Command to stop a knockout in progress. Called with admin privileges; arguments are not
     * validated.
     *
     * Syntax: `/ko stop`
     */
    private function cliStop($args, $onError)
    {
        if (isset($args[1]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko stop$>)');
        }
        elseif ($this->koStatus === KnockoutStatus::Idle)
        {
            $onError('The knockout must be running before this command can be used');
        }
        else
        {
            if ($this->roundNumber > 0)
            {
                $remainingPlayers = $this->playerList->getPlayingOrShelved();
                while (count($remainingPlayers) > 0)
                {
                    $player = array_pop($remainingPlayers);
                    $this->results->insert($player['Login'], $player['NickName'], $this->roundNumber, 0);
                }
                $this->results->export();
            }
            $this->stop();
            UI::restoreDefaultScoreboard();
            $this->koStatus = KnockoutStatus::Idle;
            Chat::info('Knockout has been stopped');
            Log::information('Knockout stopped by an admin');
        }
    }

    /**
     * Command to skip the current track or the current warmup. Called with admin privileges;
     * arguments are not validated.
     *
     * Syntax: `/ko skip [warmup]`
     */
    private function cliSkip($args, $onError)
    {
        global $gbxclient;

        if ($this->koStatus === KnockoutStatus::Idle)
        {
            $onError('The knockout must be running before this command can be used');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko skip$> or $</ko skip warmup$>)');
        }
        elseif (!isset($args[1]))
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
            $gbxclient->nextChallenge();
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
            $onError(sprintf(
                'Syntax error: unexpected argument $<%s$> (expected $</ko skip$> or $</ko skip warmup$>)',
                Text::sanitize($args[1])
            ));
        }
    }

    /**
     * Command to restart the current track or the current warmup. Called with admin privileges;
     * arguments are not validated.
     *
     * Syntax: `/ko restart [warmup]`
     */
    private function cliRestart($args, $onError)
    {
        if ($this->koStatus === KnockoutStatus::Idle)
        {
            $onError('The knockout must be running before this command can be used');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko restart$> or $</ko restart warmup$>)');
        }
        elseif (!isset($args[1]))
        {
            if ($this->koStatus === KnockoutStatus::Tiebreaker)
            {
                $this->scores->reset();
            }
            elseif ($this->koStatus !== KnockoutStatus::Starting && $this->koStatus !== KnockoutStatus::StartingNow)
            {
                $this->koStatus = KnockoutStatus::RestartingRound;
                $this->updateStatusBar();
            }
            $this->restartRound();
            $text = 'Restarting the current round';
            Chat::info($text);
            if ($this->gameMode === GameMode::Rounds || $this->gameMode === GameMode::Team)
            {
                UI::showMessage($text, 5);
            }
        }
        elseif (strtolower($args[1]) === 'warmup')
        {
            if ($this->koStatus === KnockoutStatus::Tiebreaker)
            {
                $this->returnFromTiebreaker();
            }
            if ($this->koStatus !== KnockoutStatus::Starting && $this->koStatus !== KnockoutStatus::StartingNow)
            {
                $this->koStatus = KnockoutStatus::RestartingTrack;
                $this->updateStatusBar();
            }
            $this->restartTrack();
            Chat::info('Restarting the track');
        }
        else
        {
            $onError(sprintf(
                'Syntax error: unexpected argument $<%s$> (expected $</ko restart$> or $</ko restart warmup$>)',
                Text::sanitize($args[1])
            ));
        }
    }

    /**
     * Command to add a player to the knockout. Called with admin privileges; arguments are not
     * validated.
     *
     * Syntax: `/ko add (<login> | *)`
     */
    private function cliAdd($args, $onError)
    {
        if ($this->koStatus === KnockoutStatus::Idle)
        {
            $onError('The knockout must be running before this command can be used');
        }
        elseif (!isset($args[1]))
        {
            $onError('Syntax error: expected an argument (usage: $</ko add (<login> | *)$>)');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko add (<login> | *)$>)');
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
                $onError(sprintf('Error: login $<%s$> could not be found', Text::sanitize($args[1])));
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
                    $onError(sprintf('$<%s$> is already playing', Text::sanitize($args[1])));
                }
            }
            else
            {
                $this->add($playersToAdd);
                if ($args[1] === '*')
                {
                    Chat::info('All players have been added to the knockout');
                }
                else
                {
                    Chat::info(sprintf('$<%s$> has been added to the knockout', Text::trim($playersToAdd[0]['NickName'])));
                }
            }
        }
    }

    /**
     * Commands to remove a player from the knockout. Called with admin privileges; arguments are
     * not validated.
     *
     * Syntax:
     * - `/ko remove (<login> | *)`
     * - `/ko spec (<login> | *)`
     */
    private function cliRemove($args, $onError, $issuerLogin)
    {
        if ($this->koStatus === KnockoutStatus::Idle)
        {
            $onError('The knockout must be running before this command can be used');
        }
        elseif (!isset($args[1]))
        {
            $onError('Syntax error: expected an argument (usage: $</ko remove (<login> | *)$>)');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko remove (<login> | *)$>)');
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
                $onError(sprintf('Error: login $<%s$> could not be found', Text::sanitize($args[1])));
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
                        $onError(sprintf('$<%s$> is already knocked out', Text::sanitize($args[1])));
                    }
                }
                else
                {
                    $this->remove($playersToRemove, PlayerStatus::KnockedOut);
                    if ($args[1] === '*')
                    {
                        Chat::info('All players have been removed from the knockout');
                    }
                    else
                    {
                        if ($playersToRemove[0]['Status'] === PlayerStatus::KnockedOutAndSpectating)
                        {
                            Chat::info(sprintf('$<%s$> has been moved from spectating status to knocked out status', $playersToRemove[0]['NickName']));
                        }
                        else
                        {
                            Chat::info(sprintf('$<%s$> has been removed from the knockout', $playersToRemove[0]['NickName']));
                        }
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
                        $onError(sprintf('$<%s$> is already spectating', Text::sanitize($args[1])));
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
                        if ($playersToRemove[0]['Status'] === PlayerStatus::KnockedOut)
                        {
                            Chat::info(sprintf('$<%s$> has been moved from knocked out status to spectator status', $playersToRemove[0]['NickName']));
                        }
                        else
                        {
                            Chat::info(sprintf('$<%s$> has been removed from the knockout', $playersToRemove[0]['NickName']));
                        }
                    }
                }
            }
        }
    }

    /**
     * Command to view or set the number of lives for given players. Called with admin privileges;
     * arguments are not validated.
     *
     * Syntax: `/ko lives (<login> | *) [[+ | -]<lives>]`
     */
    private function cliLives($args, $onError, $issuerLogin)
    {
        if (!isset($args[1]))
        {
            $onError('Syntax error: expected an argument (usage: $</ko lives (<login> | *) [[+ | -]<lives>]$>)');
        }
        elseif (isset($args[3]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko lives (<login> | *) [[+ | -]<lives>]$>)');
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
                $onError(sprintf('Error: login $<%s$> could not be found', Text::sanitize($args[1])));
                return;
            }

            if (!isset($args[2]))
            {
                // Display
                if ($this->koStatus === KnockoutStatus::Idle)
                {
                    $onError('The knockout must be running before this command can be used');
                }
                else
                {
                    $msg = implode(', ', array_map(
                        function ($player) { return sprintf('$<%s$> (%s)', $player['NickName'], $player['Lives']); },
                        $playersToUpdate
                    ));
                    Chat::info2($msg, $issuerLogin);
                }
            }
            elseif (!is_numeric($args[2]))
            {
                $onError(sprintf('Error: argument $<%s$> is not a number', Text::sanitize($args[2])));
            }
            elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
            {
                $onError(sprintf('Error: floating point numbers ($<%s$>) are not supported', $args[2]));
            }
            else
            {
                $sign = substr($args[2], 0, 1);
                $value = (int) $args[2];
                $livesStr = pluralize(abs($value), 'life', 'lives');
                if ($value === 0)
                {
                    $onError(sprintf('Error: argument $<%d$> must be a non-zero value', $value));
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
                            Chat::info(sprintf('All players have been %s %s', $actionStr, $livesStr));
                        }
                        else
                        {
                            $actionStr = $sign === '+' ? 'increased' : 'decreased';
                            Chat::info(sprintf('Lives per player has been %s by %d (is now %d)', $actionStr, abs($value), $this->lives));
                        }
                    }
                    else
                    {
                        $target = $this->playerList->get($args[1]);
                        Chat::info(sprintf('$<%s$> has been %s %s (currently at %d)', $target['NickName'], $actionStr, $livesStr, $target['Lives']));
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
                            Chat::info(sprintf('All players have now %s', $livesStr));
                        }
                        else
                        {
                            Chat::info(sprintf('Lives per player has been set to %d', $value));
                        }
                    }
                    else
                    {
                        Chat::info(sprintf('$<%s$> has now %s', $playersToUpdate[0]['NickName'], $livesStr));
                    }
                }
            }
        }
    }

    /**
     * Command to set the KO multiplier. Called with admin privileges; arguments are not validated.
     *
     * Syntax: `/ko multi (constant <kos> | extra <per_x_players> | dynamic <total_rounds> | none)`
     */
    private function cliMulti($args, $onError)
    {
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
                        $onError('Syntax error: too many arguments (usage: $</ko multi none$>)');
                    }
                    else
                    {
                        $this->koMultiplier->set(KOMultiplier::None, null);
                        Chat::info(sprintf('KO multiplier set to $<%s$>', $this->koMultiplier->toString()));
                        $this->onKoStatusUpdate();
                    }
                    break;

                case 'constant':
                    if (!isset($args[2]))
                    {
                        $onError('Syntax error: expected an argument (usage: $</ko multi constant <x KOs per round>$>)');
                    }
                    elseif (isset($args[3]))
                    {
                        $onError('Syntax error: too many arguments (usage: $</ko multi constant <x KOs per round>$>)');
                    }
                    elseif (!is_numeric($args[2]))
                    {
                        $onError(sprintf('Syntax error: argument $<%s$> must be a number (usage: $</ko multi constant <x KOs per round>$>)', Text::sanitize($args[2])));
                    }
                    elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
                    {
                        $onError(sprintf('Error: floating point numbers ($<%s$>) are not supported', $args[2]));
                    }
                    else
                    {
                        $val = (int) $args[2];
                        if ($val <= 0)
                        {
                            $onError(sprintf('Syntax error: argument $<%d$> must be greater than 0 (usage: $</ko multi constant <x KOs per round>$>)', $val));
                        }
                        else
                        {
                            $this->koMultiplier->set(KOMultiplier::Constant, $val);
                            Chat::info(sprintf('KO multiplier set to $<%s$>', $this->koMultiplier->toString()));
                            $this->onKoStatusUpdate();
                        }
                    }
                    break;

                case 'extra':
                    if (!isset($args[2]))
                    {
                        $onError('Syntax error: expected an argument (usage: $</ko multi extra <per X players>$>)');
                    }
                    elseif (isset($args[3]))
                    {
                        $onError('Syntax error: too many arguments (usage: $</ko multi extra <per X players>$>)');
                    }
                    elseif (!is_numeric($args[2]))
                    {
                        $onError(sprintf('Syntax error: argument $<%s$> must be a number (usage: $</ko multi extra <per x players>$>)', Text::sanitize($args[2])));
                    }
                    elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
                    {
                        $onError(sprintf('Error: floating point numbers ($<%s$>) are not supported', $args[2]));
                    }
                    else
                    {
                        $val = (int) $args[2];
                        if ($val <= 0)
                        {
                            $onError(sprintf('Syntax error: argument $<%d$> must be greater than 0 (usage: $</ko multi extra <per x players>$>)', $val));
                        }
                        else
                        {
                            $this->koMultiplier->set(KOMultiplier::Extra, $val);
                            Chat::info(sprintf('KO multiplier set to $<%s$>', $this->koMultiplier->toString()));
                            $this->onKoStatusUpdate();
                        }
                    }
                    break;

                case 'dynamic':
                    if (!isset($args[2]))
                    {
                        $onError('Syntax error: expected an argument (usage: $</ko multi dynamic <X rounds>$>)');
                    }
                    elseif (isset($args[3]))
                    {
                        $onError('Syntax error: too many arguments (usage: $</ko multi dynamic <X rounds>$>)');
                    }
                    elseif (!is_numeric($args[2]))
                    {
                        $onError(sprintf('Syntax error: argument $<%s$> must be a number (usage: $</ko multi dynamic <x rounds>$>)', Text::sanitize($args[2])));
                    }
                    elseif (str_contains($args[2], '.') || str_contains($args[2], ','))
                    {
                        $onError(sprintf('Error: floating point numbers ($<%s$>) are not supported', $args[2]));
                    }
                    else
                    {
                        $val = (int) $args[2];
                        if ($val <= 0)
                        {
                            $onError(sprintf('Syntax error: argument $<%d$> must be greater than 0 (usage: $</ko multi dynamic <x rounds>$>)', $val));
                        }
                        else
                        {
                            $this->koMultiplier->set(KOMultiplier::Dynamic, $val);
                            Chat::info(sprintf('KO multiplier set to $<%s$>', $this->koMultiplier->toString()));
                            $this->onKoStatusUpdate();
                        }
                    }
                    break;

                default:
                    if (isset($args[1]))
                    {
                        $onError(sprintf('Syntax error: unexpected argument $<%s$> (expected $<constant$>, $<extra$> or $<none$>)', Text::sanitize($args[1])));
                    }
                    else
                    {
                        $onError('Syntax error: expected an argument (usage: $</ko multi (constant <x KOs per round> | extra <per x players> | dynamic <x rounds> | none)$>)');
                    }
                    break;
            }
        }
    }

    /**
     * Command to set the knockout behaviour. Called with admin privileges; arguments are not
     * validated.
     *
     * Syntax: `/ko (behavior | behaviour) (playwarmup | forcespec | kick)`
     */
    private function cliKoBehaviour($args, $onError)
    {
        if (!isset($args[1]))
        {
            $onError('Syntax error: expected an argument (usage: $</ko behaviour (playwarmup | forcespec | kick)$>)');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko behaviour (playwarmup | forcespec | kick)$>)');
        }
        elseif ($args[1] === 'playwarmup')
        {
            $this->koBehaviour = KnockoutBehaviour::PlayDuringWarmup;
            Chat::info('Knockout behaviour has been set to $<Play during warmup$>');
        }
        elseif ($args[1] === 'forcespec')
        {
            $this->koBehaviour = KnockoutBehaviour::ForceSpec;
            Chat::info('Knockout behaviour has been set to $<Force spec$>');
        }
        elseif ($args[1] === 'kick')
        {
            $this->koBehaviour = KnockoutBehaviour::KickUntilTop5;
            Chat::info('Knockout behaviour has been set to $<Kick$>');
        }
        else
        {
            $onError(sprintf('Error: unexpected argument $<%s$> (expected $<playwarmup$>, $<forcespec$> or $<kick$>)', Text::sanitize($args[1])));
        }
    }

    /**
     * Command to enable or disable open warmups. Called with admin privileges; arguments are not
     * validated.
     *
     * Syntax: `/ko openwarmup (on | off)`
     */
    private function cliOpenwarmup($args, $onError)
    {
        if (!isset($args[1]))
        {
            $onError('Syntax error: expected an argument (usage: $</ko openwarmup (on | off)$>)');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko openwarmup (on | off)$>)');
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
            $onError(sprintf('Error: unexpected argument $<%s$> (expected $<on$> or $<off$>)', Text::sanitize($args[1])));
        }
    }

    /**
     * Command to set the domain of which false starts should be activated. Called with admin
     * privileges; arguments are not validated.
     *
     * Syntax: `/ko falsestart <max_tries>`
     */
    private function cliFalsestart($args, $onError)
    {
        if (!isset($args[1]))
        {
            $onError('Syntax error: expected an argument (usage: $</ko falsestart <max tries>$>)');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko falsestart <max tries>$>)');
        }
        elseif (!is_numeric($args[1]))
        {
            $onError(sprintf('Error: argument $<%s$> is not a number', Text::sanitize($args[1])));
        }
        elseif (str_contains($args[1], '.') || str_contains($args[1], ','))
        {
            $onError(sprintf('Error: floating point numbers ($<%s$>) are not supported', $args[1]));
        }
        else
        {
            $val = (int) $args[1];
            if ($val < 0)
            {
                $onError(sprintf('Error: argument $<%d$> must be 0 or greater', $val));
            }
            else
            {
                $prev = $this->maxFalseStarts;
                $this->maxFalseStarts = $val;
                $msg = $val === 0
                    ? sprintf('False start detection have been disabled (previously set to $<%d$>)', $prev)
                    : sprintf('False start limit has been set to $<%d$> (previously $<%d$>)', $val, $prev);
                Chat::info($msg);
            }
        }
    }

    /**
     * Command to enable or disable tiebreakers. Called with admin privileges; arguments are not
     * validated.
     *
     * Syntax: `/ko falsestart <max_tries>`
     */
    private function cliTiebreaker($args, $onError)
    {
        if (!isset($args[1]))
        {
            $onError('Syntax error: expected an argument (usage: $</ko tiebreaker (on | off)>$>)');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko tiebreaker (on | off)$>)');
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
            $onError(sprintf('Error: unexpected argument $<%s$> (expected $<on$> or $<off$>)', Text::sanitize($args[1])));
        }
    }

    /**
     * Command to set the domain of which author skips should be activated. Called with admin privileges; arguments are not
     * validated.
     *
     * Syntax: `/ko authorskip <for_top_x_players>`
     */
    private function cliAuthorskip($args, $onError)
    {
        if (!isset($args[1]))
        {
            $onError('Syntax error: expected an argument (usage: $</ko authorskip <for top X players>$>)');
        }
        elseif (isset($args[2]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko authorskip <for top X players>$>)');
        }
        elseif (!is_numeric($args[1]))
        {
            $onError(sprintf('Error: argument $<%s$> is not a number', Text::sanitize($args[1])));
        }
        elseif (str_contains($args[1], '.') || str_contains($args[1], ','))
        {
            $onError(sprintf('Error: floating point numbers ($<%s$>) are not supported', $args[1]));
        }
        else
        {
            $val = (int) $args[1];
            if ($val < 0)
            {
                $onError(sprintf('Error: argument $<%d$> must be 0 or greater', $val));
            }
            else
            {
                $prev = $this->authorSkip;
                $this->authorSkip = $val;
                $msg = $val === 0
                    ? sprintf('Author skips have been disabled (previously set to $<%d$>)', $prev)
                    : sprintf('Author skip has been enabled for top $<%d$> (previously $<%d$>)', $val, $prev);
                Chat::info($msg);
            }
        }
    }

    /**
     * Command to view knockout settings. Called with admin privileges; arguments are not validated.
     *
     * Syntax: `/ko settings`
     */
    private function cliSettings($args, $onError, $issuerLogin)
    {
        if (isset($args[1]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko settings$>)');
        }
        else
        {
            Chat::info2($this->printSettings(), $issuerLogin);
        }
    }

    /**
     * Command to view debugging info. Called with admin privileges; arguments are not validated.
     *
     * Syntax: `/ko status`
     */
    private function cliStatus($args, $onError, $issuerLogin)
    {
        if (isset($args[1]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko status$>)');
        }
        else
        {
            $playerList = array_map(
                function($player)
                {
                    return sprintf('%s (%s)',
                        getNameOfConstant($player['Status'], 'PlayerStatus'),
                        pluralize($player['Lives'], 'life', 'lives')
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
            UI::showInfoDialog($text, $issuerLogin);
        }
    }

    /**
     * Command to view the CLI reference. Called with admin privileges; arguments are not validated.
     *
     * Syntax: `/ko help`
     */
    private function cliHelp($args, $onError, $issuerLogin)
    {
        if (isset($args[1]))
        {
            $onError('Syntax error: too many arguments (usage: $</ko help$>)');
        }
        else
        {
            $this->cliReference(1, $issuerLogin);
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
            Chat::error($msg, $issuerLogin);
        };

        if (!isadmin($issuerLogin) && !isadmin2($issuerLogin))
        {
            $onError('Access denied: you do not have the required privileges to use this command');
        }
        elseif (count($args) === 0)
        {
            $onError('Syntax error: expected an argument (see $</ko help$> for usages)');
        }
        else
        {
            switch (strtolower($args[0]))
            {
                case 'start':
                    $this->cliStart($args, $onError, $issuerLogin);
                    break;

                case 'stop':
                    $this->cliStop($args, $onError);
                    break;

                case 'skip':
                    $this->cliSkip($args, $onError);
                    break;

                case 'restart':
                    $this->cliRestart($args, $onError);
                    break;

                case 'add':
                    $this->cliAdd($args, $onError);
                    break;

                case 'remove':
                case 'spec':
                    $this->cliRemove($args, $onError, $issuerLogin);
                    break;

                case 'lives':
                    $this->cliLives($args, $onError, $issuerLogin);
                    break;

                case 'multi':
                    $this->cliMulti($args, $onError);
                    break;

                case 'behavior':
                case 'behaviour':
                    $this->cliKoBehaviour($args, $onError);
                    break;

                case 'openwarmup':
                    $this->cliOpenwarmup($args, $onError);
                    break;

                case 'falsestart':
                    $this->cliFalsestart($args, $onError);
                    break;

                case 'tiebreaker':
                    $this->cliTiebreaker($args, $onError);
                    break;

                case 'authorskip':
                    $this->cliAuthorskip($args, $onError);
                    break;

                case 'settings':
                    $this->cliSettings($args, $onError, $issuerLogin);
                    break;

                case 'status':
                    $this->cliStatus($args, $onError, $issuerLogin);
                    break;

                case 'help':
                    $this->cliHelp($args, $onError, $issuerLogin);
                    break;

                default:
                    $onError(sprintf('Syntax error: unexpected argument $<%s$> (see $</ko help$> for usages)', Text::sanitize($args[0])));
                    break;
            }
        }
    }

    private function cliReference($pageNumber, $login)
    {
        $color = Text::Info;
        $prefix = "{$color} \n\$s";
        $sep1 = "\n\n";
        $sep2 = "\n    ";
        $totalPages = 3;

        switch ($pageNumber)
        {
            case 1:
                $text = implode($sep1, array(
                    implode($sep2, array(
                        '/ko start [now]',
                        'Starts the knockout. If "now" is given, the current track will be skipped immediately.'
                    )),

                    implode($sep2, array(
                        '/ko stop',
                        'Stops the knockout with immediate effect.'
                    )),

                    implode($sep2, array(
                        '/ko skip [warmup]',
                        'Skips the current track. If "warmup" is given, only the warmup is skipped.'
                    )),

                    implode($sep2, array(
                        '/ko restart [warmup]',
                        'Restarts the current track, or the current round if in Rounds. If "warmup" is given, the track is',
                        'restarted with a warmup.'
                    )),

                    implode($sep2, array(
                        '/ko add ($ilogin$i | *)',
                        'Adds a player to the knockout. If the wildcard * is used, then everyone on the server is added.'
                    )),

                    implode($sep2, array(
                        '/ko remove ($ilogin$i | *)',
                        'Removes a player from the knockout, regardless of how many lives they have.'
                    )),

                    implode($sep2, array(
                        '/ko spec ($ilogin$i | *)',
                        'Same as /ko remove but instead puts the player into spectator status.'
                    )),

                    implode($sep2, array(
                        '/ko lives ($ilogin$i | *) [[+ | -]$ilives$i]',
                        'Displays or adjusts the number of lives to use for the knockout.'
                    ))
                ));
                UI::showMultiPageDialog(
                    Text::format("{$prefix}{$text}"),
                    $login,
                    1,
                    $totalPages,
                    null,
                    Actions::CliReferencePage2
                );
                break;

            case 2:
                $text = implode($sep1, array(
                    implode($sep2, array(
                        '/ko multi (constant $ikos$i | extra $iper_x_players$i | dynamic $itotal_rounds$i | none)',
                        'Sets the KO multiplier mode.',
                        '- Constant: x KOs per round',
                        '- Extra: +1 KO for every x\'th player',
                        '- Dynamic: Aims for a total of x rounds',
                        '- None: 1 KO per round'
                    )),

                    implode($sep2, array(
                        '/ko behaviour (playwarmup | forcespec | kick)',
                        'Determines what happens when a player gets knocked out.',
                        '- Playwarmup: Knocked out players stay on the server and may play during warmups',
                        '- Forcespec: Knocked out players are forced to spec and won\'t play during warmups',
                        '- Kick: Players are kicked from the server until top 5.'
                    )),

                    implode($sep2, array(
                        '/ko openwarmup (on | off)',
                        'Enables or disables open warmup which lets knocked out players play during warmup.'
                    )),

                    implode($sep2, array(
                        '/ko falsestart $imax_tries$i',
                        'Sets the limit for how many times the round will be restarted if someone retires before the',
                        'countdown.'
                    )),

                    implode($sep2, array(
                        '/ko tiebreaker (on | off)',
                        'Enables or disables tiebreakers, a custom mode which takes effect when multiple players tie and not',
                        'all of them would be knocked out.'
                    )),

                    implode($sep2, array(
                        '/ko authorskip $ifor_top_x_players$i',
                        'Automatically skips a track when its author is present, once a given player count has been reached.'
                    ))
                ));
                UI::showMultiPageDialog(
                    Text::format("{$prefix}{$text}"),
                    $login,
                    2,
                    $totalPages,
                    Actions::CliReferencePage1,
                    Actions::CliReferencePage3
                );
                break;

            case 3:
                $text = implode($sep1, array(
                    implode($sep2, array(
                        "/ko settings",
                        'Displays knockout settings such as multiplier, lives, open warmup, etc in the chat.'
                    )),

                    implode($sep2, array(
                        "/ko status",
                        'Shows knockout mode, knockout status, player list and scores in a dialog window.'
                    )),

                    implode($sep2, array(
                        "/ko help",
                        'Shows the list of commands.'
                    )),

                    '$4af$l[http://github.com/ManiaExchange/GeryKnockout/blob/main/docs/cli.md]CLI reference$l',

                    '$4af$l[http://github.com/ManiaExchange/GeryKnockout/blob/main/docs/user-guide.md]User guide$l'
                ));
                UI::showMultiPageDialog(
                    Text::format("{$prefix}{$text}"),
                    $login,
                    3,
                    $totalPages,
                    Actions::CliReferencePage2,
                    null
                );
                break;

            default:
                Log::warning(sprintf('Tried to retrieve non-existing page $d of CLI reference', $pageNumber));
                break;
        }
    }

    /**
     * Command to opt out if someone does not want to participate in a knockout.
     *
     * This function is called when a user sends a chat message starting with '/opt'.
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
            Chat::error('Syntax error: expected an argument (usage: $</opt out$>)', $issuerLogin);
        }
        elseif (isset($args[1]))
        {
            Chat::error('Syntax error: too many arguments (usage: $</opt out$>)', $issuerLogin);
        }
        elseif (strtolower($args[0]) === 'in')
        {
            if ($this->koStatus === KnockoutStatus::Idle)
            {
                $msg = "You can't opt in to a knockout if it's not running";
                Chat::error($msg, $issuerLogin);
            }
            else
            {
                $playerObj = $this->playerList->get($issuerLogin);
                if (PlayerStatus::isIn($playerObj['Status']) || PlayerStatus::isShelved($playerObj['Status']))
                {
                    $msg = "You are already participating in this knockout";
                    Chat::error($msg, $issuerLogin);
                }
                elseif ($playerObj['Status'] === PlayerStatus::OptingOut)
                {
                    if ($this->koStatus === KnockoutStatus::Tiebreaker)
                    {
                        $this->playerList->setStatus($issuerLogin, PlayerStatus::Shelved);
                        forceSpec($issuerLogin, true);
                    }
                    else
                    {
                        $this->playerList->setStatus($issuerLogin, PlayerStatus::Playing);
                        forcePlay($issuerLogin, true);
                    }
                    $this->onKoStatusUpdate();
                    Chat::info(sprintf('$<%s$> has opted back in to the knockout', $playerObj['NickName']));
                }
                else
                {
                    $msg = "You can no longer opt in to this knockout";
                    Chat::error($msg, $issuerLogin);
                }
            }
        }
        elseif (strtolower($args[0]) === 'out')
        {
            if ($this->koStatus === KnockoutStatus::Idle)
            {
                $msg = "You can't opt out of a knockout if it's not running";
                Chat::error($msg, $issuerLogin);
            }
            {
                $playerObj = $this->playerList->get($issuerLogin);
                if (PlayerStatus::isIn($playerObj['Status']) || PlayerStatus::isShelved($playerObj['Status']))
                {
                    $text = 'Are you sure you want to opt out of the knockout?';
                    UI::showPrompt($text, Actions::ConfirmOptOut, $issuerLogin);
                }
                else
                {
                    $msg = "You are not participating in this knockout";
                    Chat::error($msg, $issuerLogin);
                }
            }
        }
        else
        {
            $msg = sprintf('Syntax error: unexpected argument $<%s$> (expected $</opt out$>)', Text::sanitize($args[0]));
            Chat::error($msg, $issuerLogin);
        }
    }

    /**
     * Called when a player clicks on a manialink element with the action attribute being set.
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
        global $gbxclient;

        Log::debug(sprintf('playerManialinkPageAnswer %s', implode(' ', $args)));

        $playerId = $args[0];
        $login = $args[1];
        $manialinkId = $args[2];
        switch ($manialinkId)
        {
            case Actions::ToggleHUD:
                // TMGery has already changed the state of PlayerScript by now
                if (KnockoutStatus::isInProgress($this->koStatus) && $this->roundNumber > 0)
                {
                    if ($PlayerScript[$login] === '1')
                    {
                        $this->updateStatusBar($login);
                    }
                    else
                    {
                        UI::hideStatusBar($login);
                    }
                }
                break;

            case Actions::ConfirmOptOut:
                $playerObj = $this->playerList->get($login);
                if (($this->koStatus === KnockoutStatus::Running && !$this->isPodium)
                    || ($this->koStatus === KnockoutStatus::Tiebreaker && $playerObj['Status'] === PlayerStatus::Playing))
                {
                    $this->scores->set($login, $playerObj['PlayerId'], $playerObj['NickName'], Scores::DidNotFinish, 0);
                }
                else
                {
                    $this->playerList->setStatus($login, PlayerStatus::OptingOut);
                }
                forceSpec($login, true);
                $this->onKoStatusUpdate();
                Chat::info(sprintf('$<%s$> has opted out of the knockout', $playerObj['NickName']));
                break;

            case Actions::CliReferencePage1:
                $this->cliReference(1, $login);
                break;

            case Actions::CliReferencePage2:
                $this->cliReference(2, $login);
                break;

            case Actions::CliReferencePage3:
                $this->cliReference(3, $login);
                break;

            default:
                if ($manialinkId >= Actions::SpectatePlayerMin && $manialinkId <= Actions::SpectatePlayerMax)
                {
                    // Manialink ID is encoded with the target playerID (Manialink ID + Player ID)
                    $target = $manialinkId - Actions::SpectatePlayerMin;
                    $gbxclient->forceSpectatorTargetId($playerId, $target, CameraType::Unchanged);
                }
                break;
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
    public function testChatCommand($args, $issuer)
    {
        global $gbxclient;

        $login = $issuer[0];
        if (isadmin($login))
        {
            if (!$args[0])
            {
                $scores = array(
                    array(
                        'Login' => 'voyager006',
                        'PlayerId' => 251,
                        'NickName' => '$bbbVoyager$fa00$f900$f806',
                        'Checkpoint' => 2,
                        'IsFinish' => false,
                        'Score' => 30000
                    ),
                    array(
                        'Login' => 'eyebo',
                        'PlayerId' => 249,
                        'NickName' => '$fffm$09fx$000.$f90eyebo',
                        'Checkpoint' => 1,
                        'IsFinish' => false,
                        'Score' => 22000
                    ),
                );
                $bestCPs = array(10000, 20000, 30000);
                UI::updateScoreboard($scores, GameMode::Laps, 1, count($scores), $bestCPs, $login);
            }
            else
            {
                $scores = array(
                    array(
                        'Login' => 'voyager006',
                        'PlayerId' => 251,
                        'NickName' => '$bbbVoyager$fa00$f900$f806',
                        'Checkpoint' => 3,
                        'IsFinish' => true,
                        'Score' => 40000
                    ),
                    array(
                        'Login' => 'eyebo',
                        'PlayerId' => 249,
                        'NickName' => '$fffm$09fx$000.$f90eyebo',
                        'Checkpoint' => 4,
                        'IsFinish' => true,
                        'Score' => 52000
                    ),
                );
                $bestCPs = array(10000, 20000, 30000, 40000);
                UI::updateScoreboard($scores, GameMode::Laps, 1, count($scores), $bestCPs, $login);
            }
            Chat::info2('test done', $login);
        }
        else
        {
            Chat::error(" UNKNOWN COMMAND !", array($login));
        }
    }
}

$gbxclient = new Client($client);

$this->AddPlugin(new KnockoutRuntime($gbxclient));

$this->AddEvent('BeginRace', 'onBeginRace');
$this->AddEvent('BeginRound', 'onBeginRound');
$this->AddEvent('EndRace', 'onEndRace');
$this->AddEvent('EndRound', 'onEndRound');
$this->AddEvent('onStartup', 'onControllerStartup');
$this->AddEvent('onMainLoop', 'onMainLoop');
$this->AddEvent('PlayerCheckpoint', 'onPlayerCheckpoint');
$this->AddEvent('PlayerConnect', 'onPlayerConnect');
$this->AddEvent('PlayerDisconnect', 'onPlayerDisconnect');
$this->AddEvent('PlayerFinish', 'onPlayerFinish');
$this->AddEvent('PlayerInfoChanged', 'onPlayerInfoChange');
$this->AddEvent('PlayerManialinkPageAnswer', 'playerManialinkPageAnswer');
$this->AddEvent('StatusChanged', 'onStatusChange');

$this->addChatCommand('ko', true, 'adminChatCommands');
$this->addChatCommand('opt', true, 'optChatCommand');
$this->addChatCommand('test', false, 'testChatCommand');

?>
