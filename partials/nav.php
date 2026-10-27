<?php
// navigation bar shared by each page

// page must call session_start() before any HTML is sent, then include this file to where nave bar should appear
//   include __DIR__ . '/partials/nav.php';  (pgs in project folder)
//  include dirname(__DIR__) . '/partials/nav.php';  (pgs in frontend folder)

//URL of project folder inside
$base = '/FlickPick'; // adjust this to the URL of what your folder is named

//user signed in when sign in page stored username in session
$isLoggedIn = isset($_SESSION['username']);
?>
<header class = "navbar">
    <nav class="nav-left">
        <?php if ($isLoggedIn): ?>
            <a href="<?= $base ?>/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?= $base ?>/signIn.php">Login</a>
        <?php endif; ?>
        <a href="#">Random</a>
        <a href="#">Watch Later</a>
        <a href="#"> Profile</a>
    </nav>
    <div class="nav-right">
        <?php if ($isLoggedIn): ?>
            <!-- what if user has <script> in username? -->
            <span class="nav-user">Hi, <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <a class="logo" href="<?= $base ?>/frontend/index.php">FlickPick</a>
    </div>
</header>