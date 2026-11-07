
<?php

session_start();

require_once __DIR__ . '/db.php';

$error = null;
$redirectTarget = 'frontend/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = 'Enter both a username and password.';

    } else {

        // Find the user in the database
        $stmt = $conn->prepare(
            'SELECT user_id, password_hash
             FROM users
             WHERE username = ?'
        );

        $stmt->bind_param('s', $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        // Verify the submitted password
        if ($user && password_verify($password, $user['password_hash'])) {

            session_regenerate_id(true);

            // Store the actual database user ID in the session
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['username'] = $username;

            header('Location: ' . $redirectTarget);
            exit;

        } else {

            $error = 'Incorrect username or password.';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - FlickPick</title>
<!--added stylesheets for general and sign-in page -->
    <link rel="stylesheet" href="frontend/styles.css"> 
    <link rel="stylesheet" href="frontend/signIn.css">
</head>

<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>

    <main class="auth-page">
        <section class="auth-card">
            <h1>Welcome</h1>
            <p class="auth-subtitle"> Sign in to save your favorite movies.</p>

            <!-- Show validation feedback without leaving the sign-in page. -->
            <?php if (!empty($error)): ?>
                <!--role announces to screen readers, too complex?-->
                <p class="message message-error" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <!-- POST sends the form values back to this file for processing. -->
            <form action="signIn.php" method="post" class="auth-form">
                <div class="field">
                    <label for="username">Username</label>
                <!-- value= keeps user input a/f failed try, so no retype -->
                    <input type="text" id="username" name="username" autocomplete="username" required autofocus
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <!--autocomplete for pwrd mannaagers. Too complex?-->
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-primary">Sign In</button>
            </form>

            <p class="auth-switch"> New here? <a href="register.php">Create an account</a></p>
        </section>
    </main>
</body>
</html>
