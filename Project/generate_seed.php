<?php
// Script to generate 100 sample rows for Bangladesh Railway Management System
$sql = "-- ==========================================================\n";
$sql .= "-- Bangladesh Railway Management System - 100 Sample Records\n";
$sql .= "-- ==========================================================\n\n";

$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// 1. Generate 100 Routes
$sql .= "-- Routes\n";
$cities = ['Dhaka', 'Chittagong', 'Sylhet', 'Rajshahi', 'Khulna', 'Barisal', 'Rangpur', 'Mymensingh', 'Comilla', 'Narayanganj', 'Gazipur', 'Bogra', 'Jessore', 'Dinajpur', 'Pabna'];
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("RT%03d", $i);
    $start = $cities[array_rand($cities)];
    $end = $cities[array_rand($cities)];
    while ($start === $end) $end = $cities[array_rand($cities)];
    $dist = rand(100, 500);
    $hours = floor($dist / 60);
    $mins = ($dist % 60);
    $dur = "{$hours}h {$mins}m";
    $sql .= "INSERT IGNORE INTO Route VALUES ('$id','$start','$end',$dist,'$dur');\n";
}
$sql .= "\n";

// 2. Generate 100 Schedules
$sql .= "-- Schedules\n";
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("SC%03d", $i);
    $date = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
    $h = rand(5, 22);
    $dep = sprintf("%02d:00", $h);
    $arr = sprintf("%02d:30", min(23, $h + rand(3, 8)));
    $sql .= "INSERT IGNORE INTO Schedule VALUES ('$id','$date','$dep','$arr');\n";
}
$sql .= "\n";

// 3. Generate 100 Employees
$sql .= "-- Employees\n";
$firstNames = ['Rahim', 'Karim', 'Hasan', 'Nadia', 'Fatema', 'Rafiq', 'Sumaiya', 'Jamal', 'Kamal', 'Tania', 'Ayesha', 'Mizan', 'Shahid', 'Amin', 'Nurul'];
$lastNames = ['Uddin', 'Ahmed', 'Ali', 'Islam', 'Begum', 'Rahman', 'Khatun', 'Hossain', 'Chowdhury', 'Khan', 'Miah', 'Sikder', 'Haque', 'Talukder', 'Roy'];
$roles = ['Driver', 'Driver', 'Driver', 'Platform Staff', 'Supervisor', 'Admin', 'Ticket Checker'];
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("EMP%03d", $i);
    $fn = $firstNames[array_rand($firstNames)];
    $ln = $lastNames[array_rand($lastNames)];
    $role = $roles[array_rand($roles)];
    $sal = rand(30, 80) * 1000;
    $phone = "017" . rand(10000000, 99999999);
    $city = $cities[array_rand($cities)];
    $hd = date('Y-m-d', strtotime('-' . rand(1, 2000) . ' days'));
    $sql .= "INSERT IGNORE INTO Employee VALUES ('$id','$fn','$ln','$role',$sal,'$phone','$city','$hd');\n";
}
$sql .= "\n";

// 4. Generate 100 Trains
$sql .= "-- Trains\n";
$trainNames = ['Sundarban Express', 'Parabat Express', 'Silk City Express', 'Mohanagar Express', 'Tista Express', 'Suborno Express', 'Sonali Bank Express', 'Jamuna Express', 'Padma Express', 'Meghna Express'];
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("TR%03d", $i);
    $name = $trainNames[array_rand($trainNames)] . " " . $i;
    $type = rand(0, 1) ? 'Express' : 'Intercity';
    $route = sprintf("RT%03d", rand(1, 100));
    $sch = sprintf("SC%03d", rand(1, 100));
    $emp = sprintf("EMP%03d", rand(1, 100)); // Driver/Staff
    $status = rand(1, 100) > 10 ? 'ACTIVE' : 'MAINTENANCE';
    $sql .= "INSERT IGNORE INTO Train VALUES ('$id','$name','$type','$route','$sch','$emp',NULL,'$status');\n";
}
$sql .= "\n";

// 5. Generate 100 Coaches
$sql .= "-- Coaches\n";
$cTypes = ['AC', 'Non-AC', 'Sleeper', 'First Class', 'Second Class'];
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("CO%03d", $i);
    $train = sprintf("TR%03d", rand(1, 100));
    $type = $cTypes[array_rand($cTypes)];
    $cap = rand(40, 100);
    $sql .= "INSERT IGNORE INTO Coach VALUES ('$id','$train','$type',$cap);\n";
}
$sql .= "\n";

// 6. Generate 100 Passengers
$sql .= "-- Passengers\n";
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("PS%03d", $i);
    $fn = $firstNames[array_rand($firstNames)];
    $ln = $lastNames[array_rand($lastNames)];
    $gen = rand(0, 1) ? 'Male' : 'Female';
    $phone = "018" . rand(10000000, 99999999);
    $dob = date('Y-m-d', strtotime('-' . rand(6000, 20000) . ' days'));
    $sql .= "INSERT IGNORE INTO Passenger VALUES ('$id','$fn','$ln','$gen','$phone','$dob');\n";
}
$sql .= "\n";

// 7. Generate 100 Payments
$sql .= "-- Payments\n";
$pMethods = ['bKash', 'Nagad', 'Cash', 'Card', 'Rocket'];
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("PY%03d", $i);
    $amt = rand(3, 25) * 100;
    $meth = $pMethods[array_rand($pMethods)];
    $stat = rand(1, 100) > 5 ? 'PAID' : 'PENDING';
    $sql .= "INSERT IGNORE INTO Payment VALUES ('$id',$amt,'$meth','$stat',NOW());\n";
}
$sql .= "\n";

// 8. Generate 100 Bookings
$sql .= "-- Bookings\n";
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("BK%03d", $i);
    $ps = sprintf("PS%03d", rand(1, 100));
    $tr = sprintf("TR%03d", rand(1, 100));
    $co = sprintf("CO%03d", rand(1, 100));
    $seat = rand(1, 80);
    $py = sprintf("PY%03d", $i); // 1 to 1 payment for simplicity
    $bDate = date('Y-m-d');
    $sql .= "INSERT IGNORE INTO Booking VALUES ('$id','$ps','$tr','$co',$seat,'$py',NOW(),'$bDate');\n";
}
$sql .= "\n";

// 9. Generate 100 Technicians
$sql .= "-- Technicians\n";
$specialties = ['Electrical', 'Mechanical', 'Hydraulics', 'Engine', 'Interior'];
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("TC%03d", $i);
    $fn = $firstNames[array_rand($firstNames)];
    $ln = $lastNames[array_rand($lastNames)];
    $spec = $specialties[array_rand($specialties)];
    $exp = rand(1, 20);
    $phone = "019" . rand(10000000, 99999999);
    $sql .= "INSERT IGNORE INTO Technician VALUES ('$id','$fn','$ln','$spec',$exp,'$phone');\n";
}
$sql .= "\n";

// 10. Generate 100 Maintenances
$sql .= "-- Maintenances\n";
$mTypes = ['Scheduled', 'Emergency', 'Routine', 'Inspection'];
for ($i = 1; $i <= 100; $i++) {
    $id = sprintf("MT%03d", $i);
    $tr = sprintf("TR%03d", rand(1, 100));
    $tc = sprintf("TC%03d", rand(1, 100));
    $type = $mTypes[array_rand($mTypes)];
    $date = date('Y-m-d', strtotime('-' . rand(1, 300) . ' days'));
    $sql .= "INSERT IGNORE INTO Maintenance VALUES ('$id','$tr','$tc','$type','$date','Completed check');\n";
}
$sql .= "\n";

// 11. Drivers
$sql .= "-- Drivers\n";
for ($i = 1; $i <= 50; $i++) { // We assume 50 drivers out of 100 employees
    $id = sprintf("DRV%03d", $i);
    $emp = sprintf("EMP%03d", $i); // Ensure they match a valid employee
    $type = rand(0, 1) ? 'Express' : 'Intercity';
    $lic = date('Y-m-d', strtotime('+' . rand(300, 2000) . ' days'));
    $sql .= "INSERT IGNORE INTO Driver VALUES ('$id','$emp','$type','$lic');\n";
}
$sql .= "\n";

// 12. Platform Staff
$sql .= "-- Platform Staff\n";
for ($i = 51; $i <= 100; $i++) { // Remaining 50 employees as platform staff
    $id = sprintf("PF%03d", $i - 50);
    $emp = sprintf("EMP%03d", $i);
    $plat = "P" . rand(1, 5);
    $shift = rand(0, 1) ? 'Morning' : 'Evening';
    $sql .= "INSERT IGNORE INTO Platform_Staff VALUES ('$id','$emp','$plat','$shift');\n";
}
$sql .= "\n";

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
$sql .= "COMMIT;\n";

file_put_contents('db/seed_100.sql', $sql);
echo "db/seed_100.sql created successfully with 100 rows per table!\n";
