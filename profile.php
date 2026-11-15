<?php
session_start();
require 'db.php';

// stop the browser from showing a cached copy after logout
header('Cache-Control: no-store, no-cache, must-revalidate');

// not logged in? send them to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit;
}

// get this user's info
$stmt = $pdo->prepare('SELECT first_name, last_name, username, email, address, created_at, profile_photo FROM users WHERE user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// get this user's photos (newest first)
$stmt = $pdo->prepare('SELECT * FROM photos WHERE user_id = ? ORDER BY uploaded_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalLikes = array_sum(array_column($photos, 'likes'));
$totalViews = array_sum(array_column($photos, 'views'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Spot &amp; Fly — Profile</title>
<link rel="stylesheet" href="Home.css">
<link rel="stylesheet" href="Prof.css?v=2">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


</head>

<body>
<!-- navigation bar -->
  <nav aria-label="Main navigation">
    <div class="logo">
      <a href="Home.html"><img src="1.jpg" alt="Spot &amp; Fly logo"></a>
    </div>
    <div class="nav-links">
      <a href="#">About</a>
      <a href="#">Contact</a>
      <a href="Home.html">Home</a>
      <a href="Profile.php" class="active">Profile</a>
    </div>
  </nav>

  <div class="profile-cover"></div>

  <div class="profile-layout">

    <section class="profile-card">

      <div class="avatar avatar-profile">
        <?php if (!empty($user['profile_photo'])): ?>
          <img src = "uploads/avatars/<?= htmlspecialchars($user['profile_photo']) ?>" alt = "your profile photo">
        <?php else: ?>
          <i class="fa-solid fa-user" aria-hidden="true"></i>
        <?php endif; ?>
      </div>
      
      <form action="upload.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="avatar">
        <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp" hidden onchange="this.form.submit()">
        <label for="avatarInput" class="avatar-change"><i class="fa-solid fa-camera" aria-hidden="true"></i> Change photo</label>
      </form>

      <?php if (!empty($_SESSION['avatar_error'])): ?>
        <p class="avatar-error" role="alert"><?= htmlspecialchars($_SESSION['avatar_error']) ?></p>
        <?php unset($_SESSION['avatar_error']); ?>
      <?php endif; ?>
      
      <h1 class="profile-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h1>
      <p class="profile-handle">@<?= htmlspecialchars($user['username'] ?? 'username') ?> &middot; Aviation Spotter</p>
      <p class="profile-bio">No bio yet.</p>

      <div class="profile-actions">
        <a class="btn btn-primary" href="#"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit Profile</a>
        <a class="btn btn-outline" href="upload.php"><i class="fa-solid fa-camera" aria-hidden="true"></i> Upload Photo</a>
      </div>

      <ul class="profile-stats">
        <li><strong><?= count($photos) ?></strong><span>Photos</span></li>
        <li><strong><?= array_sum(array_column($photos, 'likes')) ?></strong><span>Likes</span></li>
        <li><strong><?= array_sum(array_column($photos, 'views')) ?></strong><span>Views</span></li>
        <li><strong>0</strong><span>Rank</span></li>
      </ul>

      <ul class="profile-details">
        <li><i class="fa-solid fa-envelope" aria-hidden="true"></i><?= htmlspecialchars($user['email']) ?></li>
        <li><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= htmlspecialchars($user['address'] ?? 'No address') ?></li>
        <li><i class="fa-solid fa-calendar" aria-hidden="true"></i>Joined <?= date('F j, Y', strtotime($user['created_at'])) ?></li>
      </ul>

      <a class="profile-logout" href="logout.php"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Log out</a>

    </section>

    <main class="profile-main">

      
      <section class="profile-section" id="my-photos" aria-labelledby="my-photos-title">
        <h2 id="my-photos-title">My Photos</h2>

        <?php if (empty($photos)): ?>

          <div class="empty-state">
            <i class="fa-solid fa-camera" aria-hidden="true"></i>
            <h3>No photos yet</h3>
            <p>Share your first aircraft photo with the Spot &amp; Fly community.</p>
            <a class="btn btn-primary" href="upload.php">Post your first photo</a>
          </div>

        <?php else: ?>

          <div class="card-row">
            <?php foreach ($photos as $photo): ?>
              <article class="card">
                <img src="uploads/<?= htmlspecialchars($photo['image']) ?>" alt="<?= htmlspecialchars($photo['title']) ?>">
                <h3><?= htmlspecialchars($photo['title']) ?></h3>
                <?php if (!empty($photo['caption'])): ?>
                <p class="card-caption"><?= htmlspecialchars($photo['caption']) ?></p>
                <?php endif; ?>

                <ul class="card-stats">
                  <li><i class="fa-solid fa-heart fa-fw" aria-hidden="true"></i> Likes: <?= (int)$photo['likes'] ?></li>
                  <li><i class="fa-solid fa-comment fa-fw" aria-hidden="true"></i> Comments: <?= (int)$photo['comment'] ?></li>
                  <li><i class="fa-solid fa-eye fa-fw" aria-hidden="true"></i> Views: <?= (int)$photo['views'] ?></li>
                </ul>
                
              </article>
            <?php endforeach; ?>
          </div>

        <?php endif; ?>

      </section>
    </main>
  </div>

  <footer>
    <a href="#">FAQ</a>
    <a href="#">Contact Us</a>
  </footer>

</body>
</html>