<!-- Security & Injection Prevention
What's wrong with the following code? How would you fix it?
```php
$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
``` -->

<?php
//database connection
$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "test";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = $id");

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"]. " - Name: " . $row["name"]. "<br>";
    }
} else {
    echo "No results found";
}

$stmt->close();
$conn->close();
?>