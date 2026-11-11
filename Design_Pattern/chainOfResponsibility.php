<?php
// FILE: phien_ban_4.php

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

function calcHaversine(Point $p1, Point $p2): float {
    $r = 6371;
    $latFrom = deg2rad($p1->getLat()); $lonFrom = deg2rad($p1->getLong());
    $latTo   = deg2rad($p2->getLat()); $lonTo   = deg2rad($p2->getLong());
    return 2 * asin(sqrt(pow(sin(($latTo - $latFrom)/2), 2) + cos($latFrom)*cos($latTo)*pow(sin(($lonTo - $lonFrom)/2), 2))) * $r;
}

// Xích 1: Ô tô (Bị quá tải nếu > 500km)
class DistanceByCarCalculator implements DistanceCalculator 
{
    public function __construct(protected readonly ?DistanceCalculator $fallback = null) {}

    public function getDistance(Point $p1, Point $p2): float {
        try {
            $dist = calcHaversine($p1, $p2) * 1.3;
            if ($dist > 500) {
                throw new Exception("Loi: Tuyen duong O to qua dai (>500km)!");
            }
            return $dist;
        } catch (\Throwable $e) {
            echo "[PV4 Warning] " . $e->getMessage() . " -> Chuyen sang du phong...\n";
            if ($this->fallback !== null) return $this->fallback->getDistance($p1, $p2);
            throw new \RuntimeException("That bai hoan toan!");
        }
    }
}

// Xích 2: Xe đạp (Bị quá tải nếu > 100km)
class DistanceByBikeCalculator implements DistanceCalculator 
{
    public function __construct(protected readonly ?DistanceCalculator $fallback = null) {}

    public function getDistance(Point $p1, Point $p2): float {
        try {
            $dist = calcHaversine($p1, $p2) * 1.15;
            if ($dist > 100) {
                throw new Exception("Loi: Tuyen duong Xe dap qua dai (>100km)!");
            }
            return $dist;
        } catch (\Throwable $e) {
            echo "[PV4 Warning] " . $e->getMessage() . " -> Chuyen sang du phong...\n";
            if ($this->fallback !== null) return $this->fallback->getDistance($p1, $p2);
            throw new \RuntimeException("That bai hoan toan!");
        }
    }
}

// Xích 3: Đường chim bay (Luôn thành công)
class DistanceByBirdFlyCalculator implements DistanceCalculator 
{
    public function __construct(protected readonly ?DistanceCalculator $fallback = null) {}

    public function getDistance(Point $p1, Point $p2): float {
        return calcHaversine($p1, $p2);
    }
}

// === CHẠY THỬ (Hà Nội -> Đà Nẵng ~ 600km) ===
$p1 = new Point(21.0285, 105.8542);
$p2 = new Point(16.0544, 108.2022); // Đà Nẵng

$chain = new DistanceByCarCalculator(
    new DistanceByBikeCalculator(
        new DistanceByBirdFlyCalculator()
    )
);

$res = $chain->getDistance($p1, $p2);
echo "[PV4 Result] Ket qua cuoi cung: " . round($res, 2) . " km\n";