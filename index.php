<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "travel_reviews";

// 建立資料庫連接
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查資料庫連接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 設定圖片上傳路徑
$image_upload_dir = "uploads/";
if (!is_dir($image_upload_dir)) {
    mkdir($image_upload_dir, 0777, true); // 如果目錄不存在，創建目錄
}

// 新增評價（包含圖片上傳）
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = $_POST['name'];
    $destination = $_POST['destination'];
    $rating = $_POST['rating'];
    $review = $_POST['review'];
    $image = null;

    // 處理圖片上傳
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image_name = basename($_FILES['image']['name']);
        $image_path = $image_upload_dir . time() . "_" . $image_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            $image = $image_path;
        } else {
            echo "圖片上傳失敗<br>";
        }
    }

    // 儲存評價至資料庫
    $sql = "INSERT INTO reviews (name, destination, rating, review, image) VALUES ('$name', '$destination', '$rating', '$review', '$image')";
    if ($conn->query($sql) === TRUE) {
        echo "新增評價成功<br>";
    } else {
        echo "錯誤: " . $conn->error;
    }
}

// 刪除評價（刪除圖片檔案）
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    // 查找圖片檔案並刪除
    $sql = "SELECT image FROM reviews WHERE id = $delete_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['image'] && file_exists($row['image'])) {
            unlink($row['image']); // 刪除圖片檔案
        }
    }

    // 刪除資料庫中的評價
    $sql = "DELETE FROM reviews WHERE id = $delete_id";
    if ($conn->query($sql) === TRUE) {
        echo "刪除成功<br>";
    } else {
        echo "錯誤: " . $conn->error;
    }
}

// 點贊功能
if (isset($_GET['like_id'])) {
    $like_id = $_GET['like_id'];
    $sql = "UPDATE reviews SET likes = likes + 1 WHERE id = $like_id";
    if ($conn->query($sql) === TRUE) {
        echo "點贊成功<br>";
    } else {
        echo "錯誤: " . $conn->error;
    }
}

// 回覆功能
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reply') {
    $review_id = $_POST['review_id'];
    $reply_text = $_POST['reply_text'];

    $sql = "INSERT INTO replies (review_id, reply_text) VALUES ('$review_id', '$reply_text')";
    if ($conn->query($sql) === TRUE) {
        echo "回覆成功<br>";
    } else {
        echo "錯誤: " . $conn->error;
    }
}

// 查詢功能
$where_clause = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $where_clause = "WHERE name LIKE '%$search%' OR destination LIKE '%$search%' OR review LIKE '%$search%'";
}

// 查詢所有評價及其回覆
$sql_reviews = "SELECT * FROM reviews $where_clause ORDER BY created_at DESC";
$result_reviews = $conn->query($sql_reviews);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>旅遊評價系統</title>
</head>
<body>
    <h1>旅遊評價系統</h1>

    <!-- 新增評價表單 -->
    <h2>新增評價</h2>
    <form action="index.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        姓名：<input type="text" name="name" required><br>
        旅遊目的地：<input type="text" name="destination" required><br>
        評分（1-5）：<input type="number" name="rating" min="1" max="5" required><br>
        評價內容：<textarea name="review" rows="4" required></textarea><br>
        圖片：<input type="file" name="image" accept="image/*"><br>
        <button type="submit">提交評價</button>
    </form>

    <!-- 查詢評價表單 -->
    <h2>查詢評價</h2>
    <form action="index.php" method="GET">
        搜尋關鍵字：<input type="text" name="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <button type="submit">搜尋</button>
        <a href="index.php">顯示全部</a>
    </form>

    <!-- 評價列表 -->
    <h2>所有評價</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>姓名</th>
            <th>旅遊目的地</th>
            <th>評分</th>
            <th>評價內容</th>
            <th>圖片</th>
            <th>點贊數</th>
            <th>操作</th>
        </tr>
        <?php
        if ($result_reviews->num_rows > 0) {
            while ($row = $result_reviews->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["id"] . "</td>
                        <td>" . $row["name"] . "</td>
                        <td>" . $row["destination"] . "</td>
                        <td>" . $row["rating"] . "</td>
                        <td>" . $row["review"] . "</td>
                        <td>";
                if ($row["image"]) {
                    echo "<img src='" . $row["image"] . "' alt='圖片' width='100'>";
                } else {
                    echo "無圖片";
                }
                echo "</td>
                        <td>" . $row["likes"] . "</td>
                        <td>
                            <a href='index.php?like_id=" . $row["id"] . "'>點贊</a> | 
                            <a href='index.php?delete_id=" . $row["id"] . "'>刪除</a> | 
                            <a href='#reply" . $row["id"] . "'>回覆</a>
                        </td>
                    </tr>";

                // 查詢該評價的所有回覆
                $review_id = $row['id'];
                $sql_replies = "SELECT * FROM replies WHERE review_id = $review_id ORDER BY created_at ASC";
                $result_replies = $conn->query($sql_replies);

                if ($result_replies->num_rows > 0) {
                    echo "<tr><td colspan='8'><strong>回覆：</strong><ul>";
                    while ($reply = $result_replies->fetch_assoc()) {
                        echo "<li>" . $reply['reply_text'] . " <em>於 " . $reply['created_at'] . "</em></li>";
                    }
                    echo "</ul></td></tr>";
                }

                // 回覆表單
                echo "<tr>
                        <td colspan='8'>
                            <form id='reply" . $row["id"] . "' action='index.php' method='POST'>
                                <input type='hidden' name='action' value='reply'>
                                <input type='hidden' name='review_id' value='" . $row["id"] . "'>
                                <textarea name='reply_text' rows='2' placeholder='輸入回覆' required></textarea>
                                <br>
                                <button type='submit'>提交回覆</button>
                            </form>
                        </td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='8'>目前沒有評價</td></tr>";
        }
        ?>
    </table>

    <?php
    // 關閉資料庫連接
    $conn->close();
    ?>
</body>
</html>




