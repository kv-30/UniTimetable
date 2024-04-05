<?php
// Database connection parameters
$dsn = 'mysql:host=localhost;dbname=timetables';
$username = 'root';
$password = 'Spartabuddha_987';

// Attempt to connect to the database
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to validate user credentials
function validate_user($username, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    } else {
        return false;
    }
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate user credentials
    $user = validate_user($username, $password, $pdo);

    if ($user) {
        // Start session and set session variables
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Redirect to a logged-in page
        header('Location: http://localhost:3000/UniTimetable/4.Generate%20Timetable/Generate%20Timetable.html');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
    <title>Login - College Timetable Creator</title>
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

    <section class="hero">
        <div class="hero-content">
            <form id="loginForm" method="post">
                <div class="input-container">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-container">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <?php if (isset($error)) echo "<p>$error</p>"; ?>
        </div>
    </section>

    <footer>
        <p>&copy; 2024 College Timetable Creator. All rights reserved.</p>
    </footer>
</body>
</html>
