<?php
// FILE: phien_ban_3.php

class Point 
{
    public function __construct(
        private readonly float $lat,
        private readonly float $long,
    ) {}

    public function getLat(): float { return $this->lat; }
    public function getLong(): float { return $this->long; }
}

interface DistanceCalculator 
{
    public function getDistance(Point $point1, Point $point2): float;
}

// Hàm dùng chung tính Haversine cơ bản
function haversine(Point $p1, Point $p2): float {
    $earthRadius = 6371;
    $latFrom = deg2rad($p1->getLat());
    $lonFrom = deg2rad($p1->getLong());
    $latTo   = deg2rad($p2->getLat());
    $lonTo   = deg2rad($p2->getLong());

    $angle = 2 * asin(sqrt(pow(sin(($latTo - $latFrom) / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin(($lonTo - $lonFrom) / 2), 2)));

    return $angle * $earthRadius;
}

// Strategy 1: Đường chim bay
class DistanceByBirdFlyCalculator implements DistanceCalculator 
{
    public function getDistance(Point $p1, Point $p2): float {
        return haversine($p1, $p2);
    }
}

// Strategy 2: Ô tô (Hệ số đường bộ = 1.3)
class DistanceByCarCalculator implements DistanceCalculator 
{
    public function getDistance(Point $p1, Point $p2): float {
        return haversine($p1, $p2) * 1.3;
    }
}

// Strategy 3: Xe đạp (Hệ số đường tắt/hẻm = 1.15)
class DistanceByBikeCalculator implements DistanceCalculator 
{
    public function getDistance(Point $p1, Point $p2): float {
        return haversine($p1, $p2) * 1.15;
    }
}

// Context
class DistanceService 
{
    public function __construct(private DistanceCalculator $calculator) {}

    public function setStrategy(DistanceCalculator $calculator): void {
        $this->calculator = $calculator;
    }

    public function calculate(Point $p1, Point $p2): float {
        return $this->calculator->getDistance($p1, $p2);
    }
}

$p1 = new Point(21.0285, 105.8542);
$p2 = new Point(10.7769, 106.6951);

$service = new DistanceService(new DistanceByCarCalculator());
echo "[PV3] O to (Routing factor 1.3): " . round($service->calculate($p1, $p2), 2) . " km\n";

$service->setStrategy(new DistanceByBikeCalculator());
echo "[PV3] Xe dap (Routing factor 1.15): " . round($service->calculate($p1, $p2), 2) . " km\n";