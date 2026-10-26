<?php
// messages are stored in $successMessage and $errorMessage to stop break in styling

$errorMessage = null;
$successMessage = null;

session_start();

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flick Pick - Register</title>
    <link rel="stylesheet" href="frontend/styles.css"> 
    <link rel="stylesheet" href="frontend/signIn.css">
</head>

    <body>
        <?php include __DIR__ . '/partials/nav.php'; ?>

        <main class="auth-page">
            <section class="auth-card">
                <h1>Create a Flick Pick Account</h1>

                <?php if ($successMessage !== null): ?>
                    <p class="message message-success" role="status">
                        <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <a class="btn-primary" href="signIn.php">Go to Sign In</a>
                <?php else: ?>
                    <p class="auth-subtitle">Pick movies faster and save your favorites.</p>
                    <?php if ($errorMessage !== null): ?>
                        <p class="message message-error" role="alert">
                            <?=htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    <?php endif; ?>


                    <form action="register.php" method="POST" class="auth-form">
                        <div class="field">
                            <label for="username">Username:</label>
                            <!-- maxlength -->
                            <input type="text" id="username" name="username" maxlength="50"
                                autocomplete="username" required autofocus
                                value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" maxlength="100" autocomplete="email" required
                                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" autocomplete="new-password" required>
                        </div>
            
                        <button type="submit" class="btn-primary">Create Account</button>
                    </form>

                    <p class="auth-switch">Already have an account? <a href="signIn.php">Sign In</a></p>
                <?php endif; ?>
            </section>
        </main>
    </body>
</html>

