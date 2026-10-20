<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$dbname = "Flick_Pick";
$username = "root";
$password = "root";
$port = 8889;

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usernameInput = $_POST["username"];
    $emailInput = $_POST["email"];
    $passwordInput = $_POST["password"];

    // Hash the user's password before storing it
    $passwordHash = password_hash($passwordInput, PASSWORD_DEFAULT);

    // Create the user account
    $sql = "INSERT INTO users (username, email, password_hash)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sss",
        $usernameInput,
        $emailInput,
        $passwordHash
    );

    if ($stmt->execute()) {

        // Get the ID MySQL created for the new user
        $newUserId = $conn->insert_id;

        // Create a profile for the new user
        $profileSql = "INSERT INTO user_profiles (user_id)
                       VALUES (?)";

        $profileStmt = $conn->prepare($profileSql);

        $profileStmt->bind_param("i", $newUserId);

        if ($profileStmt->execute()) {

            echo "Account created successfully!<br>";
            echo "Profile created successfully!";

        } else {

            echo "Account created, but profile creation failed: "
                 . $profileStmt->error;

        }

        $profileStmt->close();

    } else {

        echo "Error creating account: " . $stmt->error;

    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Flick Pick - Register</title>
</head>

<body>

<h1>Create a Flick Pick Account</h1>

<form method="POST" action="register.php">

    <label>Username:</label>
    <input type="text" name="username" required>

    <br><br>

    <label>Email:</label>
    <input type="email" name="email" required>

    <br><br>

    <label>Password:</label>
    <input type="password" name="password" required>

    <br><br>

    <button type="submit">Create Account</button>

</form>

</body>
</html>

