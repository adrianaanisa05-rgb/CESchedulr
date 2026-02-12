<?php
include 'db_connect.php';

if (!isset($_POST['id'])) {
    echo "No event ID provided.";
    exit;
}

$id = intval($_POST['id']); // prevent SQL injection

$sql = $conn->prepare("
    SELECT e.*, u.username, venue_name, venue_address, venue_city, venue_postcode,c.club_name,c.club_email,c.club_phone
    FROM events e
    JOIN users u ON e.user_id = u.id
    LEFT JOIN venue v ON e.venue_id = v.venue_id
    LEFT JOIN club c ON e.club_id = c.id
    WHERE e.id = ?
");
$sql->bind_param("i", $id);
$sql->execute();
$result = $sql->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    echo "Event not found.";
    exit;
}

?>

<div class="container">
    <?php if (!empty($event['event_image'])): ?>
        <img src="uploads/<?php echo htmlspecialchars($event['event_image']); ?>" 
             class="img-fluid rounded mb-3" alt="Event Image">
    <?php endif; ?>

    <h3><?php echo !empty($event['title']) ? htmlspecialchars($event['title']) : 'Event Title Not provided'; ?></h3>

     <p>
    <strong> Date:</strong> <?= !empty($event['event_date']) ? date('l, F j, Y h:i A', strtotime($event['event_date'])) : 'Not provided'; ?> 
    - 
     <?= !empty($event['end_date']) ? date('l, F j, Y h:i A', strtotime($event['end_date'])) : 'Not provided'; ?>
  </p>

    <p><strong>Event Description:</strong></p>
<p><?php echo !empty($event['event_description']) ? htmlspecialchars($event['event_description']) : 'No description provided'; ?></p>

<p><strong>Venue name:</strong> <?php echo !empty($event['venue_name']) ? htmlspecialchars($event['venue_name']) : 'Not provided'; ?></p>
<p><strong>Venue address:</strong> <?php echo !empty($event['venue_address']) ? htmlspecialchars($event['venue_address']) : 'No address provided'; ?></p>
<p><strong>City:</strong> <?php echo !empty($event['venue_city']) ? htmlspecialchars($event['venue_city']) : 'Not provided'; ?></p>
<p><strong>Venue Postcode:</strong> <?php echo !empty($event['venue_postcode']) ? htmlspecialchars($event['venue_postcode']) : 'Not provided'; ?></p>

<p><strong>Organized By:</strong> <?php echo !empty($event['club_name']) ? htmlspecialchars($event['club_name']) : 'Club Not Provided'; ?></p>
<p><strong>Club Email:</strong> <?php echo !empty($event['club_email']) ? htmlspecialchars($event['club_email']) : 'Club Email Not Provided'; ?></p>
<p><strong>Club Phone Number:</strong> <?php echo !empty($event['club_phone']) ? htmlspecialchars($event['club_phone']) : 'Club Phone Number Not Provided'; ?></p>
<p><strong>Contact Number:</strong> <?php echo !empty($event['contact_number']) ? htmlspecialchars($event['contact_number']) : 'Not provided'; ?></p>
    <input type="hidden" id="modal_event_id" value="<?php echo $event['id']; ?>">
    <input type="hidden" id="modal_event_status" value="<?php echo $event['event_status']; ?>">
</div>