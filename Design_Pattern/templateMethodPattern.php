<?php
// FILE: phien_ban_5.php

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

// Abstract Class định nghĩa Template Method và công thức toán học dùng chung
abstract class AbstractDistanceCalculator implements DistanceCalculator 
{
    public function __construct(
        protected readonly ?DistanceCalculator $fallback = null
    ) {}

    // Template Method
    public function getDistance(Point $point1, Point $point2): float 
    {
        try {
            return $this->exec($point1, $point2);
        } catch (\Throwable $exception) {
            echo "[PV5 Warning] " . get_class($this) . ": " . $exception->getMessage() . "\n";
            if ($this->fallback !== null) {
                return $this->fallback->getDistance($point1, $point2);
            }
            throw new \RuntimeException($exception->getMessage());
        }
    }

    abstract protected function exec(Point $point1, Point $point2): float;

    // Công thức Haversine dùng chung cho tất cả các lớp con
    protected function calculateHaversine(Point $p1, Point $p2): float 
    {
        $r = 6371;
        $latFrom = deg2rad($p1->getLat()); $lonFrom = deg2rad($p1->getLong());
        $latTo   = deg2rad($p2->getLat()); $lonTo   = deg2rad($p2->getLong());

        return 2 * asin(sqrt(pow(sin(($latTo - $latFrom) / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin(($lonTo - $lonFrom) / 2), 2))) * $r;
    }
}

// Triển khai gọn cho từng loại
class DistanceByCarCalculator extends AbstractDistanceCalculator 
{
    protected function exec(Point $p1, Point $p2): float 
    {
        $dist = $this->calculateHaversine($p1, $p2) * 1.3;
        if ($dist > 1000) throw new Exception("Do dai O to vuot nguong 1000km");
        return $dist;
    }
}

class DistanceByBikeCalculator extends AbstractDistanceCalculator 
{
    protected function exec(Point $p1, Point $p2): float 
    {
        $dist = $this->calculateHaversine($p1, $p2) * 1.15;
        if ($dist > 200) throw new Exception("Do dai Xe dap vuot nguong 200km");
        return $dist;
    }
}

class DistanceByBirdFlyCalculator extends AbstractDistanceCalculator 
{
    protected function exec(Point $p1, Point $p2): float 
    {
        return $this->calculateHaversine($p1, $p2);
    }
}

// === CHẠY THỬ (Hà Nội -> TP.HCM ~ 1138km) ===
$hanoi = new Point(21.0285, 105.8542);
$hcm = new Point(10.7769, 106.6951);

$calculatorChain = new DistanceByCarCalculator(
    new DistanceByBikeCalculator(
        new DistanceByBirdFlyCalculator()
    )
);

$distance = $calculatorChain->getDistance($hanoi, $hcm);
echo "[PV5 Result] Khoang cach cuoi cung: " . round($distance, 2) . " km\n";