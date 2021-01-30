<?php
/*
 * TmForever XML-RPC structs for PHP by Voyager006.
 *
 * Reflects version 2.11.26 (build 2011-02-21).
 * https://methods.xaseco.org/methodstmf.php
 * https://server.xaseco.org/callbacks.php
 */
namespace Tmf;


class SChallengeInfo
{
    /** @var string $uid */
    public $uid;

    /** @var string $name */
    public $name;

    /** @var string $fileName */
    public $fileName;

    /** @var string $author */
    public $author;

    /** @var string $environnement */
    public $environnement;

    /** @var string $mood */
    public $mood;

    /** @var int $bronzeTime */
    public $bronzeTime;

    /** @var int $silverTime */
    public $silverTime;

    /** @var int $goldTime */
    public $goldTime;

    /** @var int $authorTime */
    public $authorTime;

    /** @var int $copperPrice */
    public $copperPrice;

    /** @var bool $lapRace */
    public $lapRace;

    /** @var int $nbLaps */
    public $nbLaps;

    /** @var int $nbCheckpoints */
    public $nbCheckpoints;

    public function __construct($array)
    {
        $this->uid = $array['Uid'];
        $this->name = $array['Name'];
        $this->fileName = $array['FileName'];
        $this->author = $array['Author'];
        $this->environnement = $array['Environnement'];
        $this->mood = $array['Mood'];
        $this->bronzeTime = $array['BronzeTime'];
        $this->silverTime = $array['SilverTime'];
        $this->goldTime = $array['GoldTime'];
        $this->authorTime = $array['AuthorTime'];
        $this->copperPrice = $array['CopperPrice'];
        $this->lapRace = $array['LapRace'];
        $this->nbLaps = $array['NbLaps'];
        $this->nbCheckpoints = $array['NbCheckpoints'];
    }
}


class SChallengeListInfo
{
    /** @var string $uid */
    public $uid;

    /** @var string $name */
    public $name;

    /** @var string $fileName */
    public $fileName;

    /** @var string $author */
    public $author;

    /** @var string $environnement */
    public $environnement;

    /** @var int $goldTime */
    public $goldTime;

    /** @var int $copperPrice */
    public $copperPrice;

    public function __construct($array)
    {
        $this->uid = $array['Uid'];
        $this->name = $array['Name'];
        $this->fileName = $array['FileName'];
        $this->author = $array['Author'];
        $this->environnement = $array['Environnement'];
        $this->goldTime = $array['GoldTime'];
        $this->copperPrice = $array['CopperPrice'];
    }
}


class SPlayerRanking
{
    /** @var string $login */
    public $login;

    /** @var string $nickName */
    public $nickName;

    /** @var int $playerId */
    public $playerId;

    /** @var int $rank */
    public $rank;

    /** @var int $bestTime */
    public $bestTime;

    /** @var array $bestCheckpoints */
    public $bestCheckpoints;

    /** @var int $score */
    public $score;

    /** @var int $nbrLapsFinished */
    public $nbrLapsFinished;

    /** @var int $ladderScore */
    public $ladderScore;

    public function __construct($array)
    {
        $this->login = $array['Login'];
        $this->nickName = $array['NickName'];
        $this->playerId = $array['PlayerId'];
        $this->rank = $array['Rank'];
        $this->bestCheckpoints = $array['BestCheckpoints'];
        $this->score = $array['Score'];
        $this->nbrLapsFinished = $array['NbrLapsFinished'];
        $this->ladderScore = $array['LadderScore'];
    }
}


class SPlayerInfo
{
    /** @var string $login */
    public $login;

    /** @var string $nickName */
    public $nickName;

    /** @var int $playerId */
    public $playerId;

    /** @var int $teamId */
    public $teamId;

    /** @var int $spectatorStatus */
    public $spectatorStatus;

    /** @var int $ladderRanking */
    public $ladderRanking;

    /** @var int $flags */
    public $flags;

    public function __construct($array)
    {
        $this->login = $array['Login'];
        $this->nickName = $array['NickName'];
        $this->playerId = $array['PlayerId'];
        $this->teamId = $array['TeamId'];
        $this->spectatorStatus = $array['SpectatorStatus'];
        $this->ladderRanking = $array['LadderRanking'];
        $this->flags = $array['Flags'];
    }
}


class Skins
{
    /** @var string $environnement */
    public $environnement;

    /** @var PackDesc $packDesc */
    public $packDesc;

    public function __construct($array)
    {
        $this->environnement = $array['Environnement'];
        $this->packDesc = new PackDesc($array['PackDesc']);
    }
}


abstract class GbxFile
{
    /** @var string $fileName */
    public $fileName;

    /** @var string $checksum */
    public $checksum;
}


class PackDesc extends GbxFile
{
    public function __construct($array)
    {
        $this->fileName = $array['FileName'];
        $this->checksum = $array['Checksum'];
    }
}


class Avatar extends GbxFile
{
    public function __construct($array)
    {
        $this->fileName = $array['FileName'];
        $this->checksum = $array['Checksum'];
    }
}


class SDetailedPlayerInfo
{
    /** @var string $login */
    public $login;

    /** @var string $nickName */
    public $nickName;

    /** @var int $playerId */
    public $playerId;

    /** @var int $teamId */
    public $teamId;

    /** @var string $ipAddress */
    public $ipAddress;

    /** @var int $downloadRate */
    public $downloadRate;

    /** @var int $uploadRate */
    public $uploadRate;

    /** @var string $language */
    public $language;

    /** @var bool $isSpectator */
    public $isSpectator;

    /** @var bool $isInOfficialMode */
    public $isInOfficialMode;

    /** @var Avatar $avatar */
    public $avatar;

    /** @var Skins[] $skins */
    public $skins;

    /** @var array $ladderStats */
    public $ladderStats;

    /** @var int $hoursSinceZoneInscription */
    public $hoursSinceZoneInscription;

    /** @var int $onlineRights */
    public $onlineRights;

    public function __construct($array)
    {
        $this->login = $array['Login'];
        $this->nickName = $array['NickName'];
        $this->playerId = $array['PlayerId'];
        $this->teamId = $array['TeamId'];
        $this->ipAddress = $array['IpAddress'];
        $this->downloadRate = $array['DownloadRate'];
        $this->uploadRate = $array['UploadRate'];
        $this->language = $array['Language'];
        $this->isSpectator = $array['IsSpectator'];
        $this->isInOfficialMode = $array['IsInOfficialMode'];
        $this->avatar = new Avatar($array['Avatar']);
        $this->skins = new Skins($array['Skins']);
        $this->ladderStats = $array['LadderStats'];
        $this->hoursSinceZoneInscription = $array['HoursSinceZoneInscription'];
        $this->onlineRights = $array['OnlineRights'];
    }
}


class SServerOptions
{
    /** @var string $name */
    public $name;

    /** @var string $comment */
    public $comment;

    /** @var string $password */
    public $password;

    /** @var string $passwordForSpectator */
    public $passwordForSpectator;

    /** @var int $nextMaxPlayers */
    public $nextMaxPlayers;

    /** @var int $nextMaxSpectators */
    public $nextMaxSpectators;

    /** @var bool $isP2PUpload */
    public $isP2PUpload;

    /** @var bool $isP2PDownload */
    public $isP2PDownload;

    /** @var int $nextLadderMode */
    public $nextLadderMode;

    /** @var int $nextVehicleNetQuality */
    public $nextVehicleNetQuality;

    /** @var int $nextCallVoteTimeOut */
    public $nextCallVoteTimeOut;

    /** @var float $callVoteRatio */
    public $callVoteRatio;

    /** @var bool $allowChallengeDownload */
    public $allowChallengeDownload;

    /** @var int $autoSaveReplays */
    public $autoSaveReplays;

    /** @var string $refereePassword */
    public $refereePassword;

    /** @var int $refereeMode */
    public $refereeMode;

    /** @var bool $autoSaveValidationReplays */
    public $autoSaveValidationReplays;

    /** @var int $hideServer */
    public $hideServer;

    /** @var bool $useChangingValidationSeed */
    public $useChangingValidationSeed;

    public function __construct($array)
    {
        $this->name = $array['Name'];
        $this->comment = $array['Comment'];
        $this->password = $array['Password'];
        $this->passwordForSpectator = $array['PasswordForSpectator'];
        $this->currentMaxPlayers = $array['CurrentMaxPlayers'];
        $this->nextMaxPlayers = $array['NextMaxPlayers'];
        $this->currentMaxSpectators = $array['CurrentMaxSpectators'];
        $this->nextMaxSpectators = $array['NextMaxSpectators'];
        $this->isP2PUpload = $array['IsP2PUpload'];
        $this->isP2PDownload = $array['IsP2PDownload'];
        $this->nextLadderMode = $array['NextLadderMode'];
        $this->nextVehicleNetQuality = $array['NextVehicleNetQuality'];
        $this->nextCallVoteTimeOut = $array['NextCallVoteTimeOut'];
        $this->callVoteRatio = $array['CallVoteRatio'];
        $this->allowChallengeDownload = $array['AllowChallengeDownload'];
        $this->autoSaveReplays = $array['AutoSaveReplays'];
        if (isset($array['RefereePassword'])) $this->refereePassword = $array['RefereePassword'];
        if (isset($array['RefereeMode'])) $this->refereeMode = $array['RefereeMode'];
        if (isset($array['AutoSaveValidationReplays'])) $this->autoSaveValidationReplays = $array['AutoSaveValidationReplays'];
        if (isset($array['HideServer'])) $this->hideServer = $array['HideServer'];
        if (isset($array['UseChangingValidationSeed'])) $this->useChangingValidationSeed = $array['UseChangingValidationSeed'];
    }
}


class SServerOptionsInfo extends SServerOptions
{
    /** @var int $currentMaxPlayers */
    public $currentMaxPlayers;

    /** @var int $currentMaxSpectators */
    public $currentMaxSpectators;

    /** @var int $currentLadderMode */
    public $currentLadderMode;

    /** @var int $currentVehicleNetQuality */
    public $currentVehicleNetQuality;

    /** @var int $currentCallVoteTimeOut */
    public $currentCallVoteTimeOut;

    public function __construct($array)
    {
        parent::__construct($array);
        $this->currentMaxPlayers = $array['CurrentMaxPlayers'];
        $this->currentMaxSpectators = $array['CurrentMaxSpectators'];
        $this->currentLadderMode = $array['CurrentLadderMode'];
        $this->currentVehicleNetQuality = $array['CurrentVehicleNetQuality'];
        $this->currentCallVoteTimeOut = $array['CurrentCallVoteTimeOut'];
    }
}


class SGameInfo
{

}


class SPlayerNetInfos
{

}


class SNetworkStats
{

}


abstract class CameraType
{
    const Unchanged = -1;
    const Replay = 0;
    const Follow = 1;
    const Free = 2;
}


abstract class GameMode
{
    const Rounds = 0;
    const TimeAttack = 1;
    const Team = 2;
    const Laps = 3;
    const Stunts = 4;
    const Cup = 5;
}


abstract class HideServer
{
    const Visible = 0;
    const AlwaysHidden = 1;
    const HiddenFromNations = 2;
}


abstract class LadderMode
{
    const Disabled = 0;
    const Forced = 1;
}


abstract class RefereeMode
{
    const ValidateTop3 = 0;
    const ValidateAll = 1;
}


abstract class ServerStatus
{
    const Waiting = 1;
    const Launching = 2;
    const Synchronization = 3;
    const Play = 4;
    const Finish = 5;
}


abstract class SpectatorMode
{
    const UserSelectable = 0;
    const Spectator = 1;
    const Player = 2;
}


abstract class StructVersion
{
    const United = 0;
    const Forever = 1;
    const ForeverIncludingServers = 2;
}


abstract class OnlineRights
{
    const NationsAccount = 0;
    const UnitedAccount = 3;
}


abstract class VehicleNetQuality
{
    const Fast = 0;
    const High = 1;
}

?>
