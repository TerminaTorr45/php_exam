$mysqli = new mysqli("localhost", "root", "root", "php_exam_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}