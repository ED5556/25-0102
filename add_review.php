<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "travel_reviews";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $name = $_POST["name"];
    $destination = $_POST["destination"];
    $rating = $_POST["rating"];
    $review = $_POST["review"];

    $sql = "INSERT INTO reviews (name, destination, rating, review) 
            VALUES ('$name', '$destination', '$rating', '$review')";

    if ($conn->query($sql) === TRUE) {
        echo "新評價已新增<br><br>";
        echo "<a href='index.php'>返回首頁</a>";
    } else {
        echo "錯誤: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
