<?php
session_start();
// Include the database connection file.
require_once __DIR__ . "/db.php";

// Initialise variables to store validation errors and success messages.
$error = null;
$message = null;

// Process the registration form only when it is submitted using POST.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Retrieve and sanitise the username and email by removing
    // unnecessary whitespace from the beginning and end.
    // Use an empty string if either field is missing.
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");

    // Retrieve the password without trimming it, as spaces may
    // be part of the user's intended password.
    $password = $_POST["password"] ?? "";

    // Validate that all required fields have been completed.
    if ($username === "" || $email === "" || $password === "") {
        $error = "Please fill in every field.";

    // Ensure the username does not exceed 50 characters.
    } elseif (strlen($username) > 50) {
        $error = "Username must be 50 characters or fewer.";

    // Check that the email is no longer than 100 characters
    // and follows a valid email address format.
    } elseif (
        strlen($email) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $error = "Please enter a valid email address.";

    // Require the password to contain at least 12 characters.
    } elseif (strlen($password) < 12) {
        $error = "Password must be at least 12 characters long.";

    } else {
        // Hash the password before storing it in the database.
        // PASSWORD_DEFAULT selects PHP's recommended password-hashing algorithm.
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        try {
            // Start a database transaction so that the user account
            // and profile are either both created or both rolled back.
            $conn->begin_transaction();
            // Prepare a parameterised SQL statement to insert the new user.
            // Prepared statements help protect against SQL injection.
            $stmt = $conn->prepare(
                "INSERT INTO users (username, email, password_hash)
                 VALUES (?, ?, ?)"
            );
            // Bind the username, email, and hashed password as strings.
            $stmt->bind_param(
                "sss",
                $username,
                $email,
                $passwordHash
            );
            // Execute the query to create the user account.
            $stmt->execute();
            // Retrieve the ID generated for the newly created user.
            $newUserId = $conn->insert_id;
            // Close the user insertion statement.
            $stmt->close();
            // Prepare a query to create a corresponding user profile.
            // The new user's ID links the profile to their account.
            $profileStmt = $conn->prepare(
                "INSERT INTO user_profiles (user_id) VALUES (?)"
            );
            // Bind the user ID as an integer.
            $profileStmt->bind_param("i", $newUserId);
            // Insert the user's profile into the database.
            $profileStmt->execute();
            // Close the profile insertion statement.
            $profileStmt->close();
            // Commit the transaction to permanently save both inserts.
            $conn->commit();
            // Set a success message displayed after registration.
            $message = "Account created! You can now sign in.";

        } catch (mysqli_sql_exception $e) {
            // Undo all changes made during the transaction if a
            // database operation fails.
            $conn->rollback();
            // MySQL error code 1062 indicates a duplicate entry,
            // such as an existing username or email address.
            if ((int) $e->getCode() === 1062) {
                $error = "That username or email is already registered.";

            } else {
                // Record the technical error in the server log
                // without exposing database details to the user.
                error_log($e->getMessage());
                // Display a generic error message to the user.
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Define the character encoding for the page. -->
    <meta charset="UTF-8">
    <!-- Make the page responsive on mobile devices. -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Set the title displayed in the browser tab. -->
    <title>Register - Flick Pick</title>
</head>
<body>
    <!-- Main heading for the registration page. -->
    <h1>Create a Flick Pick Account</h1>
    <!-- Display a validation or registration error, if one exists. -->
    <?php if ($error !== null): ?>
        <p role="alert">
            <!-- Escape the message to prevent HTML injection (XSS). -->
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>
    <!-- Display a success message after successful registration. -->
    <?php if ($message !== null): ?>
        <p role="status">
            <!-- Escape the success message before displaying it. -->
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            <!-- Provide a link to the sign-in page. -->
            <a href="signIn.php">Sign in</a>
        </p>
    <?php endif; ?>
    <!-- Registration form submits the entered data to register.php
         using the POST method. -->
    <form action="register.php" method="post">
        <!-- Username input field. -->
        <p>
            <label for="username">Username:</label>
            <input type="text"
                   id="username"
                   name="username"
                   maxlength="50"
                   autocomplete="username"
                   required>
        </p>
        <!-- Email input field with browser-level format validation. -->
        <p>
            <label for="email">Email:</label>
            <input type="email"
                   id="email"
                   name="email"
                   maxlength="100"
                   autocomplete="email"
                   required>
        </p>
        <!-- Password input field. The browser hides the entered characters. -->
        <p>
            <label for="password">Password:</label>
            <input type="password"
                   id="password"
                   name="password"
                   minlength="12"
                   autocomplete="new-password"
                   required>
        </p>
        <!-- Submit the registration form. -->
        <button type="submit">Create Account</button>
    </form>
    <!-- Give existing users a link to the sign-in page. -->
    <p>Already registered? <a href="signIn.php">Sign in</a></p>
</body>
</html>

<?php
// Close the database connection after the page has finished processing.
$conn->close();
?>
