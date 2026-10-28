<?php
namespace Biosync;

class Biosync
{
    public $ip;
    public $port;
    public $zkclient;

    public $data_recv = '';
    public $session_id = 0;
    public $userdata = array();
    public $attendancedata = array();

    public function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;

        $this->zkclient = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        $timeout = array('sec' => 60, 'usec' => 500000);
        socket_set_option($this->zkclient, SOL_SOCKET, SO_RCVTIMEO, $timeout);

        include_once("zkconst.php");
        include_once("zkconnect.php");
        include_once("zkversion.php");
        include_once("zkos.php");
        include_once("zkplatform.php");
        include_once("zkworkcode.php");
        include_once("zkssr.php");
        include_once("zkpin.php");
        include_once("zkface.php");
        include_once("zkserialnumber.php");
        include_once("zkdevice.php");
        include_once("zkuser.php");
        include_once("zkattendance.php");
        include_once("zktime.php");
    }

    function createChkSum($p)
    {
        $l = count($p);
        $chksum = 0;
        $i = $l;
        $j = 1;

        while ($i > 1) {
            $u = unpack('S', pack('C2', $p['c' . $j], $p['c' . ($j + 1)]));
            $chksum += $u[1];

            if ($chksum > USHRT_MAX)
                $chksum -= USHRT_MAX;

            $i -= 2;
            $j += 2;
        }

        if ($i)
            $chksum += $p['c' . strval(count($p))];

        while ($chksum > USHRT_MAX)
            $chksum -= USHRT_MAX;

        if ($chksum > 0)
            $chksum = -($chksum);
        else
            $chksum = abs($chksum);

        $chksum -= 1;

        while ($chksum < 0)
            $chksum += USHRT_MAX;

        return pack('S', $chksum);
    }

    function createHeader($command, $chksum, $session_id, $reply_id, $command_string)
    {
        $buf = pack('SSSS', $command, $chksum, $session_id, $reply_id) . $command_string;

        $buf = unpack('C' . (8 + strlen($command_string)) . 'c', $buf);

        $u = unpack('S', $this->createChkSum($buf));

        // ✅ FIXED (PHP 8 compatible)
        if (is_array($u)) {
            $u = reset($u);
        }

        $chksum = $u;

        $reply_id += 1;

        if ($reply_id >= USHRT_MAX)
            $reply_id -= USHRT_MAX;

        $buf = pack('SSSS', $command, $chksum, $session_id, $reply_id);

        return $buf . $command_string;
    }

    function checkValid($reply)
    {
        $u = unpack('H2h1/H2h2', substr($reply, 0, 8));

        $command = hexdec($u['h2'] . $u['h1']);

        return ($command == CMD_ACK_OK);
    }

    public function connect()
    {
        return zkconnect($this);
    }

    public function disconnect()
    {
        return zkdisconnect($this);
    }

    public function version()
    {
        return zkversion($this);
    }

    public function osversion()
    {
        return zkos($this);
    }

    public function platform()
    {
        return zkplatform($this);
    }

    public function fmVersion()
    {
        return zkplatformVersion($this);
    }

    public function workCode()
    {
        return zkworkcode($this);
    }

    public function ssr()
    {
        return zkssr($this);
    }

    public function pinWidth()
    {
        return zkpinwidth($this);
    }

    public function faceFunctionOn()
    {
        return zkfaceon($this);
    }

    public function serialNumber()
    {
        return zkserialnumber($this);
    }

    public function deviceName()
    {
        return zkdevicename($this);
    }

    public function disableDevice()
    {
        return zkdisabledevice($this);
    }

    public function enableDevice()
    {
        return zkenabledevice($this);
    }

    public function getUser()
    {
        return zkgetuser($this);
    }

    public function getFP()
    {
        return zkgetfp($this);
    }

    public function getAttendance()
    {
        return zkgetattendance($this);
    }

    public function setTime($t)
    {
        return zksettime($this, $t);
    }

    public function getTime()
    {
        return zkgettime($this);
    }
}