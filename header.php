<?php
$pageTitle = $pageTitle ?? 'Hostel Management System';
$notice = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-nav">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">HMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <div class="navbar-nav ms-auto">
        <a class="nav-link" href="index.php">Dashboard</a>
        <a class="nav-link" href="students.php">Students</a>
        <a class="nav-link" href="rooms.php">Rooms</a>
        <a class="nav-link" href="allocations.php">Allocations</a>
        <a class="nav-link" href="fees.php">Fees</a>
      </div>
    </div>
  </div>
</nav>
<main class="container py-4">
<?php if ($notice !== ''): ?><div class="alert alert-info alert-dismissible fade show" role="alert"><?= e($notice) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>