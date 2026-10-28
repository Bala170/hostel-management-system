<?php

require './vendor/autoload.php';

use Biosync\Biosync;

class Attendance {

    protected $ip = '10.0.251.243';
    protected $device;

    public function connect()
    {
        $this->device = new Biosync($this->ip, 4370);
        $this->device->connect();
        $this->device->disableDevice();
    }

    public function disconnect()
    {
        $this->device->enableDevice();
        $this->device->disconnect();
    }

    public function getAttendance()
    {
        $this->connect();
        $data = $this->device->getAttendance();
        $this->disconnect();
        return $data;
    }
}

// 🔹 Create object
$attendanceObj = new Attendance();
$records = $attendanceObj->getAttendance();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Data</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            padding: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #007bff;
            color: white;
        }
        tr:hover {
            background: #f1f1f1;
        }
    </style>
</head>
<body>

<h2>📊 Attendance Records</h2>

<table>
    <tr>
        <th>#</th>
        <th>User ID</th>
        <th>State</th>
        <th>Date & Time</th>
    </tr>
<?php
echo "<pre>";
print_r($records);
echo "</pre>";
?>

</table>

</body>
</html>