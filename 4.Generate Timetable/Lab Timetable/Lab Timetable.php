<!DOCTYPE html>
<html>
<head>
    <title>Generate Lab Timetable</title>
    <link rel="stylesheet" href="Lab Timetable.css">
    <style>
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
        }
        .error-message {
            color: red;
        }
    </style>
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

// Define days of the week
$daysOfWeek = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "timetables";

// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
?>
    <form method="post">
        <label for="classroom">Lab Name:</label>
        <input type="text" id="classroom" name="classroom" required><br><br>

        <label for="num_batches">Number of Batches:</label>
        <input type="number" id="num_batches" name="num_batches" required min="0"><br><br>

        <label for="batch_names">Batch Names (comma-separated):</label>
        <input type="text" id="batch_names" name="batch_names" required><br><br>

        <?php
        if (isset($_GET['error']) && $_GET['error'] == 'num_batches_mismatch') {
            echo '<p class="error-message">Number of batches entered does not match the number of batch names</p>';
        } elseif (isset($_GET['error']) && $_GET['error'] == 'exceeds_limit') {
            echo '<p class="error-message">Maximum 24 batches allowed per lab</p>';
        } elseif (isset($_GET['error']) && $_GET['error'] == 'timetable_exists') {
            echo '<p class="error-message">Timetable for this lab already exists</p>';
        }
        ?>

        <input type="submit" value="Generate Timetable">
    </form>

<?php
} else {
    // Database connection settings
    $servername = "localhost";
    $username = "root";
    $password = "Spartabuddha_987";
    $dbname = "timetables";

    // Create connection
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit();
    }
    
    // Check if timetable with the same lab name already exists
    $classroom = $_POST['classroom'];
    $stmt_check = $conn->prepare("SELECT * FROM lab_timetable WHERE lab_name = ?");
    $stmt_check->execute([$classroom]);
    $existing_timetable = $stmt_check->fetch();

    if ($existing_timetable) {
        header("Location: Lab Timetable Generator.php?error=timetable_exists");
        exit();
    }

    // Process form submission and display timetable
    $numBatches = $_POST['num_batches'];
    $classroom = $_POST['classroom'];

    // Fetch batch names and divisions from the database
    $stmt_batches = $conn->prepare("SELECT batch_name, division FROM classbatch");
    $stmt_batches->execute();
    $batches = $stmt_batches->fetchAll(PDO::FETCH_ASSOC);

    // Group batches by division
    $batchesByDivision = [];
    foreach ($batches as $batch) {
        $batchesByDivision[$batch['division']][] = $batch['batch_name'];
    }

    // Shuffle the batches within each division
    foreach ($batchesByDivision as &$divisionBatches) {
        shuffle($divisionBatches);
    }

    // Check if this is the third timetable generation
    $stmt_check_count = $conn->prepare("SELECT COUNT(*) FROM lab_timetable WHERE lab_name = ?");
    $stmt_check_count->execute([$classroom]);
    $timetableCount = $stmt_check_count->fetchColumn();

    if ($timetableCount % 3 == 0 && $timetableCount > 0) {
        // Rotate the days of the week array
        $lastDay = array_pop($daysOfWeek);
        array_unshift($daysOfWeek, $lastDay);
    }

    // Initialize timetable
    $timetable = array();

    // Define time slots
    $timeSlots = array(
        array("start" => "08:00", "end" => "10:00"),
        array("start" => "10:15", "end" => "12:05"),
        array("start" => "13:00", "end" => "15:00"),
        array("start" => "15:15", "end" => "17:15")
    );

    // Distribute batches diagonally
    $batchesAssigned = 0;
    foreach ($timeSlots as $slot) {
        if ($batchesAssigned >= $numBatches) {
            break; // No more batches to assign
        }

        foreach ($daysOfWeek as $day) {
            if ($batchesAssigned >= $numBatches) {
                break 2; // No more batches to assign
            }

            foreach ($batchesByDivision as $division => &$divisionBatches) {
                if (!empty($divisionBatches)) {
                    $batch = array_shift($divisionBatches);

                    // Check if batch is already assigned this time slot in any previous timetable
                    $isAlreadyAssigned = isBatchAlreadyAssigned($conn, $day, $slot['start'], $slot['end'], $batch, $classroom);

                    // If batch is not assigned this time slot previously, assign it
                    if (!$isAlreadyAssigned) {
                        $timetable[$day][$slot['start'] . ' - ' . $slot['end']][] = $batch;
                        $batchesAssigned++;
                        break; // Move to the next day
                    }
                }
            }
        }
    }

    // Display timetable
    echo "<h2>Weekly Timetable for $classroom</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Time Slot</th>";
    // Display days of the week as table headers
    foreach ($daysOfWeek as $day) {
        echo "<th>$day</th>";
    }
    echo "</tr>";
    // Display timetable
    foreach ($timeSlots as $slot) {
        echo "<tr><td>{$slot['start']} - {$slot['end']}</td>";
        foreach ($daysOfWeek as $day) {
            echo "<td>";
            if (isset($timetable[$day][$slot['start'] . ' - ' . $slot['end']])) {
                foreach ($timetable[$day][$slot['start'] . ' - ' . $slot['end']] as $batch) {
                    echo "$batch <br>";
                    // Insert into database
                    $stmt = $conn->prepare("INSERT INTO lab_timetable (lab_name, day, time_slot, batch_name) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$classroom, $day, $slot['start'] . ' - ' . $slot['end'], $batch]);
                }
            }
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // Add the new timetable button
    echo '<form action="Lab Timetable Generator.php" method="get">';
    echo '<input type="submit" value="New Timetable" class="button">';
    echo '</form>';
}

function isBatchAlreadyAssigned($conn, $day, $start, $end, $batch, $classroom)
{
    // Query existing timetables to check if the batch is already assigned the given timeslot
    $stmt = $conn->prepare("SELECT COUNT(*) FROM lab_timetable WHERE lab_name = ? AND day = ? AND time_slot = ? AND batch_name LIKE ?");
    $stmt->execute([$classroom, $day, "$start - $end", "%$batch%"]);
    $count = $stmt->fetchColumn();

    return $count > 0;
}

?>

</body>
</html>
