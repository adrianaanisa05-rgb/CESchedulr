<?php
session_start();
include 'db_connect.php';
$errors=[];
$current_page = basename($_SERVER['PHP_SELF']);
$create_message=[];
$user_id = $_SESSION['user_id'];

$update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE receiver_id = ?");
$update->bind_param("i", $user_id);
$update->execute();
$update->close();

$sql = $conn->prepare("
   SELECT n.*, e.title AS event_title, u.username AS sender_name
    FROM notifications n
    LEFT JOIN events e ON n.event_id = e.id
    LEFT JOIN users u ON n.sender_id = u.id
    WHERE n.receiver_id = ?
      AND n.type IN ('invitation','announcement','action')
    ORDER BY n.created_at DESC
");
$sql->bind_param("i", $user_id);
$sql->execute();
$notifications = $sql->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html> 
<html lang="en"> <head> 
<meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
<title>Event Dashboard</title> 
<!-- Google Material Icons --> 
 <link rel="stylesheet" href="styles.css?v=4"> 
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" 
 rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>

<?php
if ($_SESSION['user_type'] === 'admin') {
    include 'partials/sidebar_admin.php';
} elseif ($_SESSION['user_type'] === 'organizer') {
    include 'partials/sidebar_organizer.php';
} else {
    include 'partials/sidebar_participant.php';
}
?>

<div class="main-content" id="mainContent">
    <div class="header">
        <h1 class="page-title">My Inbox</h1>


<?php if (empty($notifications)): ?>
    <p class="text-center text-muted">No notifications yet.</p>
<?php else: ?>
<ul class="list-group">
<?php foreach ($notifications as $n): ?>
    <li class="list-group-item mb-3 shadow-sm 
        <?= $n['is_read'] ? '' : 'list-group-item-info'; ?>">

        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 class="mb-1">
                    <?= htmlspecialchars($n['title']); ?>
                </h5>

                <small class="text-muted">
                    From: <?= htmlspecialchars($n['sender_name']); ?> |
                    Event: <?= htmlspecialchars($n['event_title']); ?> |
                    <?= $n['created_at']; ?>
                </small>

               
            </div>

           
            <span class="badge 
                <?= $n['type'] === 'invitation' ? 'bg-warning' : 'bg-primary'; 
                 ($n['type'] === 'action' ? 'bg-danger' : 'bg-primary');
                ?>">
                <?= ucfirst($n['type']); ?>
            </span>
        </div>

        
        <?php if ($n['type'] === 'invitation' && $n['response'] === 'pending'): ?>
            
            <div class="mt-3 d-flex gap-2">

               
                <form method="POST" action="respond_invitation.php">
                    <input type="hidden" name="notification_id" value="<?= $n['id']; ?>">
                    <input type="hidden" name="event_id" value="<?= $n['event_id']; ?>">
                    <button type="submit" name="accept" class="btn btn-success btn-sm">
                        Accept & Join
                    </button>
                </form>

                
                <form method="POST" action="respond_invitation.php">
                    <input type="hidden" name="notification_id" value="<?= $n['id']; ?>">
                    <button type="submit" name="decline" class="btn btn-danger btn-sm">
                        Decline
                    </button>
                </form>

            </div>
            <?php endif; ?>
        
        <?php if ($n['type'] === 'invitation' && $n['response'] !== 'pending'): ?>
    <span class="badge <?= $n['response'] === 'accepted' ? 'bg-success' : 'bg-danger'; ?>">
        <?= ucfirst($n['response']); ?>
    </span>
<?php endif; ?>

        <?php if ($n['type'] === 'announcement'): ?>
    <div class="mt-2 p-2 bg-light border rounded">
        <strong>Announcement:</strong> <?= nl2br(htmlspecialchars($n['message'])); ?>
    </div>

    <?php elseif ($n['type'] === 'action'): ?>

    <div class="mt-2 p-3 border border-danger rounded bg-danger bg-opacity-10">
        <strong class="text-danger">Action Taken:</strong><br>
        <?= nl2br(htmlspecialchars($n['message'])); ?>
    </div>

<?php else: ?>
    <p class="mt-2"><?= nl2br(htmlspecialchars($n['message'])); ?></p>
<?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>


</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
const mainContent = document.getElementById("mainContent");


sidebar.addEventListener("mouseenter", () => {
    sidebar.classList.remove("collapsed");
    mainContent.style.marginLeft = "250px"; 
});

sidebar.addEventListener("mouseleave", () => {
    sidebar.classList.add("collapsed");
    mainContent.style.marginLeft = "60px"; 
});


const toggleBtn = document.getElementById("sidebarToggle");
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
    if (sidebar.classList.contains("collapsed")) {
        mainContent.style.marginLeft = "60px";
    } else {
        mainContent.style.marginLeft = "250px";
    }
});
});
 </script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" 
 crossorigin="anonymous">
</script>
</body> 
</html> 