function calculateBookingAmount($checkIn, $checkOut, $rate) {
    $startDate = new DateTime($checkIn);
    $endDate = new DateTime($checkOut);
    $nights = $endDate->diff($startDate)->days;
    return $nights * $rate;
}