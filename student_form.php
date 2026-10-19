<?php
require 'db.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$data = ['enrollment_no'=>'','name'=>'','gender'=>'','phone'=>'','email'=>'','course'=>'','semester'=>'','address'=>''];
$errors = [];
if ($id) {
    $stmt = $conn->prepare('SELECT * FROM students WHERE student_id=?');
    $stmt->bind_param('i', $id); $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    if (!$existing) { $_SESSION['flash']='Student record not found.'; redirect('students.php'); }
    $data = array_merge($data, $existing);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (array_keys($data) as $key) { $data[$key] = trim((string)($_POST[$key] ?? '')); }
    if ($data['enrollment_no'] === '') $errors[] = 'Enrollment number is required.';
    if ($data['name'] === '') $errors[] = 'Student name is required.';
    if (!in_array($data['gender'], ['Male','Female','Other'], true)) $errors[] = 'Choose a valid gender.';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $data['phone'])) $errors[] = 'Enter a valid phone number.';
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if ($data['semester'] !== '' && (!ctype_digit($data['semester']) || (int)$data['semester'] < 1 || (int)$data['semester'] > 12)) $errors[] = 'Semester must be between 1 and 12.';
    if (!$errors) {
        try {
            $semester = $data['semester'] === '' ? null : (int)$data['semester'];
            $email = $data['email'] === '' ? null : $data['email'];
            $course = $data['course'] === '' ? null : $data['course'];
            $address = $data['address'] === '' ? null : $data['address'];
            if ($id) {
                $stmt = $conn->prepare('UPDATE students SET enrollment_no=?,name=?,gender=?,phone=?,email=?,course=?,semester=?,address=? WHERE student_id=?');
                $stmt->bind_param('ssssssisi', $data['enrollment_no'],$data['name'],$data['gender'],$data['phone'],$email,$course,$semester,$address,$id);
                $stmt->execute(); $_SESSION['flash']='Student updated successfully.';
            } else {
                $stmt = $conn->prepare('INSERT INTO students (enrollment_no,name,gender,phone,email,course,semester,address) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->bind_param('ssssssis', $data['enrollment_no'],$data['name'],$data['gender'],$data['phone'],$email,$course,$semester,$address);
                $stmt->execute(); $_SESSION['flash']='Student added successfully.';
            }
            redirect('students.php');
        } catch (mysqli_sql_exception $ex) {
            if ((int)$ex->getCode() === 1062) $errors[] = 'That enrollment number already exists.';
            else $errors[] = 'Could not save the record. Please check your data and try again.';
        }
    }
}
$pageTitle = $id ? 'Edit Student' : 'Add Student';
require 'header.php';
?>
<div class="mb-3"><a href="students.php" class="text-decoration-none">← Back to students</a></div>
<div class="form-card mx-auto"><h1 class="h3 mb-1"><?= $id ? 'Edit Student' : 'Add Student' ?></h1><p class="text-secondary mb-4">Fields marked * are required.</p>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" novalidate><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-md-6"><label class="form-label">Enrollment Number *</label><input class="form-control" name="enrollment_no" maxlength="30" required value="<?= e($data['enrollment_no']) ?>"></div>
  <div class="col-md-6"><label class="form-label">Full Name *</label><input class="form-control" name="name" maxlength="100" required value="<?= e($data['name']) ?>"></div>
  <div class="col-md-6"><label class="form-label">Gender *</label><select class="form-select" name="gender" required><option value="">Choose…</option><?php foreach (['Male','Female','Other'] as $g): ?><option value="<?= $g ?>" <?= $data['gender']===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?></select></div>
  <div class="col-md-6"><label class="form-label">Phone *</label><input class="form-control" name="phone" maxlength="20" required value="<?= e($data['phone']) ?>"></div>
  <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" maxlength="100" value="<?= e($data['email']) ?>"></div>
  <div class="col-md-6"><label class="form-label">Course</label><input class="form-control" name="course" maxlength="60" placeholder="e.g. BCA" value="<?= e($data['course']) ?>"></div>
  <div class="col-md-6"><label class="form-label">Semester</label><input type="number" class="form-control" name="semester" min="1" max="12" value="<?= e($data['semester']) ?>"></div>
  <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3" maxlength="255"><?= e($data['address']) ?></textarea></div>
</div>
<div class="mt-4 d-flex gap-2"><button class="btn btn-primary" type="submit"><?= $id ? 'Save Changes' : 'Add Student' ?></button><a class="btn btn-outline-secondary" href="students.php">Cancel</a></div>
</form></div>
<?php require 'footer.php'; ?>
