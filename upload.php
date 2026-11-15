<?php
session_start();
require 'db.php';

// must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.html');
    exit;
}

$userId = $_SESSION['user_id'];
$error  = '';
$categories = ['Commercial Airliners', 'Cargo Aircraft', 'Military Aircraft', 'General Aviation'];

// Checks an uploaded image. Returns an error message, or '' if it is fine.
// On success, $ext gets the correct file extension (jpg, png or webp).
function checkImage($file, $maxMB, &$ext) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return 'Upload failed. Please choose a photo.';
    }
    if ($file['size'] > $maxMB * 1024 * 1024) {
        return "Photo must be under $maxMB MB.";
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return 'Only JPG, PNG, or WEBP images are allowed.';
    }
    $ext = $allowed[$mime];
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ext = '';

    // ---------- 1. Change profile picture ----------
    if ($action === 'avatar') {
        $error = checkImage($_FILES['avatar'] ?? null, 2, $ext);

        if ($error === '') {
            $dir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $name = 'u' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $name)) {
                // remember the old picture so it can be deleted
                $stmt = $pdo->prepare('SELECT profile_photo FROM users WHERE user_id = ?');
                $stmt->execute([$userId]);
                $old = $stmt->fetchColumn();

                $stmt = $pdo->prepare('UPDATE users SET profile_photo = ? WHERE user_id = ?');
                $stmt->execute([$name, $userId]);

                if ($old && is_file($dir . $old)) {
                    unlink($dir . $old);
                }
                header('Location: Profile.php');
                exit;
            }
            $error = 'Could not save the picture.';
        }

    } elseif ($action === 'photo') {
        $title    = trim($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $category = $_POST['category'] ?? '';

        $error = checkImage($_FILES['photo'] ?? null, 5, $ext);
        if ($error === '' && $title === '') {
            $error = 'Please enter a title.';
        }
        if ($error === '' && !in_array($category, $categories, true)) {
            $error = 'Please choose a category.';
        }

        if ($error === '') {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $name = bin2hex(random_bytes(8)) . '.' . $ext;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dir . $name)) {
             $stmt = $pdo->prepare('INSERT INTO photos (user_id, title, caption, category, image) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $title, $caption, $category, $name]);
                header('Location: Profile.php');
                exit;
            }
            $error = 'Could not save the photo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Spot &amp; Fly — Post a Photo</title>
<link rel="stylesheet" href="Home.css">
<link rel="stylesheet" href="Prof.css?v=2">
<link rel="stylesheet" href="Upload.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

  <nav aria-label="Main navigation">
    <div class="logo">
      <a href="Home.html"><img src="1.jpg" alt="Spot &amp; Fly logo"></a>
    </div>
    <div class="nav-links">
      <a href="#">About</a>
      <a href="#">Contact</a>
      <a href="Home.html">Home</a>
      <a href="Profile.php">Profile</a>
    </div>
  </nav>

  <main class="upload-main">
    <a class="upload-back" href="Profile.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to profile</a>

    <section class="upload-card">
      <h1>Post a Photo</h1>
      <p class="hint">Share your aircraft photo with the Spot &amp; Fly community.</p>

      <?php if ($error !== ''): ?>
        <p class="upload-error" role="alert"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post" action="upload.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="photo">

        <div class="upload-field">
          <label for="title">Title</label>
          <input type="text" id="title" name="title" maxlength="100" placeholder="e.g. Boeing 747 at sunset" required>
        </div>

        <div class="upload-field">
          <label for="caption">Caption<small>(optional)</small></label>
          <input type="text" id="caption" name="caption" maxlength="200" placeholder="Describe your photo">
        </div>

        <div class="upload-field">
          <label for="category">Category</label>
          <select id="category" name="category" required>
            <?php foreach ($categories as $c): ?>
              <option><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="upload-field">
          <label for="photo">Photo</label>
          <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" required>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-camera" aria-hidden="true"></i> Post</button>
      </form>
    </section>
  </main>

  <footer>
    <a href="#">FAQ</a>
    <a href="#">Contact Us</a>
  </footer>

</body>
</html> 