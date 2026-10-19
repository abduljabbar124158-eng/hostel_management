<?php
require 'db.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['room_id'] ?? 0);
        $number = trim($_POST['room_number'] ?? '');
        $type = trim($_POST['room_type'] ?? '');
        $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? 'Available';
        if ($number === '' || $type === '') $errors[] = 'Room number and type are required.';
        if (!$capacity || $capacity < 1 || $capacity > 20) $errors[] = 'Capacity must be between 1 and 20.';
        if (!in_array($status, ['Available','Maintenance'], true)) $errors[] = 'Invalid room status.';
        if (!$errors) {
            try {
                if ($id) { $stmt=$conn->prepare('UPDATE rooms SET room_number=?,room_type=?,capacity=?,status=? WHERE room_id=?'); $stmt->bind_param('ssisi',$number,$type,$capacity,$status,$id); }
                else { $stmt=$conn->prepare('INSERT INTO rooms (room_number,room_type,capacity,status) VALUES (?,?,?,?)'); $stmt->bind_param('ssis',$number,$type,$capacity,$status); }
                $stmt->execute(); $_SESSION['flash']=$id?'Room updated.':'Room added.'; redirect('rooms.php');
            } catch (mysqli_sql_exception $ex) { $errors[] = ((int)$ex->getCode()===1062) ? 'Room number already exists.' : 'Could not save room. Check capacity and occupancy.'; }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['room_id'] ?? 0);
        try { $stmt=$conn->prepare('DELETE FROM rooms WHERE room_id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $_SESSION['flash']=$stmt->affected_rows?'Room deleted.':'Room not found.'; }
        catch (mysqli_sql_exception $ex) { $_SESSION['flash']='This room has allocation history and cannot be deleted. Keep it or release its allocations first.'; }
        redirect('rooms.php');
    }
}
$edit = ['room_id'=>0,'room_number'=>'','room_type'=>'Double','capacity'=>2,'status'=>'Available'];
if (isset($_GET['edit'])) { $id=(int)$_GET['edit']; $st=$conn->prepare('SELECT * FROM rooms WHERE room_id=?'); $st->bind_param('i',$id); $st->execute(); $edit=$st->get_result()->fetch_assoc() ?: $edit; }
$q=trim($_GET['q']??'');
$st=$conn->prepare("SELECT r.*,(SELECT COUNT(*) FROM allocations a WHERE a.room_id=r.room_id AND a.release_date IS NULL) occupied FROM rooms r WHERE r.room_number LIKE ? OR r.room_type LIKE ? ORDER BY r.room_number");
$like='%'.$q.'%'; $st->bind_param('ss',$like,$like); $st->execute(); $rooms=$st->get_result();
$pageTitle='Room Management'; require 'header.php';
?>
<h1 class="h2 mb-1">Room Management</h1><p class="text-secondary mb-4">Add rooms and track current occupancy.</p>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-4"><div class="card panel-card p-3"><h2 class="h5"><?= $edit['room_id']?'Edit Room':'Add Room' ?></h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="room_id" value="<?= (int)$edit['room_id'] ?>">
<label class="form-label">Room Number *</label><input class="form-control mb-3" name="room_number" maxlength="20" required value="<?= e($edit['room_number']) ?>">
<label class="form-label">Room Type *</label><select class="form-select mb-3" name="room_type"><?php foreach(['Single','Double','Triple','Dormitory'] as $type): ?><option <?= $edit['room_type']===$type?'selected':'' ?>><?= $type ?></option><?php endforeach; ?></select>
<label class="form-label">Capacity *</label><input type="number" min="1" max="20" class="form-control mb-3" name="capacity" required value="<?= e($edit['capacity']) ?>">
<label class="form-label">Status</label><select class="form-select mb-3" name="status"><option <?= $edit['status']==='Available'?'selected':'' ?>>Available</option><option <?= $edit['status']==='Maintenance'?'selected':'' ?>>Maintenance</option></select>
<button class="btn btn-primary" type="submit"><?= $edit['room_id']?'Update Room':'Add Room' ?></button> <?php if($edit['room_id']): ?><a class="btn btn-outline-secondary" href="rooms.php">Cancel</a><?php endif; ?></form></div></div>
<div class="col-lg-8"><form class="mb-3" method="get"><div class="input-group"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search room number/type"><button class="btn btn-dark">Search</button></div></form><div class="card panel-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Room</th><th>Type</th><th>Occupancy</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(!$rooms->num_rows): ?><tr><td colspan="5" class="text-center py-4">No rooms found.</td></tr><?php endif; ?>
<?php while($r=$rooms->fetch_assoc()): ?><tr><td class="fw-semibold"><?= e($r['room_number']) ?></td><td><?= e($r['room_type']) ?></td><td><?= (int)$r['occupied'] ?> / <?= (int)$r['capacity'] ?></td><td><?= e($r['status']) ?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="rooms.php?edit=<?= (int)$r['room_id'] ?>">Edit</a> <form class="d-inline" method="post" onsubmit="return confirm('Delete this room?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="room_id" value="<?= (int)$r['room_id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endwhile; ?>
</tbody></table></div></div></div></div>
<?php require 'footer.php'; ?>
