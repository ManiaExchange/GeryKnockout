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
     * @return array|false
     */
    public function listMethods();

    /**
     * Given the name of a method, return an array of legal signatures. Each signature is an array
     * of strings. The first item of each signature is the return type, and any others items are
     * parameter types.
     *
     * @param string $methodName
     *
     * @return array|false
     */
    public function methodSignature($methodName);

    /**
     * Given the name of a method, return a help string.
     *
     * @param string $methodName
     *
     * @return string|false
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
     * @param bool $enable
     *
     * @return bool
     */
    public function enableCallbacks($enable);

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
     * @param int $timeout
     *
     * @return bool
     */
    public function setCallVoteTimeOut($timeout);

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
     * @param float $ratio
     *
     * @return bool
     */
    public function setCallVoteRatio($ratio);

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
     * @param array $ratios
     *
     * @return bool
     */
    public function setCallVoteRatios($ratios);

    /**
     * Get the current ratios for passing votes.
     *
     * @return array
     */
    public function getCallVoteRatios($ratios);

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
     * @param bool $enable
     * @param bool $forwardMessages
     *
     * @return bool
     */
    public function chatEnableManualRouting($enable, $forwardMessages = false);

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
     * @param base64 $data
     *
     * @return bool
     */
    public function writeFile($fileName, $data);

    /**
     * Send the data to the specified player. Only available to Admin.
     *
     * @param int $player
     * @param base64 $data
     *
     * @return bool
     */
    public function tunnelSendDataToId($fileName, $data);

    /**
     * Send the data to the specified player. Login can be a single login or a list of
     * comma-separated logins. Only available to Admin.
     *
     * @param string $login
     * @param base64 $data
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
    public function ignoreId($login);

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
    public function unIgnoreId($login);

    /**
     * Clean the ignore list of the server. Only available to Admin.
     *
     * @return bool
     */
    public function cleanIgnoreList($login);

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
     * @param int $download
     * @param int $upload
     *
     * @return bool
     */
    public function setConnectionRates($download, $upload);

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
     * @param string $comment
     *
     * @return bool
     */
    public function setServerComment($comment);

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
     * @param int $visibility
     *
     * @return bool
     */
    public function setHideServer($visibility);

    /**
     * Get whether the server wants to be hidden from the public server list.
     *
     * @return int
     */
    public function getHideServer();
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
                    print("Multicall method {$methodName} failed with code {$this->client->getErrorCode()}: {$this->client->getErrorMessage()}");
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
 * The static functions in this class perform queries immediately as opposed to GbxClientMulticall.
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
     * @return array|false
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
}


/**
 * XML-RPC methods for performing multicall queries against the dedicated server. This constructor
 * does not establish a connection to the server.
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
     * @return array|false
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
        $this->addCall('system.listMethods', null);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function methodSignature($methodName)
    {
        $this->addCall('system.methodSignature', null);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function methodHelp($methodName)
    {
        $this->addCall('system.methodHelp', null);
        return $this;
    }

    /**
     * @return $this This object, for chaining.
     */
    public function authenticate($login, $password)
    {
        $this->addCall('Authenticate', null);
        return $this;
    }
}

?>
