
<?php
$conn = mysqli_connect("localhost", "username", "password", "finedining");
if ($conn) {
    echo "Connected successfully!";
} else {
    echo "Connection failed: " . mysqli_connect_error();
}
?>