<?php

// Start the session so the signed-in username can be used on later pages.
session_start();

// This is displayed when the submitted form is missing required values.
$error = null;

// After the prototype sign-in succeeds, send the user back to the movie page.
$redirectTarget = 'index.php';

// Only process credentials when the form has been submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and clean the submitted form values.
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // The prototype only checks that both fields contain a value.
    if ($username === '' || $password === '') {
        $error = 'Enter both a username and password.';
    } else {
        // Prototype authentication: replace with database/password verification later.
        // The password is not stored; a real app should verify it securely.
        $_SESSION['username'] = $username;
        header('Location: ' . $redirectTarget);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - FlickPick</title>
</head>
<body>
    <h1>Sign In</h1>

    <!-- Show validation feedback without leaving the sign-in page. -->
    <?php if ($error !== null): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <!-- POST sends the form values back to this file for processing. -->
    <form action="signIn.php" method="post">
        <p>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </p>
        <p>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </p>
        <button type="submit">Sign In</button>
    </form>
</body>
</html>