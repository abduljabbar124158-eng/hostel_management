<?php
require 'db.php';
$pageTitle = 'Dashboard';
$students = (int)$conn->query('SELECT COUNT(*) c FROM students')->fetch_assoc()['c'];
$rooms = (int)$conn->query('SELECT COUNT(*) c FROM rooms')->fetch_assoc()['c'];
$activeAllocations = (int)$conn->query('SELECT COUNT(*) c FROM allocations WHERE release_date IS NULL')->fetch_assoc()['c'];
$pending = (float)$conn->query("SELECT COALESCE(SUM(amount),0) total FROM fees WHERE payment_status = 'Pending'")->fetch_assoc()['total'];
$roomStats = $conn->query("SELECT r.room_id,r.room_number,r.room_type,r.capacity,r.status,
    (SELECT COUNT(*) FROM allocations a WHERE a.room_id=r.room_id AND a.release_date IS NULL) AS occupied
    FROM rooms r ORDER BY r.room_number");
require 'header.php';
?>
<div class="hero p-4 p-md-5 mb-4">
  <div class="row align-items-center">
    <div class="col-lg-8"><p class="eyebrow mb-2">HOSTEL ADMINISTRATION</p><h1 class="display-6 fw-bold">Management Dashboard</h1><p class="mb-0 text-secondary">Manage student records, rooms, allocations and fee payments in one place.</p></div>
    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0"><a class="btn btn-primary" href="student_form.php">+ Add Student</a></div>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="stat-card"><span>Total Students</span><strong><?= $students ?></strong><small>Registered students</small></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><span>Total Rooms</span><strong><?= $rooms ?></strong><small>Room records</small></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><span>Active Allocations</span><strong><?= $activeAllocations ?></strong><small>Students currently housed</small></div></div>
  <div class="col-6 col-lg-3"><div class="stat-card"><span>Pending Fees</span><strong>₹<?= number_format($pending, 2) ?></strong><small>Recorded outstanding amounts</small></div></div>
</div>
<div class="card panel-card">
  <div class="card-header bg-white d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Room occupancy</h2><a href="rooms.php" class="small">Manage rooms →</a></div>
  <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Room</th><th>Type</th><th>Occupancy</th><th>Status</th></tr></thead><tbody>
  <?php while ($r = $roomStats->fetch_assoc()): ?>
    <tr><td class="fw-semibold"><?= e($r['room_number']) ?></td><td><?= e($r['room_type']) ?></td><td><?= (int)$r['occupied'] ?> / <?= (int)$r['capacity'] ?></td><td><?php if ($r['status']==='Maintenance'): ?><span class="badge text-bg-warning">Maintenance</span><?php elseif ((int)$r['occupied'] >= (int)$r['capacity']): ?><span class="badge text-bg-secondary">Full</span><?php else: ?><span class="badge text-bg-success">Available</span><?php endif; ?></td></tr>
  <?php endwhile; ?>
  </tbody></table></div>
</div>
<?php require 'footer.php'; ?>