<?php
$conn = mysqli_connect('localhost', 'root', '', 'aerobook_db');
$hash = password_hash('admin123', PASSWORD_DEFAULT);
mysqli_query($conn, "UPDATE admins SET password='$hash' WHERE username='admin'");
echo "Updated!";
