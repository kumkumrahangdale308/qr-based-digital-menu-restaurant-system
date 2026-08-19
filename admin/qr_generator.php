<?php

require_once("../api/db.php");

$result = mysqli_query(
    $conn,
    "SELECT table_number FROM restaurant_tables ORDER BY table_number"
);

echo "<h1>Restaurant QR Generator</h1>";

while($row = mysqli_fetch_assoc($result))
{
    $table = $row['table_number'];

    $url =
    "http://localhost/restaurant-system/menu.php?table=".$table;

    $qr =
    "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data="
    . urlencode($url);

    echo "<div style='display:inline-block;margin:20px;text-align:center;'>";

    echo "<h3>Table ".$table."</h3>";

    echo "<img src='".$qr."' width='200'>";

    echo "<br><br>";

    echo "<a href='".$qr."' target='_blank'>
    Download QR
    </a>";

    echo "</div>";
}
?>