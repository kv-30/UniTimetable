<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Timetable</title>
    <link rel="stylesheet" href="Lab Fetch.css">
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
    <!-- HTML form for input -->
    <form method="post">
        <label for="labname">LAB NAME</label>
        <input type="text" id="labname" name="labname" required><br><br>
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

    $labName = $_POST['labname'];

    // Retrieve lab timetable from lab_timetable table
    $stmt = $conn->prepare("SELECT * FROM lab_timetable WHERE lab_name = ?");
    $stmt->execute([$labName]);
    $labTimetableRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize an associative array to hold the timetable data
    $timetableData = array();

    // Populate the timetable data
    foreach ($labTimetableRows as $row) {
        $day = $row['day'];
        $timeSlot = $row['time_slot'];
        $batchName = $row['batch_name'];
        if (!isset($timetableData[$timeSlot])) {
            $timetableData[$timeSlot] = array();
        }
        $timetableData[$timeSlot][$day] = $batchName;
    }

    // Display the timetable
    echo "<h2>Timetable for Lab: $labName</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Time Slot</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th></tr>";
    foreach ($timetableData as $timeSlot => $schedule) {
        echo "<tr>";
        echo "<td>$timeSlot</td>";
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
            $batchName = isset($schedule[$day]) ? $schedule[$day] : '-';
            echo "<td>$batchName</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Add a download button
    echo "<form method='post' action='download.php'>";
    echo "<input type='hidden' name='labname' value='$labName'>";
    echo "<input type='submit' value='Download'>";
    echo "</form>";
}
?>

</body>
</html>