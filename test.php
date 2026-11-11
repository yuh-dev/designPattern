<?php
// Bài toán: Tính khoảng cách Euclidean giữa 2 điểm A(x1, y1) và B(x2, y2)
function tinhKhoangCach($x1, $y1, $x2, $y2) {
    $dx = $x2 - $x1;
    $dy = $y2 - $y1;
    return sqrt($dx * $dx + $dy * $dy);
}

// Tọa độ A(1, 2) và B(4, 6)
$x1 = 1; $y1 = 2;
$x2 = 4; $y2 = 6;

echo "Khoang cach giua A($x1, $y1) va B($x2, $y2) la: " . round(tinhKhoangCach($x1, $y1, $x2, $y2), 2);
?>