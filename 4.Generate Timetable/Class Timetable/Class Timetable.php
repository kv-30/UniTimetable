<!DOCTYPE html>
<html>
<head>
    <title> Class Timetable</title>
    <link rel="stylesheet" href="Class Timetable.css">
    
</head>
<body>

<header>
    <h1 id="pageTitle">PICT Timetable Creator</h1>
    <div class="logo">
        <img src="#" alt="PICT Logo">
    </div>
    <nav>
        <ul>
            <li><a href="http://127.0.0.1:5500/PBL/1.Homepage/Homepage.html#">Home</a></li>
            <li><a href="http://127.0.0.1:5500/PBL/3.%20Login%20page/login.html">Login</a></li>
            <li><a href="http://127.0.0.1:5500/PBL/2.About%20Page/About.html">About</a></li>
            
        </ul>
    </nav>
</header>

<?php
// Function to check if class timetable already exists
function classTimetableExists($conn, $classroom)
{
    $classroom = strtoupper($classroom);
    $sql = "SELECT * FROM class_timetable WHERE class = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $classroom);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
?>
    <form method="post">
        <label for="classroom">Class Name:</label>
        <input type="text" id="classroom" name="classroom" required><br><br>
        
        <input type="submit" value="Generate Timetable">
    </form>

<?php
} else {
    $servername = "localhost";
    $username = "root";
    $password = "password";
    $dbname = "timetables";

    // Create connection
    $conn = new mysqli($servername, $username, $password,$dbname);

    // Check connection
    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }

    // Process form submission and display timetable
    $classroom = strtoupper($_POST['classroom']);

    // Check if class timetable already exists
    if (classTimetableExists($conn, $classroom)) {
        echo "<p class='error-message'>Timetable for this class already exists.</p>";
    } else {
        // Proceed with generating timetable
        
        // Retrieve data from the database

        // Extract the number from the $classroom variable using regular expression
        preg_match('/\d+$/', $classroom, $matches);
        $classroom_number = isset($matches[0]) ? intval($matches[0]) : null;

        // Prepare SQL query based on classroom number
        $sql = "SELECT * FROM lab_timetable WHERE batch_name LIKE '%" . $classroom_number . "%'";
        $result = $conn->query($sql);

        // Check if any rows are returned
        if ($result->num_rows > 0) {
            // Fetch all rows and store in $data variable
            $data = [];
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            // Now $data contains all rows from lab_timetable where batch_name matches the pattern
            // Do whatever you need with $data
        } else {
            echo "No rows found";
        }
        // Initialize array to store formatted data
        $newData = array();

        // Iterate through the retrieved data
        foreach ($data as $row) {
            $day = $row['day'];
            $timeSlot = $row['time_slot'];
            $batchName = $row['batch_name'];
            $subject = $row['lab_name'];
            
            // Create an entry in the new array if it doesn't exist
            $key = "$day $timeSlot";
            if (!isset($newData[$key])) {
                $newData[$key] = array(
                    'day' => $day,
                    'time_slot' => $timeSlot,
                    'subject' => ''
                );
            }
            
            // Append the subject to the batch entry
            if (!empty($newData[$key]['subject'])) {
                $newData[$key]['subject'] .= ', ';
            }
            $newData[$key]['subject'] .= "$batchName: $subject";
        }

        // Function to check if two time slots overlap
        function slots_overlap($slot1, $slot2) {
            list($start1, $end1) = explode(' - ', $slot1);
            list($start2, $end2) = explode(' - ', $slot2);
            return ($start1 <= $end2 && $end1 >= $start2);
        }

        // Function to create additional rows for overlapping time slots
        function create_additional_rows($day, $time_slot)
        {
            $additional_rows = array();
            
            // Define time slots
            $time_slots = array(
                '08:00 - 09:00',
                '09:00 - 10:00',
                '10:15 - 11:15',
                '11:15 - 12:05',
                '13:00 - 14:00',
                '14:00 - 15:00',
                '15:15 - 16:15',
                '16:15 - 17:15'
            );

            // Find indices of overlapping time slots
            $overlap_indices = array();
            foreach ($time_slots as $index => $slot) {
                if (slots_overlap($time_slot, $slot)) {
                    $overlap_indices[] = $index;
                }
            }

            // Generate additional rows for time slots that don't overlap
            foreach ($time_slots as $index => $slot) {
                if (!in_array($index, $overlap_indices)) {
                    $additional_rows[] = array("day" => $day, "time_slot" => $slot, "subject" => "");
                }
            }

            return $additional_rows;
        }

        // Initialize arrays for subject assignment
        $new_array = array();
        $subjects_assigned = array();

        // Generate additional rows for overlapping time slots and assign subjects
        foreach ($newData as $entry) {
            $new_array[] = $entry;  // Add the original entry
            $new_array = array_merge($new_array, create_additional_rows($entry['day'], $entry['time_slot']));  // Add additional rows if needed

            // Initialize array for subjects assigned for this day
            if (!isset($subjects_assigned[$entry['day']])) {
                $subjects_assigned[$entry['day']] = array();
            }
        }

        // Subjects to be assigned
        $subjects = ["EG", "PPS", "EM-II", "BXE", "CHEM",];

        // Randomly assign subjects while ensuring different subjects for successive time slots on the same day
        foreach ($new_array as &$row) {
            if (empty($row['subject'])) {
                $day = $row['day'];
                // Randomly select a subject from the remaining subjects not assigned for this day
                $remaining_subjects = array_diff($subjects, $subjects_assigned[$day]);
                if (!empty($remaining_subjects)) { // Check if there are remaining subjects to assign
                    $subject = $remaining_subjects[array_rand($remaining_subjects)];
                    $row['subject'] = $subject;
                    $subjects_assigned[$day][] = $subject; // Add the assigned subject to the list for this day
                } else {
                    $row['subject'] = ''; // Set empty subject if no subjects are remaining to assign
                }
            }
        }

        // PRINTING TABLE BEGINS

        // Function to get start time
        function get_start_time($time) {
            $times = array(
                "08:00" => 1,
                "09:00" => 2,
                "10:15" => 3,
                "11:15" => 4,
                "13:00" => 5,
                "14:00" => 6,
                "15:15" => 7,
                "16:15" => 8);
            return $times[$time];
        }

        // Define custom sorting order for days of the week
        $day_order = array(
            "Monday" => 1,
            "Tuesday" => 2,
            "Wednesday" => 3,
            "Thursday" => 4,
            "Friday" => 5,
            "Saturday" => 6,
            "Sunday" => 7
        );


        // Sort the array by day and time slot
        //a and b are rows of new_array
        usort($new_array, function ($a, $b) use ($day_order) {
            $day_compare_a = $day_order[$a['day']];
            $day_compare_b = $day_order[$b['day']];
            if ($day_compare_a === $day_compare_b) {
                // Split time slot string and compare based on start time
                $a_times = explode(' - ', $a['time_slot']);
                $b_times = explode(' - ', $b['time_slot']);
                $a_start = get_start_time($a_times[0]);
                $b_start = get_start_time($b_times[0]);
                return $a_start - $b_start;
            }
            return $day_compare_a - $day_compare_b;
        });

        $final_fe6_tt=array();

        // Loop through the results and organize the data into the timetable array
        foreach ($new_array as $row) {
            $day = $row["day"];
            $time_slot = $row["time_slot"];
            $subject = $row["subject"];
            $final_fe6_tt[$day][$time_slot] = $subject;
        }

        // Function to print timetable
        function printTimetable($array) {
            $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

            // Initialize timetable
            $timetable = [];

            // Fill timetable with empty slots
            foreach ($days as $day) {
                $timetable[$day] = [];
                for ($i = 0; $i < 9; $i++) {
                    $timetable[$day][] = "";
                }
            }

            // Populate timetable with subjects
            foreach ($array as $item) {
                $dayIndex = array_search($item["day"], $days);
                $time_slot = explode(" - ", $item["time_slot"]);
                $start_time = explode(":", $time_slot[0])[0];

                // Handle two-hour slots
                if (strpos($time_slot[1], ":") !== false) {
                    $end_time = explode(":", $time_slot[1])[0];
                    $subject = $item["subject"];
                    // Merge the subject into one cell
                    for ($i = $start_time - 8; $i < $end_time - 8; $i++) {
                        if ($i == $start_time - 8) {
                            $timetable[$item["day"]][$i] = $subject;
                        } else {
                            $timetable[$item["day"]][$i] = "";
                        }
                    }
                } else { // Handle one-hour slots
                    $timetable[$item["day"]][$start_time - 8] = $item["subject"];
                }
            }
            
            // Print timetable
            echo "<table border='1'>";
            echo "<tr><th>Time</th>";
            foreach ($days as $day) {
                echo "<th>$day</th>";
            }
            echo "</tr>";

            for ($i = 8; $i <= 16; $i++) {
                echo "<tr><td>" . sprintf("%02d", $i) . ":00 - " . sprintf("%02d", $i + 1) . ":00</td>";
                foreach ($days as $day) {
                    echo "<td>" . $timetable[$day][$i - 8] . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
                
        }
        // Print timetable
        printTimetable($new_array);

        //insert into main timetable values
        $stmt=$conn->prepare("Insert into class_timetable values(?,?,?,?)");
        $stmt->bind_param('ssss',$class,$day,$time_slot,$subject);

        foreach ($new_array as $row){
            $class=$classroom;
            $day=$row["day"];
            $time_slot=$row["time_slot"];
            $subject=$row["subject"];
            $stmt->execute();
        }

        // Add the new timetable button
        echo '<form action="final_class.php" method="get">';
        echo '<input type="submit" value="Back" class="button">';
        echo '</form>';
        }
}
?>
</body>
</html>