<?php
require 'db.php';
$errors=[];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=$_POST['action']??'';
    if ($action==='save') {
        $id=(int)($_POST['fee_id']??0); $studentId=(int)($_POST['student_id']??0); $amount=trim($_POST['amount']??''); $date=$_POST['payment_date']??''; $status=$_POST['payment_status']??'Paid'; $remarks=trim($_POST['remarks']??'');
        if (!$studentId) $errors[]='Choose a student.';
        if (!is_numeric($amount) || (float)$amount<=0 || (float)$amount>99999999) $errors[]='Enter an amount greater than zero.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || !strtotime($date)) $errors[]='Choose a valid payment date.';
        if (!in_array($status,['Paid','Pending'],true)) $errors[]='Choose a valid payment status.';
        if (!$errors) {
            $amount=(float)$amount; $remarks=$remarks===''?null:$remarks;
            if ($id) { $st=$conn->prepare('UPDATE fees SET student_id=?,amount=?,payment_date=?,payment_status=?,remarks=? WHERE fee_id=?'); $st->bind_param('idsssi',$studentId,$amount,$date,$status,$remarks,$id); }
            else { $st=$conn->prepare('INSERT INTO fees (student_id,amount,payment_date,payment_status,remarks) VALUES (?,?,?,?,?)'); $st->bind_param('idsss',$studentId,$amount,$date,$status,$remarks); }
            $st->execute(); $_SESSION['flash']=$id?'Fee record updated.':'Fee payment record added.'; redirect('fees.php');
        }
    } elseif ($action==='delete') {
        $id=(int)($_POST['fee_id']??0); $st=$conn->prepare('DELETE FROM fees WHERE fee_id=?'); $st->bind_param('i',$id); $st->execute(); $_SESSION['flash']=$st->affected_rows?'Fee record deleted.':'Fee record not found.'; redirect('fees.php');
    }
}
$edit=['fee_id'=>0,'student_id'=>'','amount'=>'','payment_date'=>date('Y-m-d'),'payment_status'=>'Paid','remarks'=>''];
if(isset($_GET['edit'])) { $id=(int)$_GET['edit']; $st=$conn->prepare('SELECT * FROM fees WHERE fee_id=?'); $st->bind_param('i',$id); $st->execute(); $edit=$st->get_result()->fetch_assoc()?:$edit; }
$students=$conn->query('SELECT student_id,name,enrollment_no FROM students ORDER BY name');
$q=trim($_GET['q']??'');
$st=$conn->prepare('SELECT f.*,s.name,s.enrollment_no FROM fees f JOIN students s ON s.student_id=f.student_id WHERE s.name LIKE ? OR s.enrollment_no LIKE ? OR f.payment_status LIKE ? ORDER BY f.fee_id DESC');
$like='%'.$q.'%'; $st->bind_param('sss',$like,$like,$like); $st->execute(); $rows=$st->get_result();
$pageTitle='Fee Management'; require 'header.php';
?>
<h1 class="h2 mb-1">Fee Management</h1><p class="text-secondary mb-4">Record payments and search or update fee entries.</p>
<?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card panel-card p-3 mb-4"><h2 class="h5"><?= $edit['fee_id']?'Edit Fee Record':'Add Fee Record' ?></h2><form method="post" class="row g-3"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="fee_id" value="<?= (int)$edit['fee_id'] ?>">
<div class="col-md-4"><label class="form-label">Student *</label><select class="form-select" name="student_id" required><option value="">Choose student</option><?php while($s=$students->fetch_assoc()): ?><option value="<?= (int)$s['student_id'] ?>" <?= (int)$edit['student_id']===(int)$s['student_id']?'selected':'' ?>><?= e($s['enrollment_no'].' — '.$s['name']) ?></option><?php endwhile; ?></select></div>
<div class="col-md-2"><label class="form-label">Amount (₹) *</label><input type="number" class="form-control" name="amount" min="0.01" max="99999999" step="0.01" required value="<?= e($edit['amount']) ?>"></div>
<div class="col-md-2"><label class="form-label">Payment Date *</label><input type="date" class="form-control" name="payment_date" required value="<?= e($edit['payment_date']) ?>"></div>
<select class="form-select" name="payment_status"><?php foreach(['Paid','Pending'] as $status): ?><option <?= $edit['payment_status']===$status?'selected':'' ?>><?= $status ?></option><?php endforeach; ?></select>
<div class="col-md-2"><label class="form-label">Remarks</label><input class="form-control" name="remarks" maxlength="255" value="<?= e($edit['remarks']??'') ?>"></div>
<div class="col-12"><button class="btn btn-primary"><?= $edit['fee_id']?'Update Record':'Add Record' ?></button><?php if($edit['fee_id']): ?> <a class="btn btn-outline-secondary" href="fees.php">Cancel</a><?php endif; ?></div></form></div>
<form method="get" class="mb-3"><div class="input-group"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search student, enrollment or status"><button class="btn btn-dark">Search</button><?php if($q!==''): ?><a href="fees.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?></div></form>
<div class="card panel-card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Student</th><th>Amount</th><th>Date</th><th>Status</th><th>Remarks</th><th>Actions</th></tr></thead><tbody>
<?php if(!$rows->num_rows): ?><tr><td colspan="6" class="text-center py-4">No fee records found.</td></tr><?php endif; ?>
<?php while($f=$rows->fetch_assoc()): ?><tr><td><?= e($f['name']) ?><br><small class="text-secondary"><?= e($f['enrollment_no']) ?></small></td><td>₹<?= number_format((float)$f['amount'],2) ?></td><td><?= e($f['payment_date']) ?></td><td><?= e($f['payment_status']) ?></td><td><?= e($f['remarks']??'—') ?></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="fees.php?edit=<?= (int)$f['fee_id'] ?>">Edit</a> <form class="d-inline" method="post" onsubmit="return confirm('Delete this fee record?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="fee_id" value="<?= (int)$f['fee_id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endwhile; ?>
</tbody></table></div></div>
<?php require 'footer.php'; ?>
