<?php
//Phiên bản 1: Giải pháp đơn giản nhất
//một class duy nhất thực hiện tất cả công việc
class SimpleDistanceCalculator 
{
    public function getDistance(float $lat1, float $long1, float $lat2, float $long2): float 
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($long1);
        $latTo   = deg2rad($lat2);
        $lonTo   = deg2rad($long2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}

$calculator = new SimpleDistanceCalculator();
$distance = $calculator->getDistance(21.0285, 105.8542, 10.7769, 106.6951);

echo "[PV1] Khoang cach Haversine: " . round($distance, 2) . " km\n";