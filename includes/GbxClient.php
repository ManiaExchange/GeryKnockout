<?php
/*
 * XML-RPC Gbx API classes for TmForever by Voyager006.
 *
 * Reflects version 2.11.26 (build 2011-02-21). Targeting PHP 5.3.
 * https://methods.xaseco.org/methodstmf.php
 */


interface TmForeverMethods
{
    /**
     * Return an array of all available XML-RPC methods on this server.
     *
     * @return array
     */
    public function listMethods();

    /**
     * Given the name of a method, return an array of legal signatures. Each signature is an array
     * of strings. The first item of each signature is the return type, and any others items are
     * parameter types.
     *
     * @param string $methodName
     *
     * @return array
     */
    public function methodSignature($methodName);

    /**
     * Given the name of a method, return a help string.
     *
     * @param string $methodName
     *
     * @return string
     */
    public function methodHelp($methodName);

    // public function multicall($calls);

    /**
     * Allow user authentication by specifying a login and a password, to gain access to the set of
     * functionalities corresponding to this authorization level.
     *
     * @param string $login
     * @param string $password
     *
     * @return bool
     */
    public function authenticate($login, $password);

    /**
     * Change the password for the specified login/user. Only available to SuperAdmin.
     *
     * @param string $login
     * @param string $password
     *
     * @return bool
     */
    public function changeAuthPassword($login, $password);

    /**
     * Allow the GameServer to call you back.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public function enableCallbacks($enabled);

    /**
     * Returns a struct with the *Name*, *Version* and *Build* of the application remotely
     * controled.
     *
     * @return array
     */
    public function getVersion();

    /**
     * Call a vote for a cmd. Only available to Admin.
     *
     * @param string $request A XML string corresponding to an XmlRpc request.
     *
     * @return bool
     */
    public function callVote($request);

    /**
     * Extended call vote. Same as CallVote, but you can additionally supply specific parameters for
     * this vote: a ratio, a time out and who is voting. Special timeout values: a timeout of '0'
     * means default, '1' means indefinite; a ratio of '-1' means default; Voters values: '0' means
     * only active players, '1' means any player, '2' is for everybody, pure spectators included.
     * Only available to Admin.
     *
     * @param string $request
     * @param float $ratio
     * @param int $timeout
     * @param int $whoIsVoting
     *
     * @return bool
     */
    public function callVoteEx($request, $ratio, $timeout, $whoIsVoting);

    /**
     * Used internally by game.
     *
     * @return bool
     */
    public function internalCallVote();

    /**
     * Cancel the current vote. Only available to Admin.
     *
     * @return bool
     */
    public function cancelVote();

    /**
     * Returns the vote currently in progress. The returned structure is {CallerLogin, CmdName,
     * CmdParam }.
     *
     * @return array
     */
    public function getCurrentCallVote();

    /**
     * Set a new timeout for waiting for votes. A zero value disables callvote. Only available to
     * Admin. Requires a challenge restart to be taken into account.
     *
     * @param int $callVoteTimeout
     *
     * @return bool
     */
    public function setCallVoteTimeOut($callVoteTimeout);

    /**
     * Get the current and next timeout for waiting for votes. The struct returned contains two
     * fields 'CurrentValue' and 'NextValue'.
     *
     * @return array
     */
    public function getCallVoteTimeOut();

    /**
     * Set a new default ratio for passing a vote. Must lie between 0 and 1. Only available to
     * Admin.
     *
     * @param float $callVoteRatio
     *
     * @return bool
     */
    public function setCallVoteRatio($callVoteRatio);

    /**
     * Get the current default ratio for passing a vote. This value lies between 0 and 1.
     *
     * @return float
     */
    public function getCallVoteRatio();

    /**
     * Set new ratios for passing specific votes. The parameter is an array of structs {string
     * Command, double Ratio}, ratio is in [0,1] or -1 for vote disabled. Only available to Admin.
     *
     * @param array $callVoteRatios
     *
     * @return bool
     */
    public function setCallVoteRatios($callVoteRatios);

    /**
     * Get the current ratios for passing votes.
     *
     * @return array
     */
    public function getCallVoteRatios();

    /**
     * Send a text message to all clients without the server login. Only available to Admin.
     *
     * @param string $text
     *
     * @return bool
     */
    public function chatSendServerMessage($text);

    /**
     * Send a localised text message to all clients without the server login, or optionally to a
     * Login (which can be a single login or a list of comma-separated logins). The parameter is an
     * array of structures {*Lang*='??', *Text*='...'}. If no matching language is found, the last
     * text in the array is used. Only available to Admin.
     *
     * @param array $mapping
     * @param string $login
     *
     * @return bool
     */
    public function chatSendServerMessageToLanguage($mapping, $login = null);

    /**
     * Send a text message without the server login to the client with the specified PlayerId. Only
     * available to Admin.
     *
     * @param string $text
     * @param int $playerId
     *
     * @return bool
     */
    public function chatSendServerMessageToId($text, $playerId);

    /**
     * Send a text message without the server login to the client with the specified login. Login
     * can be a single login or a list of comma-separated logins. Only available to Admin.
     *
     * @param string $text
     * @param int $login
     *
     * @return bool
     */
    public function chatSendServerMessageToLogin($text, $login);

    /**
     * Send a text message to all clients. Only available to Admin.
     *
     * @param string $text
     *
     * @return bool
     */
    public function chatSend($text);

    /**
     * Send a localised text message to all clients, or optionally to a Login (which can be a single
     * login or a list of comma-separated logins). The parameter is an array of structures
     * {*Lang*='??', *Text*='...'}. If no matching language is found, the last text in the array is
     * used. Only available to Admin.
     *
     * @param array $mapping
     * @param string $login
     *
     * @return bool
     */
    public function chatSendToLanguage($mapping, $login = null);

    /**
     * Send a text message to the client with the specified login. Login can be a single login or a
     * list of comma-separated logins. Only available to Admin.
     *
     * @param string $text
     * @param string $login
     *
     * @return bool
     */
    public function chatSendToLogin($text, $login);

    /**
     * Send a text message to the client with the specified PlayerId. Only available to Admin.
     *
     * @param string $text
     * @param int $playerId
     *
     * @return bool
     */
    public function chatSendToId($text, $playerId);

    /**
     * Returns the last chat lines. Maximum of 40 lines. Only available to Admin.
     *
     * @return array
     */
    public function getChatLines();

    /**
     * The chat messages are no longer dispatched to the players, they only go to the rpc callback
     * and the controller has to manually forward them. The second (optional) parameter allows all
     * messages from the server to be automatically forwarded. Only available to Admin.
     *
     * @param bool $enabled
     * @param bool $forwardMessages
     *
     * @return bool
     */
    public function chatEnableManualRouting($enabled, $forwardMessages = null);

    /**
     * (Text, SenderLogin, DestLogin) Send a text message to the specified DestLogin (or everybody
     * if empty) on behalf of SenderLogin. DestLogin can be a single login or a list of
     * comma-separated logins. Only available if manual routing is enabled. Only available to Admin.
     *
     * @param string $text
     * @param string $senderLogin
     * @param string $destLogin
     *
     * @return bool
     */
    public function chatForwardToLogin($text, $senderLogin, $destLogin);

    /**
     * Display a notice on all clients. The parameters are the text message to display, and the
     * login of the avatar to display next to it (or '' for no avatar), and an optional 'max
     * duration' in seconds (default: 3). Only available to Admin.
     *
     * @param string $text
     * @param string $avatarLogin
     * @param int $maxDuration
     *
     * @return bool
     */
    public function sendNotice($text, $avatarLogin, $maxDuration = null);

    /**
     * Display a notice on the client with the specified UId. The parameters are the Uid of the
     * client to whom the notice is sent, the text message to display, and the UId of the avatar to
     * display next to it (or '255' for no avatar), and an optional 'max duration' in seconds
     * (default: 3). Only available to Admin.
     *
     * @param int $clientUid
     * @param string $text
     * @param int $avatarUid
     * @param int $maxDuration
     *
     * @return bool
     */
    public function sendNoticeToId($clientUid, $text, $avatarUid, $maxDuration = null);

    /**
     * Display a notice on the client with the specified UId. The parameters are the Uid of the
     * client to whom the notice is sent, the text message to display, and the UId of the avatar to
     * display next to it (or '255' for no avatar), and an optional 'max duration' in seconds
     * (default: 3). Only available to Admin.
     *
     * @param string $clientLogin
     * @param string $text
     * @param string $avatarLogin
     * @param int $maxDuration
     *
     * @return bool
     */
    public function sendNoticeToLogin($clientLogin, $text, $avatarLogin, $maxDuration = null);

    /**
     * Display a manialink page on all clients. The parameters are the xml description of the page
     * to display, a timeout to autohide it (0 = permanent), and a boolean to indicate whether the
     * page must be hidden as soon as the user clicks on a page option. Only available to Admin.
     *
     * @param string $manialink
     * @param int $timeout
     * @param bool $hideOnClick
     *
     * @return bool
     */
    public function sendDisplayManialinkPage($manialink, $timeout, $hideOnClick);

    /**
     * Display a manialink page on the client with the specified UId. The first parameter is the UId
     * of the player, the other are identical to 'SendDisplayManialinkPage'. Only available to
     * Admin.
     *
     * @param string $uid
     * @param string $manialink
     * @param int $timeout
     * @param bool $hideOnClick
     *
     * @return bool
     */
    public function sendDisplayManialinkPageToId($uid, $manialink, $timeout, $hideOnClick);

    /**
     * Display a manialink page on the client with the specified login. The first parameter is the
     * login of the player, the other are identical to 'SendDisplayManialinkPage'. Login can be a
     * single login or a list of comma-separated logins. Only available to Admin.
     *
     * @param string $login
     * @param string $manialink
     * @param int $timeout
     * @param bool $hideOnClick
     *
     * @return bool
     */
    public function sendDisplayManialinkPageToLogin($login, $manialink, $timeout, $hideOnClick);

    /**
     * Hide the displayed manialink page on all clients. Only available to Admin.
     *
     * @return bool
     */
    public function sendHideManialinkPage();

    /**
     * Hide the displayed manialink page on the client with the specified UId. Only available to
     * Admin.
     *
     * @param int $uid
     *
     * @return bool
     */
    public function sendHideManialinkPageToId($uid);

    /**
     * Hide the displayed manialink page on the client with the specified login. Login can be a
     * single login or a list of comma-separated logins. Only available to Admin.
     *
     * @param string $login
     *
     * @return bool
     */
    public function sendHideManialinkPageToLogin($login);

    /**
     * Returns the latest results from the current manialink page, as an array of structs {string
     * *Login*, int *PlayerId*, int *Result*} Result==0 -> no answer, Result>0.... -> answer from
     * the player.
     *
     * @return array
     */
    public function getManialinkPageAnswers($login);

    /**
     * Kick the player with the specified login, with an optional message. Only available to Admin.
     *
     * @param string $login
     * @param string $message
     *
     * @return bool
     */
    public function kick($login, $message = null);

    /**
     * Kick the player with the specified PlayerId, with an optional message. Only available to
     * Admin.
     *
     * @param int $playerId
     * @param string $message
     *
     * @return bool
     */
    public function kickId($playerId, $message = null);

    /**
     * Ban the player with the specified login, with an optional message. Only available to Admin.
     *
     * @param string $login
     * @param string $message
     *
     * @return bool
     */
    public function ban($login, $message = null);

    /**
     * Ban the player with the specified login, with a message. Add it to the black list, and
     * optionally save the new list. Only available to Admin.
     *
     * @param string $login
     * @param string $message
     * @param bool $saveBlackList
     *
     * @return bool
     */
    public function banAndBlackList($login, $message, $saveBlackList);

    /**
     * Ban the player with the specified PlayerId, with an optional message. Only available to
     * Admin.
     *
     * @param int $playerId
     * @param string $message
     *
     * @return bool
     */
    public function banId($playerId, $message = null);

    /**
     * Unban the player with the specified client name. Only available to Admin.
     *
     * @param string $clientName
     *
     * @return bool
     */
    public function unBan($clientName);

    /**
     * Clean the ban list of the server. Only available to Admin.
     *
     * @return bool
     */
    public function cleanBanList();

    /**
     * Returns the list of banned players. This method takes two parameters. The first parameter
     * specifies the maximum number of infos to be returned, and the second one the starting index
     * in the list. The list is an array of structures. Each structure contains the following fields
     * : *Login*, *ClientName* and *IPAddress*.
     *
     * @param int $maxInfos
     * @param int $startingIndex
     *
     * @return array
     */
    public function getBanList($maxInfos, $startingIndex);

    /**
     * Blacklist the player with the specified login. Only available to SuperAdmin.
     *
     * @param string $string
     *
     * @return bool
     */
    public function blackList($login);

    /**
     * Blacklist the player with the specified PlayerId. Only available to SuperAdmin.
     *
     * @param int $playerId
     *
     * @return bool
     */
    public function blackListId($playerId);

    /**
     * UnBlackList the player with the specified login. Only available to SuperAdmin.
     *
     * @param string $login
     *
     * @return bool
     */
    public function unBlackList($login);

    /**
     * Clean the blacklist of the server. Only available to SuperAdmin.
     *
     * @return bool
     */
    public function cleanBlackList($login);

    /**
     * Returns the list of blacklisted players. This method takes two parameters. The first
     * parameter specifies the maximum number of infos to be returned, and the second one the
     * starting index in the list. The list is an array of structures. Each structure contains the
     * following fields : *Login*.
     *
     * @param int $maxInfos
     * @param int $startingIndex
     *
     * @return array
     */
    public function getBlackList($maxInfos, $startingIndex);

    /**
     * Load the black list file with the specified file name. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function loadBlackList($fileName);

    /**
     * Save the black list in the file with specified file name. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function saveBlackList($fileName);

    /**
     * Add the player with the specified login on the guest list. Only available to Admin.
     *
     * @param string $login
     *
     * @return bool
     */
    public function addGuest($login);

    /**
     * Add the player with the specified PlayerId on the guest list. Only available to Admin.
     *
     * @param int $playerId
     *
     * @return bool
     */
    public function addGuestId($playerId);

    /**
     * Remove the player with the specified login from the guest list. Only available to Admin.
     *
     * @param string $login
     *
     * @return bool
     */
    public function removeGuest($login);

    /**
     * Remove the player with the specified PlayerId from the guest list. Only available to Admin.
     *
     * @param int $playerId
     *
     * @return bool
     */
    public function removeGuestId($playerId);

    /**
     * Clean the guest list of the server. Only available to Admin.
     *
     * @return bool
     */
    public function cleanGuestList();

    /**
     * Returns the list of players on the guest list. This method takes two parameters. The first
     * parameter specifies the maximum number of infos to be returned, and the second one the
     * starting index in the list. The list is an array of structures. Each structure contains the
     * following fields : *Login*.
     *
     * @param int $maxInfos
     * @param int $startingIndex
     *
     * @return array
     */
    public function getGuestList($maxInfos, $startingIndex);

    /**
     * Load the guest list file with the specified file name. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function loadGuestList($fileName);

    /**
     * Save the guest list in the file with specified file name. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function saveGuestList($fileName);

    /**
     * Sets whether buddy notifications should be sent in the chat. login is the login of the
     * player, or '' for global setting, and enabled is the value. Only available to Admin.
     *
     * @param string $login
     * @param bool $enabled
     *
     * @return bool
     */
    public function setBuddyNotification($login, $enabled);

    /**
     * Gets whether buddy notifications are enabled for login, or '' to get the global setting.
     *
     * @param string $login
     *
     * @return bool
     */
    public function getBuddyNotification($login);

    /**
     * Write the data to the specified file. The filename is relative to the Tracks path. Only
     * available to Admin.
     *
     * @param string $fileName
     * @param string $data base64 encoded
     *
     * @return bool
     */
    public function writeFile($fileName, $data);

    /**
     * Send the data to the specified player. Only available to Admin.
     *
     * @param int $player
     * @param string $data base64 encoded
     *
     * @return bool
     */
    public function tunnelSendDataToId($fileName, $data);

    /**
     * Send the data to the specified player. Login can be a single login or a list of
     * comma-separated logins. Only available to Admin.
     *
     * @param string $login
     * @param string $data base64 encoded
     *
     * @return bool
     */
    public function tunnelSendDataToLogin($fileName, $data);

    /**
     * Just log the parameters and invoke a callback. Can be used to talk to other xmlrpc clients
     * connected, or to make custom votes. If used in a callvote, the first parameter will be used
     * as the vote message on the clients. Only available to Admin.
     *
     * @param string $param1
     * @param string $param2
     *
     * @return bool
     */
    public function echo($param1, $param2);

    /**
     * Ignore the player with the specified login. Only available to Admin.
     *
     * @param string $login
     *
     * @return bool
     */
    public function ignore($login);

    /**
     * Ignore the player with the specified PlayerId. Only available to Admin.
     *
     * @param int $playerId
     *
     * @return bool
     */
    public function ignoreId($playerId);

    /**
     * Unignore the player with the specified login. Only available to Admin.
     *
     * @param string $login
     *
     * @return bool
     */
    public function unIgnore($login);

    /**
     * Unignore the player with the specified PlayerId. Only available to Admin.
     *
     * @param int $playerId
     *
     * @return bool
     */
    public function unIgnoreId($playerId);

    /**
     * Clean the ignore list of the server. Only available to Admin.
     *
     * @return bool
     */
    public function cleanIgnoreList();

    /**
     * Returns the list of ignored players. This method takes two parameters. The first parameter
     * specifies the maximum number of infos to be returned, and the second one the starting index
     * in the list. The list is an array of structures. Each structure contains the following fields
     * : *Login*.
     *
     * @param int $maxInfos
     * @param int $startingIndex
     *
     * @return array
     */
    public function getIgnoreList($maxInfos, $startingIndex);

    /**
     * Pay coppers from the server account to a player, returns the BillId. This method takes three
     * parameters: *Login* of the payee, *Coppers* to pay and a *Label* to send with the payment.
     * The creation of the transaction itself may cost coppers, so you need to have coppers on the
     * server account. Only available to Admin.
     *
     * @param string $login
     * @param int $coppers
     * @param string $label
     *
     * @return int
     */
    public function pay($login, $coppers, $label);

    /**
     * Create a bill, send it to a player, and return the BillId. This method takes four parameters:
     * *LoginFrom* of the payer, *Coppers* the player has to pay, *Label* of the transaction and an
     * optional *LoginTo* of the payee (if empty string, then the server account is used). The
     * creation of the transaction itself may cost coppers, so you need to have coppers on the
     * server account. Only available to Admin.
     *
     * @param string $loginFrom
     * @param int $coppers
     * @param string $label
     * @param string $loginTo
     *
     * @return int
     */
    public function sendBill($loginFrom, $coppers, $label, $loginTo = null);

    /**
     * Returns the current state of a bill. This method takes one parameter, the *BillId*. Returns a
     * struct containing *State*, *StateName* and *TransactionId*. Possible enum values are:
     * *CreatingTransaction*, *Issued*, *ValidatingPayement*, *Payed*, *Refused*, *Error*.
     *
     * @param int $billId
     *
     * @return array
     */
    public function getBillState($billId);

    /**
     * Returns the current number of coppers on the server account.
     *
     * @return int
     */
    public function getServerCoppers();

    /**
     * Get some system infos, including connection rates (in kbps).
     *
     * @return array
     */
    public function getSystemInfo();

    /**
     * Set the download and upload rates (in kbps).
     *
     * @param int $downloadRate
     * @param int $uploadRate
     *
     * @return bool
     */
    public function setConnectionRates($downloadRate, $uploadRate);

    /**
     * Set a new server name in utf8 format. Only available to Admin.
     *
     * @param string $serverName
     *
     * @return bool
     */
    public function setServerName($serverName);

    /**
     * Get the server name in utf8 format.
     *
     * @return string
     */
    public function getServerName();

    /**
     * Set a new server comment in utf8 format. Only available to Admin.
     *
     * @param string $serverComment
     *
     * @return bool
     */
    public function setServerComment($serverComment);

    /**
     * Get the server comment in utf8 format.
     *
     * @return string
     */
    public function getServerComment();

    /**
     * Set whether the server should be hidden from the public server list (0 = visible, 1 = always
     * hidden, 2 = hidden from nations). Only available to Admin.
     *
     * @param int $hideServer
     *
     * @return bool
     */
    public function setHideServer($hideServer);

    /**
     * Get whether the server wants to be hidden from the public server list.
     *
     * @return int
     */
    public function getHideServer();

    /**
     * Returns true if this is a relay server.
     *
     * @return bool
     */
    public function isRelayServer();

    /**
     * Set a new password for the server. Only available to Admin.
     *
     * @param string $serverPassword
     *
     * @return bool
     */
    public function setServerPassword($serverPassword);

    /**
     * Get the server password if called as Admin or Super Admin, else returns if a password is
     * needed or not.
     *
     * @return string
     */
    public function getServerPassword();

    /**
     * Set a new password for the spectator mode. Only available to Admin.
     *
     * @param string $serverPasswordForSpectator
     *
     * @return bool
     */
    public function setServerPasswordForSpectator($serverPasswordForSpectator);

    /**
     * Get the password for spectator mode if called as Admin or Super Admin, else returns if a
     * password is needed or not.
     *
     * @return string
     */
    public function getServerPasswordForSpectator();

    /**
     * Set a new maximum number of players. Only available to Admin. Requires a challenge restart to
     * be taken into account.
     *
     * @param int $maxPlayers
     *
     * @return bool
     */
    public function setMaxPlayers($maxPlayers);

    /**
     * Get the current and next maximum number of players allowed on server. The struct returned
     * contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getMaxPlayers();

    /**
     * Set a new maximum number of Spectators. Only available to Admin. Requires a challenge restart
     * to be taken into account.
     *
     * @param int $maxSpectators
     *
     * @return bool
     */
    public function setMaxSpectators($maxSpectators);

    /**
     * Get the current and next maximum number of Spectators allowed on server. The struct returned
     * contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getMaxSpectators();

    /**
     * Enable or disable peer-to-peer upload from server. Only available to Admin.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public function enableP2PUpload($enabled);

    /**
     * Returns if the peer-to-peer upload from server is enabled.
     *
     * @return bool
     */
    public function isP2PUpload();

    /**
     * Enable or disable peer-to-peer download for server. Only available to Admin.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public function enableP2PDownload($enabled);

    /**
     * Returns if the peer-to-peer download for server is enabled.
     *
     * @return bool
     */
    public function isP2PDownload();

    /**
     * Allow clients to download challenges from the server. Only available to Admin.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public function allowChallengeDownload($enabled);

    /**
     * Returns if clients can download challenges from the server.
     *
     * @return bool
     */
    public function isChallengeDownloadAllowed();

    /**
     * Enable the autosaving of all replays (vizualisable replays with all players, but not
     * validable) on the server. Only available to SuperAdmin.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public function autoSaveReplays($enabled);

    /**
     * Enable the autosaving on the server of validation replays, every time a player makes a new
     * time. Only available to SuperAdmin.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public function autoSaveValidationReplays($enabled);

    /**
     * Returns if autosaving of all replays is enabled on the server.
     *
     * @return bool
     */
    public function isAutoSaveReplaysEnabled();

    /**
     * Returns if autosaving of validation replays is enabled on the server.
     *
     * @return bool
     */
    public function isAutoSaveValidationReplaysEnabled();

    /**
     * Saves the current replay (vizualisable replays with all players, but not validable). Pass a
     * filename, or '' for an automatic filename. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function saveCurrentReplay($fileName);

    /**
     * Saves a replay with the ghost of all the players' best race. First parameter is the login of
     * the player (or '' for all players), Second parameter is the filename, or '' for an automatic
     * filename. Only available to Admin.
     *
     * @param string $login
     * @param string $fileName
     *
     * @return bool
     */
    public function saveBestGhostsReplay($login, $fileName);

    /**
     * Returns a replay containing the data needed to validate the current best time of the player.
     * The parameter is the login of the player.
     *
     * @param string $login
     *
     * @return string base64 encoded
     */
    public function getValidationReplay($login);

    /**
     * Set a new ladder mode between ladder disabled (0) and forced (1). Only available to Admin.
     * Requires a challenge restart to be taken into account.
     *
     * @param int $ladderMode
     *
     * @return bool
     */
    public function setLadderMode($ladderMode);

    /**
     * Get the current and next ladder mode on server. The struct returned contains two fields
     * *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getLadderMode();

    /**
     * Get the ladder points limit for the players allowed on this server. The struct returned
     * contains two fields *LadderServerLimitMin* and *LadderServerLimitMax*.
     *
     * @return array
     */
    public function getLadderServerLimits();

    /**
     * Set the network vehicle quality to Fast (0) or High (1). Only available to Admin. Requires a
     * challenge restart to be taken into account.
     *
     * @param int $vehicleNetQuality
     *
     * @return bool
     */
    public function setVehicleNetQuality($vehicleNetQuality);

    /**
     * Get the current and next network vehicle quality on server. The struct returned contains two
     * fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getVehicleNetQuality();

    /**
     * Set new server options using the struct passed as parameters. This struct must contain the
     * following fields : *Name*, *Comment*, *Password*, *PasswordForSpectator*, *NextMaxPlayers*,
     * *NextMaxSpectators*, *IsP2PUpload*, *IsP2PDownload*, *NextLadderMode*,
     * *NextVehicleNetQuality*, *NextCallVoteTimeOut*, *CallVoteRatio*, *AllowChallengeDownload*,
     * *AutoSaveReplays*, and optionally for forever: *RefereePassword*, *RefereeMode*,
     * *AutoSaveValidationReplays*, *HideServer*, *UseChangingValidationSeed*. Only available to
     * Admin. A change of NextMaxPlayers, NextMaxSpectators, NextLadderMode, NextVehicleNetQuality,
     * NextCallVoteTimeOut or UseChangingValidationSeed requires a challenge restart to be taken
     * into account.
     *
     * @param array $serverOptions
     *
     * @return bool
     */
    public function setServerOptions($serverOptions);

    /**
     * Optional parameter for compatibility: struct version (0 = united, 1 = forever). Returns a
     * struct containing the server options: *Name*, *Comment*, *Password*, *PasswordForSpectator*,
     * *CurrentMaxPlayers*, *NextMaxPlayers*, *CurrentMaxSpectators*, *NextMaxSpectators*,
     * *IsP2PUpload*, *IsP2PDownload*, *CurrentLadderMode*, *NextLadderMode*,
     * *CurrentVehicleNetQuality*, *NextVehicleNetQuality*, *CurrentCallVoteTimeOut*,
     * *NextCallVoteTimeOut*, *CallVoteRatio*, *AllowChallengeDownload* and *AutoSaveReplays*, and
     * additionally for forever: *RefereePassword*, *RefereeMode*, *AutoSaveValidationReplays*,
     * *HideServer*, *CurrentUseChangingValidationSeed*, *NextUseChangingValidationSeed*.
     *
     * @param int $structVersion
     *
     * @return array
     */
    public function getServerOptions($structVersion = null);

    /**
     * Defines the packmask of the server. Can be 'United', 'Nations', 'Sunrise', 'Original', or any
     * of the environment names. (Only challenges matching the packmask will be allowed on the
     * server, so that player connecting to it know what to expect.) Only available when the server
     * is stopped. Only available to Admin.
     *
     * @param string $serverPackMask
     *
     * @return bool
     */
    public function setServerPackMask($serverPackMask);

    /**
     * Get the packmask of the server.
     *
     * @return string
     */
    public function getServerPackMask();

    /**
     * Set the mods to apply on the clients. Parameters: *Override*, if true even the challenges
     * with a mod will be overridden by the server setting; and *Mods*, an array of structures
     * [{*EnvName*, *Url*}, ...]. Requires a challenge restart to be taken into account. Only
     * available to Admin.
     *
     * @param bool $override
     * @param array $mods
     *
     * @return bool
     */
    public function setForcedMods($override, $mods);

    /**
     * Get the mods settings.
     *
     * @return array
     */
    public function getForcedMods();

    /**
     * Set the music to play on the clients. Parameters: *Override*, if true even the challenges
     * with a custom music will be overridden by the server setting, and a *UrlOrFileName* for the
     * music. Requires a challenge restart to be taken into account. Only available to Admin.
     *
     * @param int $override
     * @param string $urlOrFileName
     *
     * @return bool
     */
    public function setForcedMusic($override, $urlOrFileName);

    /**
     * Get the music setting.
     *
     * @return array
     */
    public function getForcedMusic();

    /**
     * Defines a list of remappings for player skins. It expects a list of structs *Orig*, *Name*,
     * *Checksum*, *Url*. Orig is the name of the skin to remap, or '*' for any other. Name,
     * Checksum, Url define the skin to use. (They are optional, you may set value '' for any of
     * those. All 3 null means same as Orig). Will only affect players connecting after the value is
     * set. Only available to Admin.
     *
     * @param array $remappings
     *
     * @return bool
     */
    public function setForcedSkins($remappings);

    /**
     * Get the current forced skins.
     *
     * @return array
     */
    public function getForcedSkins();

    /**
     * Returns the last error message for an internet connection. Only available to Admin.
     *
     * @return string
     */
    public function getLastConnectionErrorMessage();

    /**
     * Set a new password for the referee mode. Only available to Admin.
     *
     * @param string $refereePassword
     *
     * @return bool
     */
    public function setRefereePassword($refereePassword);

    /**
     * Get the password for referee mode if called as Admin or Super Admin, else returns if a
     * password is needed or not.
     *
     * @return string
     */
    public function getRefereePassword();

    /**
     * Set the referee validation mode. 0 = validate the top3 players, 1 = validate all players.
     * Only available to Admin.
     *
     * @param int $refereeMode
     *
     * @return bool
     */
    public function setRefereeMode($refereeMode);

    /**
     * Get the referee validation mode.
     *
     * @return int
     */
    public function getRefereeMode();

    /**
     * Set whether the game should use a variable validation seed or not. Only available to Admin.
     * Requires a challenge restart to be taken into account.
     *
     * @param bool $useChangingValidationSeed
     *
     * @return bool
     */
    public function setUseChangingValidationSeed($useChangingValidationSeed);

    /**
     * Get the current and next value of UseChangingValidationSeed. The struct returned contains two
     * fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getUseChangingValidationSeed();

    /**
     * Sets whether the server is in warm-up phase or not. Only available to Admin.
     *
     * @param bool $warmUp
     *
     * @return bool
     */
    public function setWarmUp($warmUp);

    /**
     * Returns whether the server is in warm-up phase.
     *
     * @return bool
     */
    public function getWarmUp();

    /**
     * Restarts the challenge, with an optional boolean parameter *DontClearCupScores* (only
     * available in cup mode). Only available to Admin.
     *
     * @param bool $dontClearCupScores
     *
     * @return bool
     */
    public function challengeRestart($dontClearCupScores = null);

    /**
     * Restarts the challenge, with an optional boolean parameter *DontClearCupScores* (only
     * available in cup mode). Only available to Admin.
     *
     * @param bool $dontClearCupScores
     *
     * @return bool
     */
    public function restartChallenge($dontClearCupScores = null);

    /**
     * Switch to next challenge, with an optional boolean parameter *DontClearCupScores* (only
     * available in cup mode). Only available to Admin.
     *
     * @param bool $dontClearCupScores
     *
     * @return bool
     */
    public function nextChallenge($dontClearCupScores = null);

    /**
     * Stop the server. Only available to SuperAdmin.
     *
     * @return bool
     */
    public function stopServer();

    /**
     * In Rounds or Laps mode, force the end of round without waiting for all players to
     * giveup/finish. Only available to Admin.
     *
     * @return bool
     */
    public function forceEndRound();

    /**
     * Set new game settings using the struct passed as parameters. This struct must contain the
     * following fields : *GameMode*, *ChatTime*, *RoundsPointsLimit*, *RoundsUseNewRules*,
     * *RoundsForcedLaps*, *TimeAttackLimit*, *TimeAttackSynchStartPeriod*, *TeamPointsLimit*,
     * *TeamMaxPoints*, *TeamUseNewRules*, *LapsNbLaps*, *LapsTimeLimit*, *FinishTimeout*, and
     * optionally: *AllWarmUpDuration*, *DisableRespawn*, *ForceShowAllOpponents*,
     * *RoundsPointsLimitNewRules*, *TeamPointsLimitNewRules*, *CupPointsLimit*,
     * *CupRoundsPerChallenge*, *CupNbWinners*, *CupWarmUpDuration*. Only available to Admin.
     * Requires a challenge restart to be taken into account.
     *
     * @param array $gameInfos
     *
     * @return bool
     */
    public function setGameInfos($gameInfos);

    /**
     * Optional parameter for compatibility: struct version (0 = united, 1 = forever). Returns a
     * struct containing the current game settings, ie: *GameMode*, *ChatTime*, *NbChallenge*,
     * *RoundsPointsLimit*, *RoundsUseNewRules*, *RoundsForcedLaps*, *TimeAttackLimit*,
     * *TimeAttackSynchStartPeriod*, *TeamPointsLimit*, *TeamMaxPoints*, *TeamUseNewRules*,
     * *LapsNbLaps*, *LapsTimeLimit*, *FinishTimeout*, and additionally for version 1:
     * *AllWarmUpDuration*, *DisableRespawn*, *ForceShowAllOpponents*, *RoundsPointsLimitNewRules*,
     * *TeamPointsLimitNewRules*, *CupPointsLimit*, *CupRoundsPerChallenge*, *CupNbWinners*,
     * *CupWarmUpDuration*.
     *
     * @param int $structVersion
     *
     * @return array
     */
    public function getCurrentGameInfo($structVersion = null);

    /**
     * Optional parameter for compatibility: struct version (0 = united, 1 = forever). Returns a
     * struct containing the game settings for the next challenge, ie: *GameMode*, *ChatTime*,
     * *NbChallenge*, *RoundsPointsLimit*, *RoundsUseNewRules*, *RoundsForcedLaps*,
     * *TimeAttackLimit*, *TimeAttackSynchStartPeriod*, *TeamPointsLimit*, *TeamMaxPoints*,
     * *TeamUseNewRules*, *LapsNbLaps*, *LapsTimeLimit*, *FinishTimeout*, and additionally for
     * version 1: *AllWarmUpDuration*, *DisableRespawn*, *ForceShowAllOpponents*,
     * *RoundsPointsLimitNewRules*, *TeamPointsLimitNewRules*, *CupPointsLimit*,
     * *CupRoundsPerChallenge*, *CupNbWinners*, *CupWarmUpDuration*.
     *
     * @param int $structVersion
     *
     * @return array
     */
    public function getNextGameInfo($structVersion = null);

    /**
     * Optional parameter for compatibility: struct version (0 = united, 1 = forever). Returns a
     * struct containing two other structures, the first containing the current game settings and
     * the second the game settings for next challenge. The first structure is named
     * *CurrentGameInfos* and the second *NextGameInfos*.
     *
     * @param int $structVersion
     *
     * @return array
     */
    public function getGameInfos($structVersion = null);

    /**
     * Set a new game mode between Rounds (0), TimeAttack (1), Team (2), Laps (3), Stunts (4) and
     * Cup (5). Only available to Admin. Requires a challenge restart to be taken into account.
     *
     * @param int $gameMode
     *
     * @return bool
     */
    public function setGameMode($gameMode);

    /**
     * Get the current game mode.
     *
     * @return int
     */
    public function getGameMode();

    /**
     * Set a new chat time value in milliseconds (actually 'chat time' is the duration of the end
     * race podium, 0 means no podium displayed.). Only available to Admin.
     *
     * @param int $chatTime
     *
     * @return bool
     */
    public function setChatTime($chatTime);

    /**
     * Get the current and next chat time. The struct returned contains two fields *CurrentValue*
     * and *NextValue*.
     *
     * @return array
     */
    public function getChatTime();

    /**
     * Set a new finish timeout (for rounds/laps mode) value in milliseconds. 0 means default. 1
     * means adaptative to the duration of the challenge. Only available to Admin. Requires a
     * challenge restart to be taken into account.
     *
     * @param int $finishTimeout
     *
     * @return bool
     */
    public function setFinishTimeout($finishTimeout);

    /**
     * Get the current and next FinishTimeout. The struct returned contains two fields
     * *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getFinishTimeout();

    /**
     * Set whether to enable the automatic warm-up phase in all modes. 0 = no, otherwise it's the
     * duration of the phase, expressed in number of rounds (in rounds/team mode), or in number of
     * times the gold medal time (other modes). Only available to Admin. Requires a challenge
     * restart to be taken into account.
     *
     * @param int $allWarmupDuration
     *
     * @return bool
     */
    public function setAllWarmUpDuration($allWarmupDuration);

    /**
     * Get whether the automatic warm-up phase is enabled in all modes. The struct returned contains
     * two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getAllWarmUpDuration();

    /**
     * Set whether to disallow players to respawn. Only available to Admin. Requires a challenge
     * restart to be taken into account.
     *
     * @param bool $disableRespawn
     *
     * @return bool
     */
    public function setDisableRespawn($disableRespawn);

    /**
     * Get whether players are disallowed to respawn. The struct returned contains two fields
     * *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getDisableRespawn();

    /**
     * Set whether to override the players preferences and always display all opponents (0=no
     * override, 1=show all, other value=minimum number of opponents). Only available to Admin.
     * Requires a challenge restart to be taken into account.
     *
     * @param int $forceShowAllOpponents
     *
     * @return bool
     */
    public function setForceShowAllOpponents($forceShowAllOpponents);

    /**
     * Get whether players are forced to show all opponents. The struct returned contains two fields
     * *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getForceShowAllOpponents();

    /**
     * Set a new time limit for time attack mode. Only available to Admin. Requires a challenge
     * restart to be taken into account.
     *
     * @param int $timeAttackLimit
     *
     * @return bool
     */
    public function setTimeAttackLimit($timeAttackLimit);

    /**
     * Get the current and next time limit for time attack mode. The struct returned contains two
     * fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getTimeAttackLimit();

    /**
     * Set a new synchronized start period for time attack mode. Only available to Admin. Requires a
     * challenge restart to be taken into account.
     *
     * @param int $timeAttackSynchStartPeriod
     *
     * @return bool
     */
    public function setTimeAttackSynchStartPeriod($timeAttackSynchStartPeriod);

    /**
     * Get the current and synchronized start period for time attack mode. The struct returned
     * contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getTimeAttackSynchStartPeriod();

    /**
     * Set a new time limit for laps mode. Only available to Admin. Requires a challenge restart to
     * be taken into account.
     *
     * @param int $lapsTimeLimit
     *
     * @return bool
     */
    public function setLapsTimeLimit($lapsTimeLimit);

    /**
     * Get the current and next time limit for laps mode. The struct returned contains two fields
     * *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getLapsTimeLimit();

    /**
     * Set a new number of laps for laps mode. Only available to Admin. Requires a challenge restart
     * to be taken into account.
     *
     * @param int $nbLaps
     *
     * @return bool
     */
    public function setNbLaps($nbLaps);

    /**
     * Get the current and next number of laps for laps mode. The struct returned contains two
     * fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getNbLaps();

    /**
     * Set a new number of laps for rounds mode (0 = default, use the number of laps from the
     * challenges, otherwise forces the number of rounds for multilaps challenges). Only available
     * to Admin. Requires a challenge restart to be taken into account.
     *
     * @param int $roundForcedLaps
     *
     * @return bool
     */
    public function setRoundForcedLaps($roundForcedLaps);

    /**
     * Get the current and next number of laps for rounds mode. The struct returned contains two
     * fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getRoundForcedLaps();

    /**
     * Set a new points limit for rounds mode (value set depends on UseNewRulesRound). Only
     * available to Admin. Requires a challenge restart to be taken into account.
     *
     * @param int $roundPointsLimit
     *
     * @return bool
     */
    public function setRoundPointsLimit($roundPointsLimit);

    /**
     * Get the current and next points limit for rounds mode (values returned depend on
     * UseNewRulesRound). The struct returned contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getRoundPointsLimit();

    /**
     * Set the points used for the scores in rounds mode. *Points* is an array of decreasing
     * integers for the players from the first to last. And you can add an optional boolean to relax
     * the constraint checking on the scores. Only available to Admin.
     *
     * @param array $points
     * @param bool $relaxConstraints
     *
     * @return bool
     */
    public function setRoundCustomPoints($points, $relaxConstraints);

    /**
     * Gets the points used for the scores in rounds mode.
     *
     * @return array
     */
    public function getRoundCustomPoints();

    /**
     * Set if new rules are used for rounds mode. Only available to Admin. Requires a challenge
     * restart to be taken into account.
     *
     * @param bool $useNewRulesRound
     *
     * @return bool
     */
    public function setUseNewRulesRound($useNewRulesRound);

    /**
     * Get if the new rules are used for rounds mode (Current and next values). The struct returned
     * contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getUseNewRulesRound();

    /**
     * Set a new points limit for team mode (value set depends on UseNewRulesTeam). Only available
     * to Admin. Requires a challenge restart to be taken into account.
     *
     * @param int $teamPointsLimit
     *
     * @return bool
     */
    public function setTeamPointsLimit($teamPointsLimit);

    /**
     * Get the current and next points limit for team mode (values returned depend on
     * UseNewRulesTeam). The struct returned contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getTeamPointsLimit();

    /**
     * Set a new number of maximum points per round for team mode. Only available to Admin. Requires
     * a challenge restart to be taken into account.
     *
     * @param int $maxPointsTeam
     *
     * @return bool
     */
    public function setMaxPointsTeam($maxPointsTeam);

    /**
     * Get the current and next number of maximum points per round for team mode. The struct
     * returned contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getMaxPointsTeam();

    /**
     * Set if new rules are used for team mode. Only available to Admin. Requires a challenge
     * restart to be taken into account.
     *
     * @param bool $useNewRulesTeam
     *
     * @return bool
     */
    public function setUseNewRulesTeam($useNewRulesTeam);

    /**
     * Get if the new rules are used for team mode (Current and next values). The struct returned
     * contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getUseNewRulesTeam();

    /**
     * Set the points needed for victory in Cup mode. Only available to Admin. Requires a challenge
     * restart to be taken into account.
     *
     * @param int $cupPointsLimit
     *
     * @return bool
     */
    public function setCupPointsLimit($cupPointsLimit);

    /**
     * Get the points needed for victory in Cup mode. The struct returned contains two fields
     * *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getCupPointsLimit();

    /**
     * Sets the number of rounds before going to next challenge in Cup mode. Only available to
     * Admin. Requires a challenge restart to be taken into account.
     *
     * @param int $cupRoundsPerChallenge
     *
     * @return bool
     */
    public function setCupRoundsPerChallenge($cupRoundsPerChallenge);

    /**
     * Get the number of rounds before going to next challenge in Cup mode. The struct returned
     * contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getCupRoundsPerChallenge();

    /**
     * Set whether to enable the automatic warm-up phase in Cup mode. 0 = no, otherwise it's the
     * duration of the phase, expressed in number of rounds. Only available to Admin. Requires a
     * challenge restart to be taken into account.
     *
     * @param int $cupWarmUpDuration
     *
     * @return bool
     */
    public function setCupWarmUpDuration($cupWarmUpDuration);

    /**
     * Get whether the automatic warm-up phase is enabled in Cup mode. The struct returned contains
     * two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getCupWarmUpDuration();

    /**
     * Set the number of winners to determine before the match is considered over. Only available to
     * Admin. Requires a challenge restart to be taken into account.
     *
     * @param int $cupNbWinners
     *
     * @return bool
     */
    public function setCupNbWinners($cupNbWinners);

    /**
     * Get the number of winners to determine before the match is considered over. The struct
     * returned contains two fields *CurrentValue* and *NextValue*.
     *
     * @return array
     */
    public function getCupNbWinners();

    /**
     * Returns the current challenge index in the selection, or -1 if the challenge is no longer in
     * the selection.
     *
     * @return int
     */
    public function getCurrentChallengeIndex();

    /**
     * Returns the challenge index in the selection that will be played next (unless the current one
     * is restarted...)
     *
     * @return int
     */
    public function getNextChallengeIndex();

    /**
     * Sets the challenge index in the selection that will be played next (unless the current one is
     * restarted...)
     *
     * @param int $nextChallengeIndex
     *
     * @return bool
     */
    public function setNextChallengeIndex($nextChallengeIndex);

    /**
     * Returns a struct containing the infos for the current challenge. The struct contains the
     * following fields : *Name*, *UId*, *FileName*, *Author*, *Environnement*, *Mood*,
     * *BronzeTime*, *SilverTime*, *GoldTime*, *AuthorTime*, *CopperPrice*, *LapRace*, *NbLaps* and
     * *NbCheckpoints*.
     *
     * @return array
     */
    public function getCurrentChallengeInfo();

    /**
     * Returns a struct containing the infos for the next challenge. The struct contains the
     * following fields : *Name*, *UId*, *FileName*, *Author*, *Environnement*, *Mood*,
     * *BronzeTime*, *SilverTime*, *GoldTime*, *AuthorTime*, *CopperPrice* and *LapRace*. (*NbLaps*
     * and *NbCheckpoints* are also present but always set to -1)
     *
     * @return array
     */
    public function getNextChallengeInfo();

    /**
     * Returns a struct containing the infos for the challenge with the specified filename. The
     * struct contains the following fields : *Name*, *UId*, *FileName*, *Author*, *Environnement*,
     * *Mood*, *BronzeTime*, *SilverTime*, *GoldTime*, *AuthorTime*, *CopperPrice* and *LapRace*.
     * (*NbLaps* and *NbCheckpoints* are also present but always set to -1)
     *
     * @param string $fileName
     *
     * @return array
     */
    public function getChallengeInfo($fileName);

    /**
     * Returns a boolean if the challenge with the specified filename matches the current server
     * settings.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function checkChallengeForCurrentServerParams($fileName);

    /**
     * Returns a list of challenges among the current selection of the server. This method take two
     * parameters. The first parameter specifies the maximum number of infos to be returned, and the
     * second one the starting index in the selection. The list is an array of structures. Each
     * structure contains the following fields : *Name*, *UId*, *FileName*, *Environnement*,
     * *Author*, *GoldTime* and *CopperPrice*.
     *
     * @param int $maxInfos
     * @param int $startingIndex
     *
     * @return array
     */
    public function getChallengeList($maxInfos, $startingIndex);

    /**
     * Add the challenge with the specified filename at the end of the current selection. Only
     * available to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function addChallenge($fileName);

    /**
     * Add the list of challenges with the specified filenames at the end of the current selection.
     * The list of challenges to add is an array of strings. Only available to Admin.
     *
     * @param array $fileNames
     *
     * @return int
     */
    public function addChallengeList($fileNames);

    /**
     * Remove the challenge with the specified filename from the current selection. Only available
     * to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function removeChallenge($fileName);

    /**
     * Remove the list of challenges with the specified filenames from the current selection. The
     * list of challenges to remove is an array of strings. Only available to Admin.
     *
     * @param array $fileNames
     *
     * @return int
     */
    public function removeChallengeList($fileNames);

    /**
     * Insert the challenge with the specified filename after the current challenge. Only available
     * to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function insertChallenge($fileName);

    /**
     * Insert the list of challenges with the specified filenames after the current challenge. The
     * list of challenges to insert is an array of strings. Only available to Admin.
     *
     * @param array $fileNames
     *
     * @return int
     */
    public function insertChallengeList($fileNames);

    /**
     * Set as next challenge the one with the specified filename, if it is present in the selection.
     * Only available to Admin.
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function chooseNextChallenge($fileName);

    /**
     * Set as next challenges the list of challenges with the specified filenames, if they are
     * present in the selection. The list of challenges to choose is an array of strings. Only
     * available to Admin.
     *
     * @param array $fileNames
     *
     * @return int
     */
    public function chooseNextChallengeList($fileNames);

    /**
     * Set a list of challenges defined in the playlist with the specified filename as the current
     * selection of the server, and load the gameinfos from the same file. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return int
     */
    public function loadMatchSettings($fileName);

    /**
     * Add a list of challenges defined in the playlist with the specified filename at the end of
     * the current selection. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return int
     */
    public function appendPlaylistFromMatchSettings($fileName);

    /**
     * Save the current selection of challenge in the playlist with the specified filename, as well
     * as the current gameinfos. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return int
     */
    public function saveMatchSettings($fileName);

    /**
     * Insert a list of challenges defined in the playlist with the specified filename after the
     * current challenge. Only available to Admin.
     *
     * @param string $fileName
     *
     * @return int
     */
    public function insertPlaylistFromMatchSettings($fileName);

    /**
     * Returns the list of players on the server. This method take two parameters. The first
     * parameter specifies the maximum number of infos to be returned, and the second one the
     * starting index in the list, an optional 3rd parameter is used for compatibility: struct
     * version (0 = united, 1 = forever, 2 = forever, including the servers). The list is an array
     * of PlayerInfo structures. Forever PlayerInfo struct is: *Login*, *NickName*, *PlayerId*,
     * *TeamId*, *SpectatorStatus*, *LadderRanking*, and *Flags*.
     *
     * *LadderRanking* is 0 when not in official mode,
     *
     * *Flags* = *ForceSpectator*(0,1,2) + *IsReferee* \* 10 + *IsPodiumReady* \* 100 +
     * *IsUsingStereoscopy* \* 1000 + *IsManagedByAnOtherServer* \* 10000 + *IsServer* \* 100000 +
     * *HasPlayerSlot* \* 1000000
     *
     * *SpectatorStatus* = *Spectator* + *TemporarySpectator* \* 10 + *PureSpectator* \* 100 +
     * *AutoTarget* \* 1000 + *CurrentTargetId* \* 10000
     *
     * @param int $maxInfos
     * @param int $startingIndex
     * @param int $structVersion
     *
     * @return array
     */
    public function getPlayerList($maxInfos, $startingIndex, $structVersion = null);

    /**
     * Returns a struct containing the infos on the player with the specified login, with an
     * optional parameter for compatibility: struct version (0 = united, 1 = forever). The structure
     * is identical to the ones from GetPlayerList. Forever PlayerInfo struct is: *Login*,
     * *NickName*, *PlayerId*, *TeamId*, *SpectatorStatus*, *LadderRanking*, and *Flags*.
     *
     * *LadderRanking* is 0 when not in official mode,
     *
     * *Flags* = *ForceSpectator*(0,1,2) + *IsReferee* \* 10 + *IsPodiumReady* \* 100 +
     * *IsUsingStereoscopy* \* 1000 + *IsManagedByAnOtherServer* \* 10000 + *IsServer* \* 100000 +
     * *HasPlayerSlot* \* 1000000
     *
     * *SpectatorStatus* = *Spectator* + *TemporarySpectator* \* 10 + *PureSpectator* \* 100 +
     * *AutoTarget* \* 1000 + *CurrentTargetId* \* 10000
     *
     * @param string $login
     * @param int $structVersion
     *
     * @return array
     */
    public function getPlayerInfo($login, $structVersion = null);

    /**
     * Returns a struct containing the infos on the player with the specified login. The structure
     * contains the following fields : *Login*, *NickName*, *PlayerId*, *TeamId*, *IPAddress*,
     * *DownloadRate*, *UploadRate*, *Language*, *IsSpectator*, *IsInOfficialMode*, a structure
     * named *Avatar*, an array of structures named *Skins*, a structure named *LadderStats*,
     * *HoursSinceZoneInscription* and *OnlineRights* (0: nations account, 3: united account). Each
     * structure of the array *Skins* contains two fields *Environnement* and a struct *PackDesc*.
     * Each structure *PackDesc*, as well as the struct *Avatar*, contains two fields *FileName* and
     * *Checksum*.
     *
     * @param string $login
     *
     * @return array
     */
    public function getDetailedPlayerInfo($login);

    /**
     * Returns a struct containing the player infos of the game server (ie: in case of a basic
     * server, itself; in case of a relay server, the main server), with an optional parameter for
     * compatibility: struct version (0 = united, 1 = forever). The structure is identical to the
     * ones from GetPlayerList. Forever PlayerInfo struct is: *Login*, *NickName*, *PlayerId*,
     * *TeamId*, *SpectatorStatus*, *LadderRanking*, and *Flags*.
     *
     * *LadderRanking* is 0 when not in official mode,
     *
     * *Flags* = *ForceSpectator*(0,1,2) + *IsReferee* \* 10 + *IsPodiumReady* \* 100 +
     * *IsUsingStereoscopy* \* 1000 + *IsManagedByAnOtherServer* \* 10000 + *IsServer* \* 100000 +
     * *HasPlayerSlot* \* 1000000
     *
     * *SpectatorStatus* = *Spectator* + *TemporarySpectator* \* 10 + *PureSpectator* \* 100 +
     * *AutoTarget* \* 1000 + *CurrentTargetId* \* 10000
     *
     * @param int $structVersion
     *
     * @return array
     */
    public function getMainServerPlayerInfo($structVersion = null);

    /**
     * Returns the current rankings for the race in progress. (in team mode, the scores for the two
     * teams are returned. In other modes, it's the individual players' scores) This method take two
     * parameters. The first parameter specifies the maximum number of infos to be returned, and the
     * second one the starting index in the ranking. The ranking returned is a list of structures.
     * Each structure contains the following fields : *Login*, *NickName*, *PlayerId*, *Rank*,
     * *BestTime*, *Score*, *NbrLapsFinished* and *LadderScore*. It also contains an array
     * *BestCheckpoints* that contains the checkpoint times for the best race.
     *
     * @param int $maxInfos
     * @param int $startingIndex
     *
     * @return array
     */
    public function getCurrentRanking($maxInfos, $startingIndex);

    /**
     * Returns the current ranking for the race in progressof the player with the specified login
     * (or list of comma-separated logins). The ranking returned is a list of structures, that
     * contains the following fields : *Login*, *NickName*, *PlayerId*, *Rank*, *BestTime*, *Score*,
     * *NbrLapsFinished* and *LadderScore*. It also contains an array *BestCheckpoints* that
     * contains the checkpoint times for the best race.
     *
     * @param string $login
     *
     * @return array
     */
    public function getCurrentRankingForLogin($login);

    /**
     * Force the scores of the current game. Only available in rounds and team mode. You have to
     * pass an array of structs {int *PlayerId*, int *Score*}. And a boolean *SilentMode* - if true,
     * the scores are silently updated (only available for SuperAdmin), allowing an external
     * controller to do its custom counting... Only available to Admin/SuperAdmin.
     *
     * @param array $scores
     * @param bool $silentMode
     *
     * @return bool
     */
    public function forceScores($scores, $silentMode);

    /**
     * Force the team of the player. Only available in team mode. You have to pass the login and the
     * team number (0 or 1). Only available to Admin.
     *
     * @param string $login
     * @param int $teamNumber
     *
     * @return bool
     */
    public function forcePlayerTeam($login, $teamNumber);

    /**
     * Force the team of the player. Only available in team mode. You have to pass the playerid and
     * the team number (0 or 1). Only available to Admin.
     *
     * @param int $playerId
     * @param int $teamNumber
     *
     * @return bool
     */
    public function forcePlayerTeamId($playerId, $teamNumber);

    /**
     * Force the spectating status of the player. You have to pass the login and the spectator mode
     * (0: user selectable, 1: spectator, 2: player). Only available to Admin.
     *
     * @param string $login
     * @param int $spectatorMode
     *
     * @return bool
     */
    public function forceSpectator($login, $spectatorMode);

    /**
     * Force the spectating status of the player. You have to pass the playerid and the spectator
     * mode (0: user selectable, 1: spectator, 2: player). Only available to Admin.
     *
     * @param int $playerId
     * @param int $spectatorMode
     *
     * @return bool
     */
    public function forceSpectatorId($playerId, $spectatorMode);

    /**
     * Force spectators to look at a specific player. You have to pass the login of the spectator
     * (or '' for all) and the login of the target (or '' for automatic), and an integer for the
     * camera type to use (-1 = leave unchanged, 0 = replay, 1 = follow, 2 = free). Only available
     * to Admin.
     *
     * @param string $spectatorLogin
     * @param string $targetLogin
     * @param int $cameraType
     *
     * @return bool
     */
    public function forceSpectatorTarget($spectatorLogin, $targetLogin, $cameraType);

    /**
     * Force spectators to look at a specific player. You have to pass the id of the spectator (or
     * -1 for all) and the id of the target (or -1 for automatic), and an integer for the camera
     * type to use (-1 = leave unchanged, 0 = replay, 1 = follow, 2 = free). Only available to
     * Admin.
     *
     * @param string $spectatorId
     * @param string $targetId
     * @param int $cameraType
     *
     * @return bool
     */
    public function forceSpectatorTargetId($spectatorLogin, $targetLogin, $cameraType);

    /**
     * Pass the login of the spectator. A spectator that once was a player keeps his player slot, so
     * that he can go back to race mode. Calling this function frees this slot for another player to
     * connect. Only available to Admin.
     *
     * @param string $login
     *
     * @return bool
     */
    public function spectatorReleasePlayerSlot($login);

    /**
     * Pass the playerid of the spectator. A spectator that once was a player keeps his player slot,
     * so that he can go back to race mode. Calling this function frees this slot for another player
     * to connect. Only available to Admin.
     *
     * @param int $playerId
     *
     * @return bool
     */
    public function spectatorReleasePlayerSlotId($playerId);

    /**
     * Enable control of the game flow: the game will wait for the caller to validate state
     * transitions. Only available to Admin.
     *
     * @param bool $enabled
     *
     * @return bool
     */
    public function manualFlowControlEnable($enabled);

    /**
     * Allows the game to proceed. Only available to Admin.
     *
     * @return bool
     */
    public function manualFlowControlProceed();

    /**
     * Returns whether the manual control of the game flow is enabled. 0 = no, 1 = yes by the
     * xml-rpc client making the call, 2 = yes, by some other xml-rpc client. Only available to
     * Admin.
     *
     * @return int
     */
    public function manualFlowControlIsEnabled();

    /**
     * Returns the transition that is currently blocked, or '' if none. (That's exactly the value
     * last received by the callback.) Only available to Admin.
     *
     * @return string
     */
    public function manualFlowControlGetCurTransition();

    /**
     * Returns the current match ending condition. Return values are: 'Playing', 'ChangeMap' or
     * 'Finished'.
     *
     * @return string
     */
    public function checkEndMatchCondition();

    /**
     * Returns a struct containing the networks stats of the server. The structure contains the
     * following fields : *Uptime*, *NbrConnection*, *MeanConnectionTime*, *MeanNbrPlayer*,
     * *RecvNetRate*, *SendNetRate*, *TotalReceivingSize*, *TotalSendingSize* and an array of
     * structures named *PlayerNetInfos*. Each structure of the array PlayerNetInfos contains the
     * following fields : *Login*, *IPAddress*, *LastTransferTime*, *DeltaBetweenTwoLastNetState*,
     * *PacketLossRate*. Only available to SuperAdmin.
     *
     * @return array
     */
    public function getNetworkStats();

    /**
     * Start a server on lan, using the current configuration. Only available to SuperAdmin.
     *
     * @return bool
     */
    public function startServerLan();

    /**
     * Start a server on internet using the 'Login' and 'Password' specified in the struct passed as
     * parameters. Only available to SuperAdmin.
     *
     * @param array $credentials
     *
     * @return bool
     */
    public function startServerInternet($credentials);

    /**
     * Returns the current status of the server.
     *
     * @return array
     */
    public function getStatus();

    /**
     * Quit the application. Only available to SuperAdmin.
     *
     * @return bool
     */
    public function quitGame();

    /**
     * Returns the path of the game datas directory. Only available to Admin.
     *
     * @return string
     */
    public function gameDataDirectory();

    /**
     * Returns the path of the tracks directory. Only available to Admin.
     *
     * @return string
     */
    public function getTracksDirectory();

    /**
     * Returns the path of the skins directory. Only available to Admin.
     *
     * @return string
     */
    public function getSkinsDirectory();
}


abstract class ClientLogging
{
    protected $client;

    protected function logError($methodName)
    {
        print("Query method {$methodName} failed with code {$this->client->getErrorCode()}: {$this->client->getErrorMessage()}");
        $this->client->resetError();
    }

    protected function logMulticallErrors($calls, $results)
    {
        if (is_array($results))
        {
            $length = count($results);
            for ($i = 0; $i < $length; $i++)
            {
                $result = $results[$i];
                if (isset($result['faultCode']) && isset($result['faultString']))
                {
                    $methodName = $calls[$i]['methodName'];
                    print("Multicall method {$methodName} failed with code {$result['faultCode']}: {$result['faultString']}");
                }
            }
        }
        elseif ($results === false)
        {
            $this->logError('system.multicall');
        }
    }
}


/**
 * XML-RPC functions for querying the dedicated server.
 *
 * The functions in this class perform queries immediately as opposed to GbxClientMulticall.
 */
class GbxClient extends ClientLogging implements TmForeverMethods
{
    /**
     * Instantiates a new API class using an existing XML-RPC connection. This constructor does not
     * establish a connection to the server.
     *
     * @param IXR_Client_Gbx $client The XML-RPC connection to use.
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    private function query($args)
    {
        $success = call_user_func_array(array($this->client, 'query'), func_get_args());
        // $success = call_user_func_array($this->client->query, func_get_args());
        // $success = call_user_func_array('$this->client->query', func_get_args());
        if ($success)
        {
            return $this->client->getResponse();
        }
        else
        {
            $this->logError(func_get_arg(0));
            return false;
        }
    }

    public function listMethods()
    {
        return $this->query('system.listMethods');
    }

    public function methodSignature($methodName)
    {
        return $this->query('system.methodSignature', $methodName);
    }

    public function methodHelp($methodName)
    {
        return $this->query('system.methodHelp', $methodName);
    }

    /**
     * Process an array of calls, and return an array of results. Calls should be structs of the
     * form {'methodName': string, 'params': array}. Each result will either be a single-item array
     * containing the result value, or a struct of the form {'faultCode': int, 'faultString':
     * string}. This is useful when you need to make lots of small calls without lots of round
     * trips.
     *
     * Consider using the GbxClientMulticall class for fluent multicall query building.
     *
     * @param array $calls
     *
     * @return array
     */
    public function multicall($calls)
    {
        $calls = $this->client->calls;
        $result = $this->query('system.multicall', $calls);
        $this->logMulticallErrors($calls, $result);
        return $result;
    }

    public function authenticate($login, $password)
    {
        return $this->query('Authenticate', $login, $password);
    }

    public function changeAuthPassword($login, $password)
    {
        return $this->query('ChangeAuthPassword', $login, $password);
    }

    public function enableCallbacks($enabled)
    {
        return $this->query('EnableCallbacks', $enabled);
    }

    public function getVersion()
    {
        return $this->query('GetVersion');
    }

    public function callVote($request)
    {
        return $this->query('CallVote', $request);
    }

    public function callVoteEx($request, $ratio, $timeout, $whoIsVoting)
    {
        return $this->query('CallVoteEx', $request, $ratio, $timeout, $whoIsVoting);
    }

    public function internalCallVote()
    {
        return $this->query('InternalCallVote');
    }

    public function cancelVote()
    {
        return $this->query('CancelVote');
    }

    public function getCurrentCallVote()
    {
        return $this->query('GetCurrentCallVote');
    }

    public function setCallVoteTimeOut($callVoteTimeout)
    {
        return $this->query('SetCallVoteTimeOut', $callVoteTimeout);
    }

    public function getCallVoteTimeOut()
    {
        return $this->query('GetCallVoteTimeOut');
    }

    public function setCallVoteRatio($callVoteRatio)
    {
        return $this->query('SetCallVoteRatio', $callVoteRatio);
    }

    public function getCallVoteRatio()
    {
        return $this->query('GetCallVoteRatio');
    }

    public function setCallVoteRatios($callVoteRatios)
    {
        return $this->query('SetCallVoteRatios', $callVoteRatios);
    }

    public function getCallVoteRatios()
    {
        return $this->query('GetCallVoteRatios');
    }

    public function chatSendServerMessage($text)
    {
        return $this->query('ChatSendServerMessage', $text);
    }

    public function chatSendServerMessageToLanguage($mapping, $login = null)
    {
        return $this->query('ChatSendServerMessageToLanguage', $mapping, $login);
    }

    public function chatSendServerMessageToId($text, $playerId)
    {
        return $this->query('ChatSendServerMessageToId', $text, $playerId);
    }

    public function chatSendServerMessageToLogin($text, $login)
    {
        return $this->query('ChatSendServerMessageToLogin', $text, $login);
    }

    public function chatSend($text)
    {
        return $this->query('ChatSend', $text);
    }

    public function chatSendToLanguage($mapping, $login = null)
    {
        return $this->query('ChatSendToLanguage', $mapping, $login);
    }

    public function chatSendToLogin($text, $login)
    {
        return $this->query('ChatSendToLogin', $text, $login);
    }

    public function chatSendToId($text, $playerId)
    {
        return $this->query('ChatSendToId', $text, $playerId);
    }

    public function getChatLines()
    {
        return $this->query('GetChatLines');
    }

    public function chatEnableManualRouting($enabled, $forwardMessages = null)
    {
        return $this->query('ChatEnableManualRouting', $enabled, $forwardMessages);
    }

    public function chatForwardToLogin($text, $senderLogin, $destLogin)
    {
        return $this->query('ChatForwardToLogin', $text, $senderLogin, $destLogin);
    }

    public function sendNotice($text, $avatarLogin, $maxDuration = null)
    {
        return $this->query('SendNotice', $text, $avatarLogin, $maxDuration);
    }

    public function sendNoticeToId($clientUid, $text, $avatarUid, $maxDuration = null)
    {
        return $this->query('SendNoticeToId', $clientUid, $text, $avatarUid, $maxDuration);
    }

    public function sendNoticeToLogin($clientLogin, $text, $avatarLogin, $maxDuration = null)
    {
        return $this->query('SendNoticeToLogin', $clientLogin, $text, $avatarLogin, $maxDuration);
    }

    public function sendDisplayManialinkPage($manialink, $timeout, $hideOnClick)
    {
        return $this->query('SendDisplayManialinkPage', $manialink, $timeout, $hideOnClick);
    }

    public function sendDisplayManialinkPageToId($uid, $manialink, $timeout, $hideOnClick)
    {
        return $this->query('SendDisplayManialinkPageToId', $uid, $manialink, $timeout, $hideOnClick);
    }

    public function sendDisplayManialinkPageToLogin($login, $manialink, $timeout, $hideOnClick)
    {
        return $this->query('SendDisplayManialinkPageToLogin', $login, $manialink, $timeout, $hideOnClick);
    }

    public function sendHideManialinkPage()
    {
        return $this->query('SendHideManialinkPage');
    }

    public function sendHideManialinkPageToId($uid)
    {
        return $this->query('SendHideManialinkPageToId', $uid);
    }

    public function sendHideManialinkPageToLogin($login)
    {
        return $this->query('SendHideManialinkPageToLogin', $login);
    }

    public function getManialinkPageAnswers($login)
    {
        return $this->query('GetManialinkPageAnswers', $login);
    }

    public function kick($login, $message = null)
    {
        return $this->query('Kick', $login, $message);
    }

    public function kickId($playerId, $message = null)
    {
        return $this->query('KickId', $playerId, $message);
    }

    public function ban($login, $message = null)
    {
        return $this->query('Ban', $login, $message);
    }

    public function banAndBlackList($login, $message, $saveBlackList)
    {
        return $this->query('BanAndBlackList', $login, $message, $saveBlackList);
    }

    public function banId($playerId, $message = null)
    {
        return $this->query('BanId', $playerId, $message);
    }

    public function unBan($clientName)
    {
        return $this->query('UnBan', $clientName);
    }

    public function cleanBanList()
    {
        return $this->query('CleanBanList');
    }

    public function getBanList($maxInfos, $startingIndex)
    {
        return $this->query('GetBanList', $maxInfos, $startingIndex);
    }

    public function blackList($login)
    {
        return $this->query('BlackList', $login);
    }

    public function blackListId($playerId)
    {
        return $this->query('BlackListId', $playerId);
    }

    public function unBlackList($login)
    {
        return $this->query('UnBlackList', $login);
    }

    public function cleanBlackList($login)
    {
        return $this->query('CleanBlackList', $login);
    }

    public function getBlackList($maxInfos, $startingIndex)
    {
        return $this->query('GetBlackList', $maxInfos, $startingIndex);
    }

    public function loadBlackList($fileName)
    {
        return $this->query('LoadBlackList', $fileName);
    }

    public function saveBlackList($fileName)
    {
        return $this->query('SaveBlackList', $fileName);
    }

    public function addGuest($login)
    {
        return $this->query('AddGuest', $login);
    }

    public function addGuestId($playerId)
    {
        return $this->query('AddGuestId', $playerId);
    }

    public function removeGuest($login)
    {
        return $this->query('RemoveGuest', $login);
    }

    public function removeGuestId($playerId)
    {
        return $this->query('RemoveGuestId', $playerId);
    }

    public function cleanGuestList()
    {
        return $this->query('CleanGuestList');
    }

    public function getGuestList($maxInfos, $startingIndex)
    {
        return $this->query('GetGuestList', $maxInfos, $startingIndex);
    }

    public function loadGuestList($fileName)
    {
        return $this->query('LoadGuestList', $fileName);
    }

    public function saveGuestList($fileName)
    {
        return $this->query('SaveGuestList', $fileName);
    }

    public function setBuddyNotification($login, $enabled)
    {
        return $this->query('SetBuddyNotification', $login, $enabled);
    }

    public function getBuddyNotification($login)
    {
        return $this->query('GetBuddyNotification', $login);
    }

    public function writeFile($fileName, $data)
    {
        return $this->query('WriteFile', $fileName, $data);
    }

    public function tunnelSendDataToId($fileName, $data)
    {
        return $this->query('TunnelSendDataToId', $fileName, $data);
    }

    public function tunnelSendDataToLogin($fileName, $data)
    {
        return $this->query('TunnelSendDataToLogin', $fileName, $data);
    }

    public function echo($param1, $param2)
    {
        return $this->query('Echo', $param1, $param2);
    }

    public function ignore($login)
    {
        return $this->query('Ignore', $login);
    }

    public function ignoreId($playerId)
    {
        return $this->query('IgnoreId', $playerId);
    }

    public function unIgnore($login)
    {
        return $this->query('UnIgnore', $login);
    }

    public function unIgnoreId($playerId)
    {
        return $this->query('UnIgnoreId', $playerId);
    }

    public function cleanIgnoreList()
    {
        return $this->query('CleanIgnoreList');
    }

    public function getIgnoreList($maxInfos, $startingIndex)
    {
        return $this->query('GetIgnoreList', $maxInfos, $startingIndex);
    }

    public function pay($login, $coppers, $label)
    {
        return $this->query('Pay', $login, $coppers, $label);
    }

    public function sendBill($loginFrom, $coppers, $label, $loginTo = null)
    {
        return $this->query('SendBill', $loginFrom, $coppers, $label, $loginTo);
    }

    public function getBillState($billId)
    {
        return $this->query('GetBillState', $billId);
    }

    public function getServerCoppers()
    {
        return $this->query('GetServerCoppers');
    }

    public function getSystemInfo()
    {
        return $this->query('GetSystemInfo');
    }

    public function setConnectionRates($downloadRate, $uploadRate)
    {
        return $this->query('SetConnectionRates', $downloadRate, $uploadRate);
    }

    public function setServerName($serverName)
    {
        return $this->query('SetServerName', $serverName);
    }

    public function getServerName()
    {
        return $this->query('GetServerName');
    }

    public function setServerComment($serverComment)
    {
        return $this->query('SetServerComment', $serverComment);
    }

    public function getServerComment()
    {
        return $this->query('GetServerComment');
    }

    public function setHideServer($hideServer)
    {
        return $this->query('SetHideServer', $hideServer);
    }

    public function getHideServer()
    {
        return $this->query('GetHideServer');
    }

    public function isRelayServer()
    {
        return $this->query('IsRelayServer');
    }

    public function setServerPassword($serverPassword)
    {
        return $this->query('SetServerPassword', $serverPassword);
    }

    public function getServerPassword()
    {
        return $this->query('GetServerPassword');
    }

    public function setServerPasswordForSpectator($serverPasswordForSpectator)
    {
        return $this->query('SetServerPasswordForSpectator', $serverPasswordForSpectator);
    }

    public function getServerPasswordForSpectator()
    {
        return $this->query('GetServerPasswordForSpectator');
    }

    public function setMaxPlayers($maxPlayers)
    {
        return $this->query('SetMaxPlayers', $maxPlayers);
    }

    public function getMaxPlayers()
    {
        return $this->query('GetMaxPlayers');
    }

    public function setMaxSpectators($maxSpectators)
    {
        return $this->query('SetMaxSpectators', $maxSpectators);
    }

    public function getMaxSpectators()
    {
        return $this->query('GetMaxSpectators');
    }

    public function enableP2PUpload($enabled)
    {
        return $this->query('EnableP2PUpload', $enabled);
    }

    public function isP2PUpload()
    {
        return $this->query('IsP2PUpload');
    }

    public function enableP2PDownload($enabled)
    {
        return $this->query('EnableP2PDownload', $enabled);
    }

    public function isP2PDownload()
    {
        return $this->query('IsP2PDownload');
    }

    public function allowChallengeDownload($enabled)
    {
        return $this->query('AllowChallengeDownload', $enabled);
    }

    public function isChallengeDownloadAllowed()
    {
        return $this->query('IsChallengeDownloadAllowed');
    }

    public function autoSaveReplays($enabled)
    {
        return $this->query('AutoSaveReplays', $enabled);
    }

    public function autoSaveValidationReplays($enabled)
    {
        return $this->query('AutoSaveValidationReplays', $enabled);
    }

    public function isAutoSaveReplaysEnabled()
    {
        return $this->query('IsAutoSaveReplaysEnabled');
    }

    public function isAutoSaveValidationReplaysEnabled()
    {
        return $this->query('IsAutoSaveValidationReplaysEnabled');
    }

    public function saveCurrentReplay($fileName)
    {
        return $this->query('SaveCurrentReplay', $fileName);
    }

    public function saveBestGhostsReplay($login, $fileName)
    {
        return $this->query('SaveBestGhostsReplay', $login, $fileName);
    }

    public function getValidationReplay($login)
    {
        return $this->query('GetValidationReplay', $login);
    }

    public function setLadderMode($ladderMode)
    {
        return $this->query('SetLadderMode', $ladderMode);
    }

    public function getLadderMode()
    {
        return $this->query('GetLadderMode');
    }

    public function getLadderServerLimits()
    {
        return $this->query('GetLadderServerLimits');
    }

    public function setVehicleNetQuality($vehicleNetQuality)
    {
        return $this->query('SetVehicleNetQuality', $vehicleNetQuality);
    }

    public function getVehicleNetQuality()
    {
        return $this->query('GetVehicleNetQuality');
    }

    public function setServerOptions($serverOptions)
    {
        return $this->query('SetServerOptions', $serverOptions);
    }

    public function getServerOptions($structVersion = null)
    {
        return $this->query('GetServerOptions', $structVersion);
    }

    public function setServerPackMask($serverPackMask)
    {
        return $this->query('SetServerPackMask', $serverPackMask);
    }

    public function getServerPackMask()
    {
        return $this->query('GetServerPackMask');
    }

    public function setForcedMods($override, $mods)
    {
        return $this->query('SetForcedMods', $override, $mods);
    }

    public function getForcedMods()
    {
        return $this->query('GetForcedMods');
    }

    public function setForcedMusic($override, $urlOrFileName)
    {
        return $this->query('SetForcedMusic', $override, $urlOrFileName);
    }

    public function getForcedMusic()
    {
        return $this->query('GetForcedMusic');
    }

    public function setForcedSkins($remappings)
    {
        return $this->query('SetForcedSkins', $remappings);
    }

    public function getForcedSkins()
    {
        return $this->query('GetForcedSkins');
    }

    public function getLastConnectionErrorMessage()
    {
        return $this->query('GetLastConnectionErrorMessage');
    }

    public function setRefereePassword($refereePassword)
    {
        return $this->query('SetRefereePassword', $refereePassword);
    }

    public function getRefereePassword()
    {
        return $this->query('GetRefereePassword');
    }

    public function setRefereeMode($refereeMode)
    {
        return $this->query('SetRefereeMode', $refereeMode);
    }

    public function getRefereeMode()
    {
        return $this->query('GetRefereeMode');
    }

    public function setUseChangingValidationSeed($useChangingValidationSeed)
    {
        return $this->query('SetUseChangingValidationSeed', $useChangingValidationSeed);
    }

    public function getUseChangingValidationSeed()
    {
        return $this->query('GetUseChangingValidationSeed');
    }

    public function setWarmUp($warmUp)
    {
        return $this->query('SetWarmUp', $warmUp);
    }

    public function getWarmUp()
    {
        return $this->query('GetWarmUp');
    }

    public function challengeRestart($dontClearCupScores = null)
    {
        return $this->query('ChallengeRestart', $dontClearCupScores);
    }

    public function restartChallenge($dontClearCupScores = null)
    {
        return $this->query('RestartChallenge', $dontClearCupScores);
    }

    public function nextChallenge($dontClearCupScores = null)
    {
        return $this->query('NextChallenge', $dontClearCupScores);
    }

    public function stopServer()
    {
        return $this->query('StopServer');
    }

    public function forceEndRound()
    {
        return $this->query('ForceEndRound');
    }

    public function setGameInfos($gameInfos)
    {
        return $this->query('SetGameInfos', $gameInfos);
    }

    public function getCurrentGameInfo($structVersion = null)
    {
        return $this->query('GetCurrentGameInfo', $structVersion);
    }

    public function getNextGameInfo($structVersion = null)
    {
        return $this->query('GetNextGameInfo', $structVersion);
    }

    public function getGameInfos($structVersion = null)
    {
        return $this->query('GetGameInfos', $structVersion);
    }

    public function setGameMode($gameMode)
    {
        return $this->query('SetGameMode', $gameMode);
    }

    public function getGameMode()
    {
        return $this->query('GetGameMode');
    }

    public function setChatTime($chatTime)
    {
        return $this->query('SetChatTime', $chatTime);
    }

    public function getChatTime()
    {
        return $this->query('GetChatTime');
    }

    public function setFinishTimeout($finishTimeout)
    {
        return $this->query('SetFinishTimeout', $finishTimeout);
    }

    public function getFinishTimeout()
    {
        return $this->query('GetFinishTimeout');
    }

    public function setAllWarmUpDuration($allWarmupDuration)
    {
        return $this->query('SetAllWarmUpDuration', $allWarmupDuration);
    }

    public function getAllWarmUpDuration()
    {
        return $this->query('GetAllWarmUpDuration');
    }

    public function setDisableRespawn($disableRespawn)
    {
        return $this->query('SetDisableRespawn', $disableRespawn);
    }

    public function getDisableRespawn()
    {
        return $this->query('GetDisableRespawn');
    }

    public function setForceShowAllOpponents($forceShowAllOpponents)
    {
        return $this->query('SetForceShowAllOpponents', $forceShowAllOpponents);
    }

    public function getForceShowAllOpponents()
    {
        return $this->query('GetForceShowAllOpponents');
    }

    public function setTimeAttackLimit($timeAttackLimit)
    {
        return $this->query('SetTimeAttackLimit', $timeAttackLimit);
    }

    public function getTimeAttackLimit()
    {
        return $this->query('GetTimeAttackLimit');
    }

    public function setTimeAttackSynchStartPeriod($timeAttackSynchStartPeriod)
    {
        return $this->query('SetTimeAttackSynchStartPeriod', $timeAttackSynchStartPeriod);
    }

    public function getTimeAttackSynchStartPeriod()
    {
        return $this->query('GetTimeAttackSynchStartPeriod');
    }

    public function setLapsTimeLimit($lapsTimeLimit)
    {
        return $this->query('SetLapsTimeLimit', $lapsTimeLimit);
    }

    public function getLapsTimeLimit()
    {
        return $this->query('GetLapsTimeLimit');
    }

    public function setNbLaps($nbLaps)
    {
        return $this->query('SetNbLaps', $nbLaps);
    }

    public function getNbLaps()
    {
        return $this->query('GetNbLaps');
    }

    public function setRoundForcedLaps($roundForcedLaps)
    {
        return $this->query('SetRoundForcedLaps', $roundForcedLaps);
    }

    public function getRoundForcedLaps()
    {
        return $this->query('GetRoundForcedLaps');
    }

    public function setRoundPointsLimit($roundPointsLimit)
    {
        return $this->query('SetRoundPointsLimit', $roundPointsLimit);
    }

    public function getRoundPointsLimit()
    {
        return $this->query('GetRoundPointsLimit');
    }

    public function setRoundCustomPoints($points, $relaxConstraints)
    {
        return $this->query('SetRoundCustomPoints', $points, $relaxConstraints);
    }

    public function getRoundCustomPoints()
    {
        return $this->query('GetRoundCustomPoints');
    }

    public function setUseNewRulesRound($useNewRulesRound)
    {
        return $this->query('SetUseNewRulesRound', $useNewRulesRound);
    }

    public function getUseNewRulesRound()
    {
        return $this->query('GetUseNewRulesRound');
    }

    public function setTeamPointsLimit($teamPointsLimit)
    {
        return $this->query('SetTeamPointsLimit', $teamPointsLimit);
    }

    public function getTeamPointsLimit()
    {
        return $this->query('GetTeamPointsLimit');
    }

    public function setMaxPointsTeam($maxPointsTeam)
    {
        return $this->query('SetMaxPointsTeam', $maxPointsTeam);
    }

    public function getMaxPointsTeam()
    {
        return $this->query('GetMaxPointsTeam');
    }

    public function setUseNewRulesTeam($useNewRulesTeam)
    {
        return $this->query('SetUseNewRulesTeam', $useNewRulesTeam);
    }

    public function getUseNewRulesTeam()
    {
        return $this->query('GetUseNewRulesTeam');
    }

    public function setCupPointsLimit($cupPointsLimit)
    {
        return $this->query('SetCupPointsLimit', $cupPointsLimit);
    }

    public function getCupPointsLimit()
    {
        return $this->query('GetCupPointsLimit');
    }

    public function setCupRoundsPerChallenge($cupRoundsPerChallenge)
    {
        return $this->query('SetCupRoundsPerChallenge', $cupRoundsPerChallenge);
    }

    public function getCupRoundsPerChallenge()
    {
        return $this->query('GetCupRoundsPerChallenge');
    }

    public function setCupWarmUpDuration($cupWarmUpDuration)
    {
        return $this->query('SetCupWarmUpDuration', $cupWarmUpDuration);
    }

    public function getCupWarmUpDuration()
    {
        return $this->query('GetCupWarmUpDuration');
    }

    public function setCupNbWinners($cupNbWinners)
    {
        return $this->query('SetCupNbWinners', $cupNbWinners);
    }

    public function getCupNbWinners()
    {
        return $this->query('GetCupNbWinners');
    }

    public function getCurrentChallengeIndex()
    {
        return $this->query('GetCurrentChallengeIndex');
    }

    public function getNextChallengeIndex()
    {
        return $this->query('GetNextChallengeIndex');
    }

    public function setNextChallengeIndex($nextChallengeIndex)
    {
        return $this->query('SetNextChallengeIndex', $nextChallengeIndex);
    }

    public function getCurrentChallengeInfo()
    {
        return $this->query('GetCurrentChallengeInfo');
    }

    public function getNextChallengeInfo()
    {
        return $this->query('GetNextChallengeInfo');
    }

    public function getChallengeInfo($fileName)
    {
        return $this->query('GetChallengeInfo', $fileName);
    }

    public function checkChallengeForCurrentServerParams($fileName)
    {
        return $this->query('CheckChallengeForCurrentServerParams', $fileName);
    }

    public function getChallengeList($maxInfos, $startingIndex)
    {
        return $this->query('GetChallengeList', $maxInfos, $startingIndex);
    }

    public function addChallenge($fileName)
    {
        return $this->query('AddChallenge', $fileName);
    }

    public function addChallengeList($fileNames)
    {
        return $this->query('AddChallengeList', $fileNames);
    }

    public function removeChallenge($fileName)
    {
        return $this->query('RemoveChallenge', $fileName);
    }

    public function removeChallengeList($fileNames)
    {
        return $this->query('RemoveChallengeList', $fileNames);
    }

    public function insertChallenge($fileName)
    {
        return $this->query('InsertChallenge', $fileName);
    }

    public function insertChallengeList($fileNames)
    {
        return $this->query('InsertChallengeList', $fileNames);
    }

    public function chooseNextChallenge($fileName)
    {
        return $this->query('ChooseNextChallenge', $fileName);
    }

    public function chooseNextChallengeList($fileNames)
    {
        return $this->query('ChooseNextChallengeList', $fileNames);
    }

    public function loadMatchSettings($fileName)
    {
        return $this->query('LoadMatchSettings', $fileName);
    }

    public function appendPlaylistFromMatchSettings($fileName)
    {
        return $this->query('AppendPlaylistFromMatchSettings', $fileName);
    }

    public function saveMatchSettings($fileName)
    {
        return $this->query('SaveMatchSettings', $fileName);
    }

    public function insertPlaylistFromMatchSettings($fileName)
    {
        return $this->query('InsertPlaylistFromMatchSettings', $fileName);
    }

    public function getPlayerList($maxInfos, $startingIndex, $structVersion = null)
    {
        return $this->query('GetPlayerList', $maxInfos, $startingIndex, $structVersion);
    }

    public function getPlayerInfo($login, $structVersion = null)
    {
        return $this->query('GetPlayerInfo', $login, $structVersion);
    }

    public function getDetailedPlayerInfo($login)
    {
        return $this->query('GetDetailedPlayerInfo', $login);
    }

    public function getMainServerPlayerInfo($structVersion = null)
    {
        return $this->query('GetMainServerPlayerInfo', $structVersion);
    }

    public function getCurrentRanking($maxInfos, $startingIndex)
    {
        return $this->query('GetCurrentRanking', $maxInfos, $startingIndex);
    }

    public function getCurrentRankingForLogin($login)
    {
        return $this->query('GetCurrentRankingForLogin', $login);
    }

    public function forceScores($scores, $silentMode)
    {
        return $this->query('ForceScores', $scores, $silentMode);
    }

    public function forcePlayerTeam($login, $teamNumber)
    {
        return $this->query('ForcePlayerTeam', $login, $teamNumber);
    }

    public function forcePlayerTeamId($playerId, $teamNumber)
    {
        return $this->query('ForcePlayerTeamId', $playerId, $teamNumber);
    }

    public function forceSpectator($login, $spectatorMode)
    {
        return $this->query('ForceSpectator', $login, $spectatorMode);
    }

    public function forceSpectatorId($playerId, $spectatorMode)
    {
        return $this->query('ForceSpectatorId', $playerId, $spectatorMode);
    }

    public function forceSpectatorTarget($spectatorLogin, $targetLogin, $cameraType)
    {
        return $this->query('ForceSpectatorTarget', $spectatorLogin, $targetLogin, $cameraType);
    }

    public function forceSpectatorTargetId($spectatorLogin, $targetLogin, $cameraType)
    {
        return $this->query('ForceSpectatorTargetId', $spectatorLogin, $targetLogin, $cameraType);
    }

    public function spectatorReleasePlayerSlot($login)
    {
        return $this->query('SpectatorReleasePlayerSlot', $login);
    }

    public function spectatorReleasePlayerSlotId($playerId)
    {
        return $this->query('SpectatorReleasePlayerSlotId', $playerId);
    }

    public function manualFlowControlEnable($enabled)
    {
        return $this->query('ManualFlowControlEnable', $enabled);
    }

    public function manualFlowControlProceed()
    {
        return $this->query('ManualFlowControlProceed');
    }

    public function manualFlowControlIsEnabled()
    {
        return $this->query('ManualFlowControlIsEnabled');
    }

    public function manualFlowControlGetCurTransition()
    {
        return $this->query('ManualFlowControlGetCurTransition');
    }

    public function checkEndMatchCondition()
    {
        return $this->query('CheckEndMatchCondition');
    }

    public function getNetworkStats()
    {
        return $this->query('GetNetworkStats');
    }

    public function startServerLan()
    {
        return $this->query('StartServerLan');
    }

    public function startServerInternet($credentials)
    {
        return $this->query('StartServerInternet', $credentials);
    }

    public function getStatus()
    {
        return $this->query('GetStatus');
    }

    public function quitGame()
    {
        return $this->query('QuitGame');
    }

    public function gameDataDirectory()
    {
        return $this->query('GameDataDirectory');
    }

    public function getTracksDirectory()
    {
        return $this->query('GetTracksDirectory');
    }

    public function getSkinsDirectory()
    {
        return $this->query('GetSkinsDirectory');
    }
}


/**
 * XML-RPC methods for performing multicall queries against the dedicated server.
 *
 * Methods in this class do not perform queries themselves; instead, method calls are added until
 * they are submitted using the submit() method.
 */
class GbxClientMulticall extends ClientLogging implements TmForeverMethods
{
    /**
     * Instantiates a new multicall client.
     *
     * @param IXR_ClientMulticall_Gbx $client The XML-RPC connection to use.
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    private function addCall()
    {
        call_user_func_array(array($this->client, 'addCall'), func_get_args());
        return $this;
    }

    /**
     * Submits the multicall query.
     *
     * Each result will either be a single-item array containing the result value, or a struct of
     * the form {'faultCode': int, 'faultString': string}.
     *
     * @return array
     */
    public function submit()
    {
        $calls = $this->client->calls;
        $result = $this->client->multiquery();
        $this->logMulticallErrors($calls, $result);
        return $result;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function listMethods()
    {
        $this->addCall('system.listMethods');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function methodSignature($methodName)
    {
        $this->addCall('system.methodSignature', $methodName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function methodHelp($methodName)
    {
        $this->addCall('system.methodHelp', $methodName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function authenticate($login, $password)
    {
        $this->addCall('Authenticate', $login, $password);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function changeAuthPassword($login, $password)
    {
        $this->addCall('ChangeAuthPassword', $login, $password);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function enableCallbacks($enabled)
    {
        $this->addCall('EnableCallbacks', $enabled);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getVersion()
    {
        $this->addCall('GetVersion');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function callVote($request)
    {
        $this->addCall('CallVote', $request);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function callVoteEx($request, $ratio, $timeout, $whoIsVoting)
    {
        $this->addCall('CallVoteEx', $request, $ratio, $timeout, $whoIsVoting);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function internalCallVote()
    {
        $this->addCall('InternalCallVote');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function cancelVote()
    {
        $this->addCall('CancelVote');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCurrentCallVote()
    {
        $this->addCall('GetCurrentCallVote');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setCallVoteTimeOut($callVoteTimeout)
    {
        $this->addCall('SetCallVoteTimeOut', $callVoteTimeout);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCallVoteTimeOut()
    {
        $this->addCall('GetCallVoteTimeOut');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setCallVoteRatio($callVoteRatio)
    {
        $this->addCall('SetCallVoteRatio', $callVoteRatio);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCallVoteRatio()
    {
        $this->addCall('GetCallVoteRatio');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setCallVoteRatios($callVoteRatios)
    {
        $this->addCall('SetCallVoteRatios', $callVoteRatios);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCallVoteRatios()
    {
        $this->addCall('GetCallVoteRatios');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatSendServerMessage($text)
    {
        $this->addCall('ChatSendServerMessage', $text);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatSendServerMessageToLanguage($mapping, $login = null)
    {
        $this->addCall('ChatSendServerMessageToLanguage', $mapping, $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatSendServerMessageToId($text, $playerId)
    {
        $this->addCall('ChatSendServerMessageToId', $text, $playerId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatSendServerMessageToLogin($text, $login)
    {
        $this->addCall('ChatSendServerMessageToLogin', $text, $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatSend($text)
    {
        $this->addCall('ChatSend', $text);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatSendToLanguage($mapping, $login = null)
    {
        $this->addCall('ChatSendToLanguage', $mapping, $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatSendToLogin($text, $login)
    {
        $this->addCall('ChatSendToLogin', $text, $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatSendToId($text, $playerId)
    {
        $this->addCall('ChatSendToId', $text, $playerId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getChatLines()
    {
        $this->addCall('GetChatLines');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatEnableManualRouting($enabled, $forwardMessages = null)
    {
        $this->addCall('ChatEnableManualRouting', $enabled, $forwardMessages);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chatForwardToLogin($text, $senderLogin, $destLogin)
    {
        $this->addCall('ChatForwardToLogin', $text, $senderLogin, $destLogin);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendNotice($text, $avatarLogin, $maxDuration = null)
    {
        $this->addCall('SendNotice', $text, $avatarLogin, $maxDuration);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendNoticeToId($clientUid, $text, $avatarUid, $maxDuration = null)
    {
        $this->addCall('SendNoticeToId', $clientUid, $text, $avatarUid, $maxDuration);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendNoticeToLogin($clientLogin, $text, $avatarLogin, $maxDuration = null)
    {
        $this->addCall('SendNoticeToLogin', $clientLogin, $text, $avatarLogin, $maxDuration);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendDisplayManialinkPage($manialink, $timeout, $hideOnClick)
    {
        $this->addCall('SendDisplayManialinkPage', $manialink, $timeout, $hideOnClick);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendDisplayManialinkPageToId($uid, $manialink, $timeout, $hideOnClick)
    {
        $this->addCall('SendDisplayManialinkPageToId', $uid, $manialink, $timeout, $hideOnClick);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendDisplayManialinkPageToLogin($login, $manialink, $timeout, $hideOnClick)
    {
        $this->addCall('SendDisplayManialinkPageToLogin', $login, $manialink, $timeout, $hideOnClick);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendHideManialinkPage()
    {
        $this->addCall('SendHideManialinkPage');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendHideManialinkPageToId($uid)
    {
        $this->addCall('SendHideManialinkPageToId', $uid);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendHideManialinkPageToLogin($login)
    {
        $this->addCall('SendHideManialinkPageToLogin', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getManialinkPageAnswers($login)
    {
        $this->addCall('GetManialinkPageAnswers', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function kick($login, $message = null)
    {
        $this->addCall('Kick', $login, $message);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function kickId($playerId, $message = null)
    {
        $this->addCall('KickId', $playerId, $message);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function ban($login, $message = null)
    {
        $this->addCall('Ban', $login, $message);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function banAndBlackList($login, $message, $saveBlackList)
    {
        $this->addCall('BanAndBlackList', $login, $message, $saveBlackList);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function banId($playerId, $message = null)
    {
        $this->addCall('BanId', $playerId, $message);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function unBan($clientName)
    {
        $this->addCall('UnBan', $clientName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function cleanBanList()
    {
        $this->addCall('CleanBanList');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getBanList($maxInfos, $startingIndex)
    {
        $this->addCall('GetBanList', $maxInfos, $startingIndex);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function blackList($login)
    {
        $this->addCall('BlackList', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function blackListId($playerId)
    {
        $this->addCall('BlackListId', $playerId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function unBlackList($login)
    {
        $this->addCall('UnBlackList', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function cleanBlackList($login)
    {
        $this->addCall('CleanBlackList', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getBlackList($maxInfos, $startingIndex)
    {
        $this->addCall('GetBlackList', $maxInfos, $startingIndex);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function loadBlackList($fileName)
    {
        $this->addCall('LoadBlackList', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function saveBlackList($fileName)
    {
        $this->addCall('SaveBlackList', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function addGuest($login)
    {
        $this->addCall('AddGuest', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function addGuestId($playerId)
    {
        $this->addCall('AddGuestId', $playerId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function removeGuest($login)
    {
        $this->addCall('RemoveGuest', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function removeGuestId($playerId)
    {
        $this->addCall('RemoveGuestId', $playerId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function cleanGuestList()
    {
        $this->addCall('CleanGuestList');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getGuestList($maxInfos, $startingIndex)
    {
        $this->addCall('GetGuestList', $maxInfos, $startingIndex);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function loadGuestList($fileName)
    {
        $this->addCall('LoadGuestList', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function saveGuestList($fileName)
    {
        $this->addCall('SaveGuestList', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setBuddyNotification($login, $enabled)
    {
        $this->addCall('SetBuddyNotification', $login, $enabled);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getBuddyNotification($login)
    {
        $this->addCall('GetBuddyNotification', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function writeFile($fileName, $data)
    {
        $this->addCall('WriteFile', $fileName, $data);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function tunnelSendDataToId($fileName, $data)
    {
        $this->addCall('TunnelSendDataToId', $fileName, $data);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function tunnelSendDataToLogin($fileName, $data)
    {
        $this->addCall('TunnelSendDataToLogin', $fileName, $data);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function echo($param1, $param2)
    {
        $this->addCall('Echo', $param1, $param2);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function ignore($login)
    {
        $this->addCall('Ignore', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function ignoreId($playerId)
    {
        $this->addCall('IgnoreId', $playerId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function unIgnore($login)
    {
        $this->addCall('UnIgnore', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function unIgnoreId($playerId)
    {
        $this->addCall('UnIgnoreId', $playerId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function cleanIgnoreList()
    {
        $this->addCall('CleanIgnoreList');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getIgnoreList($maxInfos, $startingIndex)
    {
        $this->addCall('GetIgnoreList', $maxInfos, $startingIndex);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function pay($login, $coppers, $label)
    {
        $this->addCall('Pay', $login, $coppers, $label);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function sendBill($loginFrom, $coppers, $label, $loginTo = null)
    {
        $this->addCall('SendBill', $loginFrom, $coppers, $label, $loginTo);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getBillState($billId)
    {
        $this->addCall('GetBillState', $billId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getServerCoppers()
    {
        $this->addCall('GetServerCoppers');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getSystemInfo()
    {
        $this->addCall('GetSystemInfo');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setConnectionRates($downloadRate, $uploadRate)
    {
        $this->addCall('SetConnectionRates', $downloadRate, $uploadRate);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setServerName($serverName)
    {
        $this->addCall('SetServerName', $serverName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getServerName()
    {
        $this->addCall('GetServerName');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setServerComment($serverComment)
    {
        $this->addCall('SetServerComment', $serverComment);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getServerComment()
    {
        $this->addCall('GetServerComment');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setHideServer($hideServer)
    {
        $this->addCall('SetHideServer', $hideServer);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getHideServer()
    {
        $this->addCall('GetHideServer');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function isRelayServer()
    {
        $this->addCall('IsRelayServer');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setServerPassword($serverPassword)
    {
        $this->addCall('SetServerPassword', $serverPassword);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getServerPassword()
    {
        $this->addCall('GetServerPassword');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setServerPasswordForSpectator($serverPasswordForSpectator)
    {
        $this->addCall('SetServerPasswordForSpectator', $serverPasswordForSpectator);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getServerPasswordForSpectator()
    {
        $this->addCall('GetServerPasswordForSpectator');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setMaxPlayers($maxPlayers)
    {
        $this->addCall('SetMaxPlayers', $maxPlayers);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getMaxPlayers()
    {
        $this->addCall('GetMaxPlayers');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setMaxSpectators($maxSpectators)
    {
        $this->addCall('SetMaxSpectators', $maxSpectators);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getMaxSpectators()
    {
        $this->addCall('GetMaxSpectators');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function enableP2PUpload($enabled)
    {
        $this->addCall('EnableP2PUpload', $enabled);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function isP2PUpload()
    {
        $this->addCall('IsP2PUpload');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function enableP2PDownload($enabled)
    {
        $this->addCall('EnableP2PDownload', $enabled);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function isP2PDownload()
    {
        $this->addCall('IsP2PDownload');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function allowChallengeDownload($enabled)
    {
        $this->addCall('AllowChallengeDownload', $enabled);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function isChallengeDownloadAllowed()
    {
        $this->addCall('IsChallengeDownloadAllowed');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function autoSaveReplays($enabled)
    {
        $this->addCall('AutoSaveReplays', $enabled);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function autoSaveValidationReplays($enabled)
    {
        $this->addCall('AutoSaveValidationReplays', $enabled);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function isAutoSaveReplaysEnabled()
    {
        $this->addCall('IsAutoSaveReplaysEnabled');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function isAutoSaveValidationReplaysEnabled()
    {
        $this->addCall('IsAutoSaveValidationReplaysEnabled');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function saveCurrentReplay($fileName)
    {
        $this->addCall('SaveCurrentReplay', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function saveBestGhostsReplay($login, $fileName)
    {
        $this->addCall('SaveBestGhostsReplay', $login, $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getValidationReplay($login)
    {
        $this->addCall('GetValidationReplay', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setLadderMode($ladderMode)
    {
        $this->addCall('SetLadderMode', $ladderMode);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getLadderMode()
    {
        $this->addCall('GetLadderMode');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getLadderServerLimits()
    {
        $this->addCall('GetLadderServerLimits');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setVehicleNetQuality($vehicleNetQuality)
    {
        $this->addCall('SetVehicleNetQuality', $vehicleNetQuality);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getVehicleNetQuality()
    {
        $this->addCall('GetVehicleNetQuality');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setServerOptions($serverOptions)
    {
        $this->addCall('SetServerOptions', $serverOptions);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getServerOptions($structVersion = null)
    {
        $this->addCall('GetServerOptions', $structVersion);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setServerPackMask($serverPackMask)
    {
        $this->addCall('SetServerPackMask', $serverPackMask);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getServerPackMask()
    {
        $this->addCall('GetServerPackMask');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setForcedMods($override, $mods)
    {
        $this->addCall('SetForcedMods', $override, $mods);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getForcedMods()
    {
        $this->addCall('GetForcedMods');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setForcedMusic($override, $urlOrFileName)
    {
        $this->addCall('SetForcedMusic', $override, $urlOrFileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getForcedMusic()
    {
        $this->addCall('GetForcedMusic');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setForcedSkins($remappings)
    {
        $this->addCall('SetForcedSkins', $remappings);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getForcedSkins()
    {
        $this->addCall('GetForcedSkins');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getLastConnectionErrorMessage()
    {
        $this->addCall('GetLastConnectionErrorMessage');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setRefereePassword($refereePassword)
    {
        $this->addCall('SetRefereePassword', $refereePassword);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getRefereePassword()
    {
        $this->addCall('GetRefereePassword');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setRefereeMode($refereeMode)
    {
        $this->addCall('SetRefereeMode', $refereeMode);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getRefereeMode()
    {
        $this->addCall('GetRefereeMode');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setUseChangingValidationSeed($useChangingValidationSeed)
    {
        $this->addCall('SetUseChangingValidationSeed', $useChangingValidationSeed);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getUseChangingValidationSeed()
    {
        $this->addCall('GetUseChangingValidationSeed');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setWarmUp($warmUp)
    {
        $this->addCall('SetWarmUp', $warmUp);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getWarmUp()
    {
        $this->addCall('GetWarmUp');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function challengeRestart($dontClearCupScores = null)
    {
        $this->addCall('ChallengeRestart', $dontClearCupScores);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function restartChallenge($dontClearCupScores = null)
    {
        $this->addCall('RestartChallenge', $dontClearCupScores);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function nextChallenge($dontClearCupScores = null)
    {
        $this->addCall('NextChallenge', $dontClearCupScores);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function stopServer()
    {
        $this->addCall('StopServer');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function forceEndRound()
    {
        $this->addCall('ForceEndRound');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setGameInfos($gameInfos)
    {
        $this->addCall('SetGameInfos', $gameInfos);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCurrentGameInfo($structVersion = null)
    {
        $this->addCall('GetCurrentGameInfo', $structVersion);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getNextGameInfo($structVersion = null)
    {
        $this->addCall('GetNextGameInfo', $structVersion);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getGameInfos($structVersion = null)
    {
        $this->addCall('GetGameInfos', $structVersion);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setGameMode($gameMode)
    {
        $this->addCall('SetGameMode', $gameMode);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getGameMode()
    {
        $this->addCall('GetGameMode');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setChatTime($chatTime)
    {
        $this->addCall('SetChatTime', $chatTime);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getChatTime()
    {
        $this->addCall('GetChatTime');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setFinishTimeout($finishTimeout)
    {
        $this->addCall('SetFinishTimeout', $finishTimeout);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getFinishTimeout()
    {
        $this->addCall('GetFinishTimeout');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setAllWarmUpDuration($allWarmupDuration)
    {
        $this->addCall('SetAllWarmUpDuration', $allWarmupDuration);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getAllWarmUpDuration()
    {
        $this->addCall('GetAllWarmUpDuration');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setDisableRespawn($disableRespawn)
    {
        $this->addCall('SetDisableRespawn', $disableRespawn);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getDisableRespawn()
    {
        $this->addCall('GetDisableRespawn');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setForceShowAllOpponents($forceShowAllOpponents)
    {
        $this->addCall('SetForceShowAllOpponents', $forceShowAllOpponents);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getForceShowAllOpponents()
    {
        $this->addCall('GetForceShowAllOpponents');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setTimeAttackLimit($timeAttackLimit)
    {
        $this->addCall('SetTimeAttackLimit', $timeAttackLimit);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getTimeAttackLimit()
    {
        $this->addCall('GetTimeAttackLimit');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setTimeAttackSynchStartPeriod($timeAttackSynchStartPeriod)
    {
        $this->addCall('SetTimeAttackSynchStartPeriod', $timeAttackSynchStartPeriod);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getTimeAttackSynchStartPeriod()
    {
        $this->addCall('GetTimeAttackSynchStartPeriod');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setLapsTimeLimit($lapsTimeLimit)
    {
        $this->addCall('SetLapsTimeLimit', $lapsTimeLimit);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getLapsTimeLimit()
    {
        $this->addCall('GetLapsTimeLimit');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setNbLaps($nbLaps)
    {
        $this->addCall('SetNbLaps', $nbLaps);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getNbLaps()
    {
        $this->addCall('GetNbLaps');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setRoundForcedLaps($roundForcedLaps)
    {
        $this->addCall('SetRoundForcedLaps', $roundForcedLaps);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getRoundForcedLaps()
    {
        $this->addCall('GetRoundForcedLaps');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setRoundPointsLimit($roundPointsLimit)
    {
        $this->addCall('SetRoundPointsLimit', $roundPointsLimit);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getRoundPointsLimit()
    {
        $this->addCall('GetRoundPointsLimit');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setRoundCustomPoints($points, $relaxConstraints)
    {
        $this->addCall('SetRoundCustomPoints', $points, $relaxConstraints);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getRoundCustomPoints()
    {
        $this->addCall('GetRoundCustomPoints');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setUseNewRulesRound($useNewRulesRound)
    {
        $this->addCall('SetUseNewRulesRound', $useNewRulesRound);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getUseNewRulesRound()
    {
        $this->addCall('GetUseNewRulesRound');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setTeamPointsLimit($teamPointsLimit)
    {
        $this->addCall('SetTeamPointsLimit', $teamPointsLimit);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getTeamPointsLimit()
    {
        $this->addCall('GetTeamPointsLimit');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setMaxPointsTeam($maxPointsTeam)
    {
        $this->addCall('SetMaxPointsTeam', $maxPointsTeam);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getMaxPointsTeam()
    {
        $this->addCall('GetMaxPointsTeam');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setUseNewRulesTeam($useNewRulesTeam)
    {
        $this->addCall('SetUseNewRulesTeam', $useNewRulesTeam);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getUseNewRulesTeam()
    {
        $this->addCall('GetUseNewRulesTeam');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setCupPointsLimit($cupPointsLimit)
    {
        $this->addCall('SetCupPointsLimit', $cupPointsLimit);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCupPointsLimit()
    {
        $this->addCall('GetCupPointsLimit');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setCupRoundsPerChallenge($cupRoundsPerChallenge)
    {
        $this->addCall('SetCupRoundsPerChallenge', $cupRoundsPerChallenge);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCupRoundsPerChallenge()
    {
        $this->addCall('GetCupRoundsPerChallenge');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setCupWarmUpDuration($cupWarmUpDuration)
    {
        $this->addCall('SetCupWarmUpDuration', $cupWarmUpDuration);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCupWarmUpDuration()
    {
        $this->addCall('GetCupWarmUpDuration');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setCupNbWinners($cupNbWinners)
    {
        $this->addCall('SetCupNbWinners', $cupNbWinners);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCupNbWinners()
    {
        $this->addCall('GetCupNbWinners');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCurrentChallengeIndex()
    {
        $this->addCall('GetCurrentChallengeIndex');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getNextChallengeIndex()
    {
        $this->addCall('GetNextChallengeIndex');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function setNextChallengeIndex($nextChallengeIndex)
    {
        $this->addCall('SetNextChallengeIndex', $nextChallengeIndex);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCurrentChallengeInfo()
    {
        $this->addCall('GetCurrentChallengeInfo');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getNextChallengeInfo()
    {
        $this->addCall('GetNextChallengeInfo');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getChallengeInfo($fileName)
    {
        $this->addCall('GetChallengeInfo', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function checkChallengeForCurrentServerParams($fileName)
    {
        $this->addCall('CheckChallengeForCurrentServerParams', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getChallengeList($maxInfos, $startingIndex)
    {
        $this->addCall('GetChallengeList', $maxInfos, $startingIndex);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function addChallenge($fileName)
    {
        $this->addCall('AddChallenge', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function addChallengeList($fileNames)
    {
        $this->addCall('AddChallengeList', $fileNames);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function removeChallenge($fileName)
    {
        $this->addCall('RemoveChallenge', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function removeChallengeList($fileNames)
    {
        $this->addCall('RemoveChallengeList', $fileNames);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function insertChallenge($fileName)
    {
        $this->addCall('InsertChallenge', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function insertChallengeList($fileNames)
    {
        $this->addCall('InsertChallengeList', $fileNames);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chooseNextChallenge($fileName)
    {
        $this->addCall('ChooseNextChallenge', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function chooseNextChallengeList($fileNames)
    {
        $this->addCall('ChooseNextChallengeList', $fileNames);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function loadMatchSettings($fileName)
    {
        $this->addCall('LoadMatchSettings', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function appendPlaylistFromMatchSettings($fileName)
    {
        $this->addCall('AppendPlaylistFromMatchSettings', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function saveMatchSettings($fileName)
    {
        $this->addCall('SaveMatchSettings', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function insertPlaylistFromMatchSettings($fileName)
    {
        $this->addCall('InsertPlaylistFromMatchSettings', $fileName);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getPlayerList($maxInfos, $startingIndex, $structVersion = null)
    {
        $this->addCall('GetPlayerList', $maxInfos, $startingIndex, $structVersion);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getPlayerInfo($login, $structVersion = null)
    {
        $this->addCall('GetPlayerInfo', $login, $structVersion);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getDetailedPlayerInfo($login)
    {
        $this->addCall('GetDetailedPlayerInfo', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getMainServerPlayerInfo($structVersion = null)
    {
        $this->addCall('GetMainServerPlayerInfo', $structVersion);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCurrentRanking($maxInfos, $startingIndex)
    {
        $this->addCall('GetCurrentRanking', $maxInfos, $startingIndex);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getCurrentRankingForLogin($login)
    {
        $this->addCall('GetCurrentRankingForLogin', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function forceScores($scores, $silentMode)
    {
        $this->addCall('ForceScores', $scores, $silentMode);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function forcePlayerTeam($login, $teamNumber)
    {
        $this->addCall('ForcePlayerTeam', $login, $teamNumber);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function forcePlayerTeamId($playerId, $teamNumber)
    {
        $this->addCall('ForcePlayerTeamId', $playerId, $teamNumber);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function forceSpectator($login, $spectatorMode)
    {
        $this->addCall('ForceSpectator', $login, $spectatorMode);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function forceSpectatorId($playerId, $spectatorMode)
    {
        $this->addCall('ForceSpectatorId', $playerId, $spectatorMode);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function forceSpectatorTarget($spectatorLogin, $targetLogin, $cameraType)
    {
        $this->addCall('ForceSpectatorTarget', $spectatorLogin, $targetLogin, $cameraType);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function forceSpectatorTargetId($spectatorLogin, $targetLogin, $cameraType)
    {
        $this->addCall('ForceSpectatorTargetId', $spectatorLogin, $targetLogin, $cameraType);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function spectatorReleasePlayerSlot($login)
    {
        $this->addCall('SpectatorReleasePlayerSlot', $login);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function spectatorReleasePlayerSlotId($playerId)
    {
        $this->addCall('SpectatorReleasePlayerSlotId', $playerId);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function manualFlowControlEnable($enabled)
    {
        $this->addCall('ManualFlowControlEnable', $enabled);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function manualFlowControlProceed()
    {
        $this->addCall('ManualFlowControlProceed');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function manualFlowControlIsEnabled()
    {
        $this->addCall('ManualFlowControlIsEnabled');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function manualFlowControlGetCurTransition()
    {
        $this->addCall('ManualFlowControlGetCurTransition');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function checkEndMatchCondition()
    {
        $this->addCall('CheckEndMatchCondition');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getNetworkStats()
    {
        $this->addCall('GetNetworkStats');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function startServerLan()
    {
        $this->addCall('StartServerLan');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function startServerInternet($credentials)
    {
        $this->addCall('StartServerInternet', $credentials);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getStatus()
    {
        $this->addCall('GetStatus');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function quitGame()
    {
        $this->addCall('QuitGame');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function gameDataDirectory()
    {
        $this->addCall('GameDataDirectory');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getTracksDirectory()
    {
        $this->addCall('GetTracksDirectory');
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function getSkinsDirectory()
    {
        $this->addCall('GetSkinsDirectory');
        return $this;
    }
}

?>
