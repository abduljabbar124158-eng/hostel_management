<?php
require 'db.php';
$pageTitle = 'Student Management';
$q = trim($_GET['q'] ?? '');
$sql = "SELECT s.*,(SELECT r.room_number FROM allocations a JOIN rooms r ON r.room_id=a.room_id WHERE a.student_id=s.student_id AND a.release_date IS NULL LIMIT 1) AS room_number FROM students s WHERE s.name LIKE ? OR s.enrollment_no LIKE ? OR s.phone LIKE ? ORDER BY s.student_id DESC";
$stmt = $conn->prepare($sql);
$like = '%' . $q . '%';
$stmt->bind_param('sss', $like, $like, $like);
$stmt->execute();
$students = $stmt->get_result();
require 'header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3"><div><h1 class="h2 mb-1">Student Management</h1><p class="text-secondary mb-0">Create, view, update, delete and search student records.</p></div><a class="btn btn-primary" href="student_form.php">+ Add Student</a></div>
<form class="search-bar mb-3" method="get"><div class="input-group"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name, enrollment number or phone"><button class="btn btn-dark" type="submit">Search</button><?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="students.php">Clear</a><?php endif; ?></div></form>
<div class="card panel-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Enrollment No.</th><th>Name</th><th>Course / Sem.</th><th>Phone</th><th>Room</th><th>Actions</th></tr></thead><tbody>
<?php if ($students->num_rows === 0): ?><tr><td colspan="6" class="text-center py-4 text-secondary">No students found.</td></tr><?php endif; ?>
<?php while ($s = $students->fetch_assoc()): ?><tr><td><?= e($s['enrollment_no']) ?></td><td><div class="fw-semibold"><?= e($s['name']) ?></div><small class="text-secondary"><?= e($s['email'] ?? '') ?></small></td><td><?= e($s['course'] ?? '-') ?><?= $s['semester'] ? ' / Sem ' . (int)$s['semester'] : '' ?></td><td><?= e($s['phone']) ?></td><td><?= e($s['room_number'] ?? 'Not allocated') ?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="student_form.php?id=<?= (int)$s['student_id'] ?>">Edit</a> <form class="d-inline" method="post" action="student_delete.php" onsubmit="return confirm('Delete this student? Students with fee or allocation history cannot be deleted.');"><?= csrf_field() ?><input type="hidden" name="student_id" value="<?= (int)$s['student_id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form></td></tr><?php endwhile; ?>
</tbody></table></div></div>
<?php require 'footer.php'; ?>
