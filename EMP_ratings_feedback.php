<?php
include 'connection.php';
session_start();

// Ensure employee is logged in
if (!isset($_SESSION['email'])) {
    echo "<script>alert('Please log in first.'); window.location.href='landing_page2.html';</script>";
    exit;
}

// Get logged-in employee's information
$employeeEmail = $_SESSION['email'];
$employeeQuery = "SELECT id, first_name, last_name, position FROM employees WHERE email = ?";
$stmt = $conn->prepare($employeeQuery);
$stmt->bind_param("s", $employeeEmail);
$stmt->execute();
$employeeResult = $stmt->get_result();
$employee = $employeeResult->fetch_assoc();

if (!$employee) {
    echo "<script>alert('Employee not found.'); window.location.href='landing_page2.html';</script>";
    exit;
}

$employeeId = $employee['id'];
$employeeName = $employee['first_name'] . ' ' . $employee['last_name'];
$employeeFirstName = $employee['first_name'];
$employeeLastName = $employee['last_name'];
$employeePosition = $employee['position'] ?? 'N/A';

// ✅ ENABLE DEBUG MODE - Set to true to see diagnostic information
$DEBUG_MODE = false; // Change to false after checking

// ==================== DEBUG SECTION ====================
if ($DEBUG_MODE) {
    echo "<div style='background: #fff3cd; padding: 20px; margin: 20px; border: 2px solid #ffc107; border-radius: 10px; font-family: monospace;'>";
    echo "<h2 style='color: #856404;'>🔍 DEBUG MODE - Staff Ratings</h2>";
    echo "<p><strong>Logged-in Employee ID:</strong> " . $employeeId . "</p>";
    echo "<p><strong>Employee Name:</strong> " . $employeeName . "</p>";
    echo "<hr style='margin: 20px 0;'>";
    
    // Check all staff_ratings entries
    $debugQuery = "SELECT * FROM staff_ratings ORDER BY created_at DESC LIMIT 20";
    $debugResult = $conn->query($debugQuery);
    
    echo "<h3>📋 All Staff Ratings (Last 20):</h3>";
    echo "<table style='width: 100%; border-collapse: collapse; background: white;'>";
    echo "<tr style='background: #856404; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Booking ID</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Employee ID</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Staff Type</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Rating</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Created At</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Match?</th>";
    echo "</tr>";
    
    $foundMatch = false;
    while ($row = $debugResult->fetch_assoc()) {
        $isMatch = ($row['employee_id'] == $employeeId);
        if ($isMatch) $foundMatch = true;
        
        $rowColor = $isMatch ? 'background: #d4edda;' : '';
        echo "<tr style='$rowColor'>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['booking_id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['employee_id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['staff_type'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'><strong>" . $row['rating'] . " ⭐</strong></td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['created_at'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>" . ($isMatch ? '✅ YES' : '❌ NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!$foundMatch) {
        echo "<p style='color: #d32f2f; font-weight: bold; margin-top: 20px;'>⚠️ WARNING: No ratings found for Employee ID: $employeeId</p>";
        echo "<p style='color: #856404;'>💡 This could mean:</p>";
        echo "<ul style='color: #856404;'>";
        echo "<li>The employee_id in staff_ratings table doesn't match your employee ID</li>";
        echo "<li>No ratings have been submitted yet for this employee</li>";
        echo "<li>The staff_ratings table is empty</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: #4caf50; font-weight: bold; margin-top: 20px;'>✅ Found matching ratings! (highlighted in green)</p>";
    }
    
    // Check bookings for name matches
    echo "<hr style='margin: 20px 0;'>";
    echo "<h3>📦 Checking Bookings for Name Matches:</h3>";
    $bookingCheckQuery = "
        SELECT id, service_date, cleaners, drivers 
        FROM bookings 
        WHERE cleaners LIKE CONCAT('%', ?, '%') OR drivers LIKE CONCAT('%', ?, '%')
        LIMIT 10
    ";
    $stmt = $conn->prepare($bookingCheckQuery);
    $stmt->bind_param("ss", $employeeName, $employeeName);
    $stmt->execute();
    $bookingCheckResult = $stmt->get_result();
    
    if ($bookingCheckResult->num_rows > 0) {
        echo "<p style='color: #4caf50;'>✅ Found " . $bookingCheckResult->num_rows . " bookings with your name:</p>";
        echo "<table style='width: 100%; border-collapse: collapse; background: white;'>";
        echo "<tr style='background: #4caf50; color: white;'>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Booking ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Service Date</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Cleaners</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Drivers</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Has Rating?</th>";
        echo "</tr>";
        
        while ($brow = $bookingCheckResult->fetch_assoc()) {
            // Check if this booking has a rating
            $ratingCheckQuery = "SELECT id FROM staff_ratings WHERE booking_id = ?";
            $rStmt = $conn->prepare($ratingCheckQuery);
            $rStmt->bind_param("i", $brow['id']);
            $rStmt->execute();
            $hasRating = $rStmt->get_result()->num_rows > 0 ? '✅ YES' : '❌ NO';
            
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $brow['id'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $brow['service_date'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . ($brow['cleaners'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . ($brow['drivers'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>" . $hasRating . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: #d32f2f;'>❌ No bookings found with your name: $employeeName</p>";
        echo "<p style='color: #856404;'>Also checked for: '$employeeFirstName' and '$employeeLastName'</p>";
    }
    
    // NEW: Cross-reference - Check which ratings exist for bookings with your name
    echo "<hr style='margin: 20px 0;'>";
    echo "<h3>🔗 Cross-Reference: Ratings for YOUR Bookings:</h3>";
    $crossRefQuery = "
        SELECT 
            b.id as booking_id,
            b.service_date,
            b.cleaners,
            b.drivers,
            sr.id as rating_id,
            sr.employee_id as rated_employee_id,
            sr.staff_type,
            sr.rating
        FROM bookings b
        LEFT JOIN staff_ratings sr ON b.id = sr.booking_id
        WHERE (
            b.cleaners LIKE CONCAT('%', ?, '%')
            OR b.cleaners LIKE CONCAT('%', ?, '%')
            OR b.cleaners LIKE CONCAT('%', ?, '%')
            OR b.drivers LIKE CONCAT('%', ?, '%')
            OR b.drivers LIKE CONCAT('%', ?, '%')
            OR b.drivers LIKE CONCAT('%', ?, '%')
        )
        ORDER BY b.service_date DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($crossRefQuery);
    $stmt->bind_param("ssssss", 
        $employeeName, $employeeFirstName, $employeeLastName,
        $employeeName, $employeeFirstName, $employeeLastName
    );
    $stmt->execute();
    $crossRefResult = $stmt->get_result();
    
    if ($crossRefResult->num_rows > 0) {
        echo "<p style='color: #4caf50;'>✅ Found bookings where you appear:</p>";
        echo "<table style='width: 100%; border-collapse: collapse; background: white;'>";
        echo "<tr style='background: #673ab7; color: white;'>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Booking ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Date</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Cleaners</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Drivers</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Rating?</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Rated Emp ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Staff Type</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Stars</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Match?</th>";
        echo "</tr>";
        
        while ($crow = $crossRefResult->fetch_assoc()) {
            $hasRating = $crow['rating_id'] ? 'YES' : 'NO';
            $ratedEmpId = $crow['rated_employee_id'] ?? 'N/A';
            $staffType = $crow['staff_type'] ?? 'N/A';
            $stars = $crow['rating'] ?? 'N/A';
            
            // Check if rating should match
            $shouldMatch = false;
            $reason = '';
            if ($crow['rating_id']) {
                // Check if employee ID matches
                if ($crow['rated_employee_id'] == $employeeId) {
                    $shouldMatch = true;
                    $reason = 'ID Match';
                }
                // Check if name appears in correct field
                $cleanersMatch = stripos($crow['cleaners'] ?? '', $employeeName) !== false ||
                                stripos($crow['cleaners'] ?? '', $employeeFirstName) !== false ||
                                stripos($crow['cleaners'] ?? '', $employeeLastName) !== false;
                $driversMatch = stripos($crow['drivers'] ?? '', $employeeName) !== false ||
                               stripos($crow['drivers'] ?? '', $employeeFirstName) !== false ||
                               stripos($crow['drivers'] ?? '', $employeeLastName) !== false;
                
                if ($crow['staff_type'] == 'cleaner' && $cleanersMatch) {
                    $shouldMatch = true;
                    $reason = 'Name in Cleaners';
                } elseif ($crow['staff_type'] == 'driver' && $driversMatch) {
                    $shouldMatch = true;
                    $reason = 'Name in Drivers';
                }
            }
            
            $rowColor = $shouldMatch ? 'background: #c8e6c9;' : ($hasRating == 'YES' ? 'background: #ffebee;' : '');
            echo "<tr style='$rowColor'>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $crow['booking_id'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $crow['service_date'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . ($crow['cleaners'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . ($crow['drivers'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd; font-weight: bold;'>" . $hasRating . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $ratedEmpId . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $staffType . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'><strong>" . $stars . ($stars != 'N/A' ? ' ⭐' : '') . "</strong></td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . ($shouldMatch ? "✅ $reason" : ($hasRating == 'YES' ? '❌ No Match' : '')) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='margin-top: 10px;'><strong>Legend:</strong></p>";
        echo "<ul>";
        echo "<li>🟢 Green = Rating should appear for you</li>";
        echo "<li>🔴 Red = Rating exists but doesn't match you</li>";
        echo "<li>⚪ White = No rating yet</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: #d32f2f;'>❌ No bookings found containing your name</p>";
    }
    
    // Check employees table
    echo "<hr style='margin: 20px 0;'>";
    echo "<h3>👥 All Employees (for reference):</h3>";
    $empDebugQuery = "SELECT id, first_name, last_name, email, position FROM employees LIMIT 10";
    $empDebugResult = $conn->query($empDebugQuery);
    
    echo "<table style='width: 100%; border-collapse: collapse; background: white;'>";
    echo "<tr style='background: #856404; color: white;'>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Name</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Email</th>";
    echo "<th style='padding: 10px; border: 1px solid #ddd;'>Position</th>";
    echo "</tr>";
    
    while ($row = $empDebugResult->fetch_assoc()) {
        $isCurrentEmp = ($row['id'] == $employeeId);
        $rowColor = $isCurrentEmp ? 'background: #d4edda;' : '';
        echo "<tr style='$rowColor'>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['id'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['email'] . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $row['position'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "</div>";
}

// Fetch all ratings for this employee (by ID OR by name match in bookings)
$ratingsQuery = "
    SELECT DISTINCT
        sr.id,
        sr.booking_id,
        sr.staff_type,
        sr.rating,
        sr.created_at,
        b.service_date,
        b.service_time,
        b.service_type,
        b.booking_type,
        b.full_name,
        b.address,
        b.status as status,
        b.cleaners,
        b.drivers
    FROM staff_ratings sr
    INNER JOIN bookings b ON sr.booking_id = b.id
    WHERE (
        sr.employee_id = ?
        OR (
            sr.staff_type = 'cleaner' AND (
                b.cleaners LIKE CONCAT('%', ?, '%')
                OR b.cleaners LIKE CONCAT('%', ?, '%')
                OR b.cleaners LIKE CONCAT('%', ?, '%')
            )
        )
        OR (
            sr.staff_type = 'driver' AND (
                b.drivers LIKE CONCAT('%', ?, '%')
                OR b.drivers LIKE CONCAT('%', ?, '%')
                OR b.drivers LIKE CONCAT('%', ?, '%')
            )
        )
    )
    ORDER BY sr.created_at DESC
";

$stmt = $conn->prepare($ratingsQuery);
$stmt->bind_param("issssss", 
    $employeeId, 
    $employeeName, $employeeFirstName, $employeeLastName,
    $employeeName, $employeeFirstName, $employeeLastName
);
$stmt->execute();
$ratingsResult = $stmt->get_result();

// DEBUG: Show query results
if ($DEBUG_MODE) {
    echo "<div style='background: #e3f2fd; padding: 20px; margin: 20px; border: 2px solid #2196f3; border-radius: 10px;'>";
    echo "<h3 style='color: #1565c0;'>🔎 Query Results Debug:</h3>";
    echo "<p><strong>Number of rows returned:</strong> " . $ratingsResult->num_rows . "</p>";
    
    if ($ratingsResult->num_rows > 0) {
        echo "<p style='color: #4caf50; font-weight: bold;'>✅ Found ratings for this employee!</p>";
        echo "<table style='width: 100%; border-collapse: collapse; background: white; margin-top: 10px;'>";
        echo "<tr style='background: #2196f3; color: white;'>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Rating ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Booking ID</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Stars</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Staff Type</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Client</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Date</th>";
        echo "</tr>";
        
        // Store current position
        $tempResults = [];
        while ($tempRow = $ratingsResult->fetch_assoc()) {
            $tempResults[] = $tempRow;
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $tempRow['id'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $tempRow['booking_id'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'><strong>" . $tempRow['rating'] . " ⭐</strong></td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $tempRow['staff_type'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . ($tempRow['full_name'] ?? 'N/A') . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $tempRow['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Restore data for processing
        $ratingsResult = $tempResults;
    } else {
        echo "<p style='color: #d32f2f; font-weight: bold;'>❌ No ratings returned by query</p>";
        echo "<p style='color: #856404;'>This means either:</p>";
        echo "<ul style='color: #856404;'>";
        echo "<li>Employee ID $employeeId has no direct ratings in staff_ratings</li>";
        echo "<li>Employee name '$employeeName' doesn't appear in any booking's cleaners/drivers fields</li>";
        echo "<li>Or both conditions are not met</li>";
        echo "</ul>";
    }
    
    echo "<p><strong>Query used:</strong></p>";
    echo "<pre style='background: white; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 0.85em;'>";
    echo htmlspecialchars($ratingsQuery);
    echo "</pre>";
    echo "<p><strong>Parameters:</strong></p>";
    echo "<ul>";
    echo "<li>Employee ID: $employeeId</li>";
    echo "<li>Employee Full Name: $employeeName</li>";
    echo "<li>Employee First Name: $employeeFirstName</li>";
    echo "<li>Employee Last Name: $employeeLastName</li>";
    echo "<li>Employee Position: $employeePosition</li>";
    echo "</ul>";
    
    echo "<p style='margin-top: 15px;'><strong>🔍 What the query is looking for:</strong></p>";
    echo "<ol style='color: #1565c0;'>";
    echo "<li>Direct match: staff_ratings.employee_id = $employeeId</li>";
    echo "<li>OR if staff_type='cleaner': bookings.cleaners contains '$employeeName' OR '$employeeFirstName' OR '$employeeLastName'</li>";
    echo "<li>OR if staff_type='driver': bookings.drivers contains '$employeeName' OR '$employeeFirstName' OR '$employeeLastName'</li>";
    echo "</ol>";
    echo "</div>";
}

// Calculate statistics
$totalRatings = 0;
$sumRatings = 0;
$ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

$ratingsData = [];
if (is_array($ratingsResult)) {
    // If we used the debug workaround
    $ratingsData = $ratingsResult;
    foreach ($ratingsData as $row) {
        $totalRatings++;
        $sumRatings += $row['rating'];
        $ratingCounts[$row['rating']]++;
    }
} else {
    // Normal flow
    while ($row = $ratingsResult->fetch_assoc()) {
        $ratingsData[] = $row;
        $totalRatings++;
        $sumRatings += $row['rating'];
        $ratingCounts[$row['rating']]++;
    }
}

$averageRating = $totalRatings > 0 ? round($sumRatings / $totalRatings, 1) : 0;

// Helper function to format reference number
function formatRefNo($id, $serviceType, $date) {
    $serviceCode = '';
    if (strpos(strtolower($serviceType), 'deep') !== false) $serviceCode = 'DC';
    elseif (strpos(strtolower($serviceType), 'general') !== false) $serviceCode = 'GC';
    elseif (strpos(strtolower($serviceType), 'move') !== false) $serviceCode = 'MC';
    else $serviceCode = 'OT';
    
    $yearMonth = date('ym', strtotime($date));
    return "ALZ-{$serviceCode}-{$yearMonth}-" . str_pad($id, 4, '0', STR_PAD_LEFT);
}

// Helper function to render stars
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="bx bxs-star"></i>';
        } else {
            $stars .= '<i class="bx bx-star"></i>';
        }
    }
    return $stars;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ALAZIMA - My Ratings & Feedback</title>
<link rel="icon" href="site_icon.png" type="image/png">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="client_db.css">
<style>
.ratings-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.ratings-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
    font-weight: bold;
}

.stat-card p {
    margin: 0;
    font-size: 0.9em;
    opacity: 0.9;
}

.rating-breakdown {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.breakdown-row {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.breakdown-stars {
    display: flex;
    gap: 3px;
    color: #FFD700;
    font-size: 1.2em;
    width: 120px;
}

.breakdown-bar {
    flex: 1;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.breakdown-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

.breakdown-count {
    min-width: 40px;
    text-align: right;
    font-weight: bold;
    color: #666;
}

.ratings-list {
    display: grid;
    gap: 20px;
}

.rating-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.rating-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.rating-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.rating-stars {
    display: flex;
    gap: 5px;
    font-size: 1.5em;
    color: #FFD700;
}

.rating-stars .bx-star {
    color: #ddd;
}

.rating-date {
    color: #999;
    font-size: 0.9em;
}

.booking-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #555;
}

.info-item i {
    color: #667eea;
    font-size: 1.2em;
}

.info-item strong {
    color: #333;
}

.staff-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: bold;
    text-transform: uppercase;
}

.staff-badge.cleaner {
    background: #e3f2fd;
    color: #1976d2;
}

.staff-badge.driver {
    background: #f3e5f5;
    color: #7b1fa2;
}

.ref-number {
    color: #B32133;
    font-weight: bold;
    font-size: 1.1em;
}

.no-ratings {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.no-ratings i {
    font-size: 5em;
    color: #ddd;
    margin-bottom: 20px;
}

.no-ratings h3 {
    color: #666;
    margin-bottom: 10px;
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.filter-group {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group label {
    font-weight: bold;
    color: #333;
}

.filter-group select {
    padding: 8px 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 1em;
    cursor: pointer;
    transition: border-color 0.3s;
}

.filter-group select:focus {
    outline: none;
    border-color: #667eea;
}
</style>
</head>
<body>
<header class="header" id="header">
<nav class="nav container">
<a href="employee_dashboard.php" class="nav__logo">
<img src="LOGO.png" alt="ALAZIMA Cleaning Services LLC Logo" onerror="this.onerror=null;this.src='https://placehold.co/200x50/FFFFFF/004a80?text=ALAZIMA';">
</a>
<button class="nav__toggle" id="nav-toggle" aria-label="Toggle navigation menu">
<i class='bx bx-menu'></i>
</button>
</nav>
</header>

<div class="dashboard__wrapper">
<aside class="dashboard__sidebar">
<ul class="sidebar__menu">
<li class="menu__item">
<a href="employee_dashboard.php" class="menu__link">
<i class='bx bx-home-alt-2'></i> Dashboard
</a>
</li>
<li class="menu__item has-dropdown">
    <a href="#" class="menu__link">
        <i class='bx bx-calendar-check'></i> My Appointments <i class='bx bx-chevron-down arrow-icon'></i>
    </a>
    <ul class="dropdown__menu">
        <li class="menu__item">
            <a href="EMP_appointments_today.php" class="menu__link">Today's Appointments</a>
        </li>
        <li class="menu__item">
            <a href="EMP_appointments_history.php" class="menu__link">History</a>
        </li>
    </ul>
</li>
<li class="menu__item">
<a href="EMP_ratings_feedback.php" class="menu__link active">
<i class='bx bx-star'></i> Ratings/Feedback
</a>
</li>
<!-- <li class="menu__item">
<a href="employee_profile.php" class="menu__link">
<i class='bx bx-user'></i> My Profile -->
</a>
</li>
<li class="menu__item">
<a href="landing_page2.html" class="menu__link">
<i class='bx bx-log-out'></i> Logout
</a>
</li>
</ul>
</aside>

<main class="dashboard__content">
<div class="ratings-header">
<h1><i class='bx bx-star'></i> My Ratings & Feedback</h1>
<div class="ratings-stats">
<div class="stat-card">
<h3><?php echo $averageRating; ?></h3>
<p>Average Rating</p>
</div>
<div class="stat-card">
<h3><?php echo $totalRatings; ?></h3>
<p>Total Ratings</p>
</div>
<div class="stat-card">
<h3><?php echo $totalRatings > 0 ? number_format(($ratingCounts[5] / $totalRatings) * 100, 1) : 0; ?>%</h3>
<p>5-Star Ratings</p>
</div>
</div>
</div>

<?php if ($totalRatings > 0): ?>
<div class="rating-breakdown">
<h3 style="margin-bottom: 20px; color: #333;"><i class='bx bx-bar-chart-alt-2'></i> Rating Distribution</h3>
<?php for ($star = 5; $star >= 1; $star--): ?>
<div class="breakdown-row">
<div class="breakdown-stars">
<?php echo renderStars($star); ?>
</div>
<div class="breakdown-bar">
<div class="breakdown-bar-fill" style="width: <?php echo $totalRatings > 0 ? ($ratingCounts[$star] / $totalRatings) * 100 : 0; ?>%"></div>
</div>
<div class="breakdown-count"><?php echo $ratingCounts[$star]; ?></div>
</div>
<?php endfor; ?>
</div>

<div class="filter-section">
<div class="filter-group">
<label><i class='bx bx-filter'></i> Filter by:</label>
<select id="ratingFilter">
<option value="all">All Ratings</option>
<option value="5">5 Stars</option>
<option value="4">4 Stars</option>
<option value="3">3 Stars</option>
<option value="2">2 Stars</option>
<option value="1">1 Star</option>
</select>

</div>
</div>

<div class="ratings-list">
<?php foreach ($ratingsData as $rating): 
    $refNo = formatRefNo($rating['booking_id'], $rating['service_type'], $rating['service_date']);
?>
<div class="rating-card" data-rating="<?php echo $rating['rating']; ?>" data-type="<?php echo $rating['staff_type']; ?>">
<div class="rating-card-header">
<div>
<div class="rating-stars"><?php echo renderStars($rating['rating']); ?></div>
<span class="staff-badge <?php echo $rating['staff_type']; ?>"><?php echo ucfirst($rating['staff_type']); ?></span>
</div>
<div class="rating-date">
<i class='bx bx-time'></i> <?php echo date('M d, Y', strtotime($rating['created_at'])); ?>
</div>
</div>

<div class="booking-info">
<div class="info-item">
<i class='bx bx-hash'></i>
<span><strong>Ref:</strong> <span class="ref-number"><?php echo $refNo; ?></span></span>
</div>
<div class="info-item">
<i class='bx bx-calendar'></i>
<span><strong>Service Date:</strong> <?php echo date('M d, Y', strtotime($rating['service_date'])); ?></span>
</div>
<div class="info-item">
<i class='bx bx-time'></i>
<span><strong>Time:</strong> <?php echo date('g:i A', strtotime($rating['service_time'])); ?></span>
</div>
<div class="info-item">
<i class='bx bx-wrench'></i>
<span><strong>Service:</strong> <?php echo htmlspecialchars($rating['service_type']); ?></span>
</div>
<div class="info-item">
<i class='bx bx-user'></i>
<span><strong>Client:</strong> <?php echo htmlspecialchars($rating['full_name']); ?></span>
</div>
<div class="info-item">
<i class='bx bx-map'></i>
<span><strong>Location:</strong> <?php echo htmlspecialchars($rating['address']); ?></span>
</div>
</div>
</div>
<?php endforeach; ?>
</div>


<?php else: ?>
<div class="no-ratings">
<i class='bx bx-star'></i>
<h3>No Ratings Yet</h3>
<p>You haven't received any ratings yet. Complete more jobs to receive feedback from clients!</p>
</div>
<?php endif; ?>

</main>
</div>

<script>
// Filter functionality
document.getElementById('ratingFilter').addEventListener('change', filterRatings);
document.getElementById('typeFilter').addEventListener('change', filterRatings);

function filterRatings() {
    const ratingFilter = document.getElementById('ratingFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const cards = document.querySelectorAll('.rating-card');
    
    cards.forEach(card => {
        const cardRating = card.getAttribute('data-rating');
        const cardType = card.getAttribute('data-type');
        
        let showCard = true;
        
        if (ratingFilter !== 'all' && cardRating !== ratingFilter) {
            showCard = false;
        }
        
        if (typeFilter !== 'all' && cardType !== typeFilter) {
            showCard = false;
        }
        
        card.style.display = showCard ? 'block' : 'none';
    });
}

// Mobile menu toggle
const navToggle = document.getElementById('nav-toggle');
const sidebar = document.querySelector('.dashboard__sidebar');

if (navToggle) {
    navToggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
    });
}
</script>
</body>
</html>