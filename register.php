<?php
session_start();

// Use the shared database connection instead of duplicating credentials here.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/db.php';

// Names match the styled messages used in the HTML below.
$errorMessage = null;
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $errorMessage = 'Please fill in every field.';
    } elseif (strlen($username) > 50) {
        $errorMessage = 'Username must be 50 characters or fewer.';
    } elseif (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif (strlen($password) < 12) {
        $errorMessage = 'Password must be at least 12 characters long.';
    } else {
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
           
            $conn->begin_transaction();

            $stmt = $conn->prepare(
                'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)'
            );
            $stmt->bind_param('sss', $username, $email, $passwordHash);
            $stmt->execute();
            $newUserId = $conn->insert_id;
            $stmt->close();

            $profileStmt = $conn->prepare(
                'INSERT INTO user_profiles (user_id) VALUES (?)'
            );
            $profileStmt->bind_param('i', $newUserId);
            $profileStmt->execute();
            $profileStmt->close();

            $conn->commit();
            $successMessage = 'Account created! You can now sign in.';
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();

            if ((int) $e->getCode() === 1062) {
                $errorMessage = 'That username or email is already registered.';
            } else {
                error_log($e->getMessage());
                $errorMessage = 'Registration failed. Please try again.';
            }
        }
    }
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
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <form action="register.php" method="POST" class="auth-form">
                    <div class="field">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" maxlength="50"

                        autocomplete="username" required autofocus
                               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="field">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" maxlength="100"
                               autocomplete="email" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="field">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password"
                               minlength="12" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn-primary">Create Account</button>
                </form>

                <p class="auth-switch">Already have an account? <a href="signIn.php">Sign In</a></p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
<?php
$conn->close();
