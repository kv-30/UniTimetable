<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Timetable</title>
    <link rel="stylesheet" href="Teacher Timetable.css">
</head>
<body>

<header>
    <h1 id="pageTitle">PICT Timetable Creator</h1>

    <nav>
       <ul>
         <li><a href="http://127.0.0.1:5500/UniTimetable/1.Homepage/Homepage.html">Home</a></li>
         <li><a href="http://localhost:3000/UniTimetable/3.Login%20Page/Login.php">Login</a></li>
         <li><a href="http://localhost:3000/UniTimetable/2.About%20Page/About.html">About</a></li>
        </ul>
    </nav>
</header>

<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "Spartabuddha_987";
$dbname = "timetables";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
?>
    <form method="post">
        <label for="teacher">TEACHER'S NAME INITIALS</label>
        <input type="text" id="teacher" name="teacher" required><br><br>
        <input type="submit" value="Generate Timetable">
    </form>
<?php
} else {
    // Create connection
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit();
    }

    $teacherName = $_POST['teacher'];

    // Retrieve teacher's timetable from timetable table
    $stmt1 = $conn->prepare("SELECT * FROM lab_timetable WHERE teacher = ?");
    $stmt1->execute([$teacherName]);
    $timetableRows = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // Retrieve teacher's class timetable from class_timetable table
    $stmt2 = $conn->prepare("SELECT * FROM class_timetable WHERE teacher = ?");
    $stmt2->execute([$teacherName]);
    $classTimetableRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Merge timetable entries and class timetable entries
    $teacherSchedule = array_merge($timetableRows, $classTimetableRows);

    // Initialize an associative array to hold the timetable data
    $timetableData = array();

    // Populate the timetable data
    foreach ($teacherSchedule as $row) {
        $day = $row['day'];
        $timeSlot = $row['time_slot'];
        $classLab = $row['class_lab'];
        if (!isset($timetableData[$timeSlot])) {
            $timetableData[$timeSlot] = array();
        }
        $timetableData[$timeSlot][$day] = $classLab;
    }

    // Display the timetable
    echo "<h2>Timetable for $teacherName</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Time Slot</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th></tr>";
    foreach ($timetableData as $timeSlot => $schedule) {
        echo "<tr>";
        echo "<td>$timeSlot</td>";
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
            $classLab = isset($schedule[$day]) ? $schedule[$day] : '-';
            echo "<td>$classLab</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // Add a download button
    echo "<form method='post' action='download.php'>";
    echo "<input type='hidden' name='teacher' value='$teacherName'>";
    echo "<input type='submit' value='Download'>";
    echo "</form>";
    
}
?>

</body>
</html