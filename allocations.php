<?php
require 'db.php';
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=$_POST['action']??'';
    if ($action==='allocate') {
        $studentId=(int)($_POST['student_id']??0); $roomId=(int)($_POST['room_id']??0); $date=$_POST['allocation_date']??date('Y-m-d');
        if (!$studentId || !$roomId) $errors[]='Choose a student and a room.';
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || !strtotime($date)) $errors[]='Choose a valid allocation date.';
        else {
            try {
                $conn->begin_transaction();
                $st=$conn->prepare('SELECT student_id FROM students WHERE student_id=?'); $st->bind_param('i',$studentId); $st->execute();
                if (!$st->get_result()->num_rows) throw new RuntimeException('Student not found.');
                $st=$conn->prepare('SELECT room_id,capacity,status FROM rooms WHERE room_id=? FOR UPDATE'); $st->bind_param('i',$roomId); $st->execute(); $room=$st->get_result()->fetch_assoc();
                if (!$room) throw new RuntimeException('Room not found.');
                if ($room['status']==='Maintenance') throw new RuntimeException('This room is under maintenance.');
                $st=$conn->prepare('SELECT COUNT(*) c FROM allocations WHERE student_id=? AND release_date IS NULL'); $st->bind_param('i',$studentId); $st->execute();
                if ((int)$st->get_result()->fetch_assoc()['c']>0) throw new RuntimeException('This student already has an active room allocation.');
                $st=$conn->prepare('SELECT COUNT(*) c FROM allocations WHERE room_id=? AND release_date IS NULL'); $st->bind_param('i',$roomId); $st->execute();
                if ((int)$st->get_result()->fetch_assoc()['c'] >= (int)$room['capacity']) throw new RuntimeException('This room is already full.');
                $st=$conn->prepare('INSERT INTO allocations (student_id,room_id,allocation_date) VALUES (?,?,?)'); $st->bind_param('iis',$studentId,$roomId,$date); $st->execute();
                $conn->commit(); $_SESSION['flash']='Room allocated successfully.'; redirect('allocations.php');
            } catch (Throwable $ex) {
                $conn->rollback();
                $errors[]=$ex instanceof RuntimeException ? $ex->getMessage() : 'Could not allocate room.';
            }
        }
    } elseif ($action==='release') {
        $allocationId=(int)($_POST['allocation_id']??0);
        $st=$conn->prepare('UPDATE allocations SET release_date=CURDATE() WHERE allocation_id=? AND release_date IS NULL'); $st->bind_param('i',$allocationId); $st->execute();
        $_SESSION['flash']=$st->affected_rows?'Allocation released.':'Allocation not found or already released.'; redirect('allocations.php');
    }
}
$students=$conn->query("SELECT s.student_id,s.name,s.enrollment_no FROM students s WHERE NOT EXISTS (SELECT 1 FROM allocations a WHERE a.student_id=s.student_id AND a.release_date IS NULL) ORDER BY s.name");
$rooms=$conn->query("SELECT r.room_id,r.room_number,r.capacity,r.status,(SELECT COUNT(*) FROM allocations a WHERE a.room_id=r.room_id AND a.release_date IS NULL) occupied FROM rooms r WHERE r.status='Available' ORDER BY r.room_number");
$rows=$conn->query("SELECT a.*,s.name,s.enrollment_no,r.room_number FROM allocations a JOIN students s ON s.student_id=a.student_id JOIN rooms r ON r.room_id=a.room_id ORDER BY a.allocation_id DESC");
$pageTitle='Room Allocations'; require 'header.php';
?>
<h1 class="h2 mb-1">Room Allocation</h1><p class="text-secondary mb-4">Assign one active room per student and prevent room over-capacity.</p>
<?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card panel-card p-3 mb-4"><h2 class="h5">Allocate a Room</h2><form method="post" class="row g-3 align-items-end"><?= csrf_field() ?><input type="hidden" name="action" value="allocate">
<div class="col-md-5"><label class="form-label">Student</label><select class="form-select" name="student_id" required><option value="">Choose student</option><?php while($s=$students->fetch_assoc()): ?><option value="<?= (int)$s['student_id'] ?>"><?= e($s['enrollment_no'].' — '.$s['name']) ?></option><?php endwhile; ?></select></div>
<div class="col-md-4"><label class="form-label">Available room</label><select class="form-select" name="room_id" required><option value="">Choose room</option><?php while($r=$rooms->fetch_assoc()): if((int)$r['occupied'] >= (int)$r['capacity']) continue; ?><option value="<?= (int)$r['room_id'] ?>"><?= e($r['room_number']) ?> (<?= (int)$r['capacity']-(int)$r['occupied'] ?> beds left)</option><?php endwhile; ?></select></div>
<div class="col-md-2"><label class="form-label">Date</label><input type="date" class="form-control" name="allocation_date" required value="<?= date('Y-m-d') ?>"></div><div class="col-md-1"><button class="btn btn-primary w-100">Assign</button></div></form></div>
<div class="card panel-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Student</th><th>Room</th><th>Allocation Date</th><th>Release Date</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php if(!$rows->num_rows): ?><tr><td colspan="6" class="text-center py-4">No allocation records yet.</td></tr><?php endif; ?>
<?php while($a=$rows->fetch_assoc()): ?><tr><td><?= e($a['name']) ?><br><small class="text-secondary"><?= e($a['enrollment_no']) ?></small></td><td><?= e($a['room_number']) ?></td><td><?= e($a['allocation_date']) ?></td><td><?= e($a['release_date']??'—') ?></td><td><?= $a['release_date']===null?'<span class="badge text-bg-success">Active</span>':'<span class="badge text-bg-secondary">Released</span>' ?></td><td><?php if($a['release_date']===null): ?><form method="post" onsubmit="return confirm('Release this room allocation?');"><?= csrf_field() ?><input type="hidden" name="action" value="release"><input type="hidden" name="allocation_id" value="<?= (int)$a['allocation_id'] ?>"><button class="btn btn-sm btn-outline-danger">Release</button></form><?php else: ?>—<?php endif; ?></td></tr><?php endwhile; ?>
</tbody></table></div></div>
<?php require 'footer.php'; ?>
