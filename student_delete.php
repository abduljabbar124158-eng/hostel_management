<?php
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('students.php');
verify_csrf();
$id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
if (!$id) { $_SESSION['flash']='Invalid student ID.'; redirect('students.php'); }
try {
    $stmt = $conn->prepare('DELETE FROM students WHERE student_id=?');
    $stmt->bind_param('i', $id); $stmt->execute();
    $_SESSION['flash'] = $stmt->affected_rows ? 'Student deleted successfully.' : 'Student record not found.';
} catch (mysqli_sql_exception $ex) {
    $_SESSION['flash'] = 'This student has fee or allocation history. Remove the related records first, then try again.';
}
redirect('students.php');
