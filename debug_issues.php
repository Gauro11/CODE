<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'connection.php';

$client_email = $_SESSION['email'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Debug - Issue Report System</title>
<style>
    body {
        font-family: 'Courier New', monospace;
        background: #1e1e1e;
        color: #00ff00;
        padding: 20px;
        line-height: 1.6;
    }
    .section {
        background: #2d2d2d;
        border: 2px solid #00ff00;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .section h2 {
        color: #ffff00;
        margin-top: 0;
        border-bottom: 2px solid #00ff00;
        padding-bottom: 10px;
    }
    .success { color: #00ff00; }
    .error { color: #ff0000; }
    .warning { color: #ffaa00; }
    .info { color: #00aaff; }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    table th, table td {
        border: 1px solid #00ff00;
        padding: 8px;
        text-align: left;
    }
    table th {
        background: #003300;
        color: #ffff00;
    }
    pre {
        background: #000;
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
        color: #00ff00;
    }
    .test-form {
        background: #003300;
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
    }
    .test-form input, .test-form select, .test-form textarea {
        width: 100%;
        padding: 8px;
        margin: 5px 0;
        background: #1e1e1e;
        color: #00ff00;
        border: 1px solid #00ff00;
    }
    .test-form button {
        background: #00ff00;
        color: #000;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        font-weight: bold;
        margin-top: 10px;
    }
    .test-form button:hover {
        background: #00cc00;
    }
</style>
</head>
<body>

<h1>🔍 ISSUE REPORT SYSTEM - DEBUG MODE</h1>

<!-- ============================================ -->
<!-- SECTION 1: SESSION & CONNECTION CHECK -->
<!-- ============================================ -->
<div class="section">
    <h2>1. SESSION & DATABASE CONNECTION</h2>
    
    <p><strong>Session Email:</strong> 
        <?php 
        if ($client_email) {
            echo "<span class='success'>✅ " . htmlspecialchars($client_email) . "</span>";
        } else {
            echo "<span class='error'>❌ NOT LOGGED IN</span>";
        }
        ?>
    </p>
    
    <p><strong>Database Connection:</strong> 
        <?php 
        if ($conn->connect_error) {
            echo "<span class='error'>❌ FAILED: " . $conn->connect_error . "</span>";
        } else {
            echo "<span class='success'>✅ CONNECTED</span>";
        }
        ?>
    </p>
    
    <p><strong>Database Name:</strong> 
        <?php 
        $db_name = $conn->query("SELECT DATABASE()")->fetch_row()[0];
        echo "<span class='info'>" . htmlspecialchars($db_name) . "</span>";
        ?>
    </p>
</div>

<!-- ============================================ -->
<!-- SECTION 2: TABLE STRUCTURE VERIFICATION -->
<!-- ============================================ -->
<div class="section">
    <h2>2. DATABASE TABLE STRUCTURE - 'bookings'</h2>
    
    <?php
    $table_check = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($table_check->num_rows > 0) {
        echo "<p class='success'>✅ Table 'bookings' EXISTS</p>";
        
        // Get column info
        $columns = $conn->query("DESCRIBE bookings");
        
        echo "<h3>Issue-Related Columns:</h3>";
        echo "<table>";
        echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        
        $issue_columns = ['issue_type', 'issue_description', 'issue_report_date', 'issue_report_time', 'issue_photo1', 'issue_photo2', 'issue_photo3'];
        $found_columns = [];
        
        while ($col = $columns->fetch_assoc()) {
            if (in_array($col['Field'], $issue_columns)) {
                $found_columns[] = $col['Field'];
                echo "<tr>";
                echo "<td class='success'>" . htmlspecialchars($col['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
        // Check for missing columns
        $missing = array_diff($issue_columns, $found_columns);
        if (count($missing) > 0) {
            echo "<p class='error'>❌ MISSING COLUMNS: " . implode(', ', $missing) . "</p>";
        } else {
            echo "<p class='success'>✅ All issue columns exist</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Table 'bookings' DOES NOT EXIST!</p>";
    }
    ?>
</div>

<!-- ============================================ -->
<!-- SECTION 3: COMPLETED BOOKINGS CHECK -->
<!-- ============================================ -->
<div class="section">
    <h2>3. YOUR COMPLETED BOOKINGS</h2>
    
    <?php
    if ($client_email) {
        $sql = "SELECT id, service_type, service_date, status, issue_type, issue_description 
                FROM bookings 
                WHERE email = ? 
                AND status = 'Completed' 
                AND booking_type = 'One-Time'
                ORDER BY id DESC 
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $client_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<p class='success'>✅ Found " . $result->num_rows . " completed bookings</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Service Type</th><th>Date</th><th>Has Issue?</th><th>Issue Type</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                $has_issue = !empty($row['issue_type']);
                echo "<tr>";
                echo "<td class='info'>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['service_type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['service_date']) . "</td>";
                echo "<td>" . ($has_issue ? "<span class='warning'>YES</span>" : "<span class='info'>NO</span>") . "</td>";
                echo "<td>" . ($has_issue ? htmlspecialchars($row['issue_type']) : '-') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ No completed bookings found for your account</p>";
        }
        $stmt->close();
    } else {
        echo "<p class='error'>❌ Cannot check bookings - not logged in</p>";
    }
    ?>
</div>

<!-- ============================================ -->
<!-- SECTION 4: UPLOAD DIRECTORY CHECK -->
<!-- ============================================ -->
<div class="section">
    <h2>4. FILE UPLOAD CONFIGURATION</h2>
    
    <?php
    $upload_dir = 'uploads/issues/';
    $full_path = realpath('.') . '/' . $upload_dir;
    
    echo "<p><strong>Upload Directory:</strong> <span class='info'>" . htmlspecialchars($upload_dir) . "</span></p>";
    echo "<p><strong>Full Path:</strong> <span class='info'>" . htmlspecialchars($full_path) . "</span></p>";
    
    if (is_dir($upload_dir)) {
        echo "<p class='success'>✅ Directory EXISTS</p>";
        
        if (is_writable($upload_dir)) {
            echo "<p class='success'>✅ Directory is WRITABLE</p>";
        } else {
            echo "<p class='error'>❌ Directory is NOT WRITABLE</p>";
            echo "<p class='warning'>Fix: Run command: chmod 777 " . htmlspecialchars($upload_dir) . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Directory DOES NOT EXIST</p>";
        echo "<p class='info'>Attempting to create...</p>";
        
        if (mkdir($upload_dir, 0777, true)) {
            echo "<p class='success'>✅ Directory created successfully</p>";
        } else {
            echo "<p class='error'>❌ Failed to create directory</p>";
        }
    }
    
    echo "<p><strong>PHP Upload Settings:</strong></p>";
    echo "<pre>";
    echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
    echo "post_max_size: " . ini_get('post_max_size') . "\n";
    echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
    echo "</pre>";
    ?>
</div>

<!-- ============================================ -->
<!-- SECTION 5: TEST FORM SUBMISSION -->
<!-- ============================================ -->
<div class="section">
    <h2>5. TEST FORM SUBMISSION</h2>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])) {
        echo "<div style='background: #003300; padding: 15px; border-radius: 5px; margin-bottom: 15px;'>";
        echo "<h3 class='warning'>📥 FORM SUBMISSION RECEIVED</h3>";
        
        echo "<h4>POST Data:</h4>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        
        echo "<h4>FILES Data:</h4>";
        echo "<pre>" . print_r($_FILES, true) . "</pre>";
        
        // Attempt database update
        $test_booking_id = intval($_POST['test_booking_id']);
        $test_issue_type = $_POST['test_issue_type'];
        $test_issue_desc = $_POST['test_issue_desc'];
        $test_date = date('Y-m-d');
        $test_time = date('H:i:s');
        
        echo "<h4>Attempting Database Update...</h4>";
        
        $stmt = $conn->prepare("UPDATE bookings SET 
                issue_type = ?,
                issue_description = ?,
                issue_report_date = ?,
                issue_report_time = ?
                WHERE id = ?");
        
        if ($stmt) {
            $stmt->bind_param("ssssi", $test_issue_type, $test_issue_desc, $test_date, $test_time, $test_booking_id);
            
            if ($stmt->execute()) {
                $affected = $stmt->affected_rows;
                if ($affected > 0) {
                    echo "<p class='success'>✅ SUCCESS! Updated $affected row(s)</p>";
                } else {
                    echo "<p class='warning'>⚠️ Query executed but NO ROWS AFFECTED</p>";
                    echo "<p class='info'>Possible reasons:</p>";
                    echo "<ul>";
                    echo "<li>Booking ID $test_booking_id does not exist</li>";
                    echo "<li>No changes were made (same data already exists)</li>";
                    echo "</ul>";
                }
            } else {
                echo "<p class='error'>❌ Execute Error: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p class='error'>❌ Prepare Error: " . $conn->error . "</p>";
        }
        
        echo "</div>";
    }
    ?>
    
    <p class='info'>Use this form to test if database updates work:</p>
    
    <div class="test-form">
        <form method="POST">
            <input type="hidden" name="test_submit" value="1">
            
            <label>Booking ID (must be a real completed booking):</label>
            <input type="number" name="test_booking_id" required placeholder="Enter booking ID">
            
            <label>Issue Type:</label>
            <select name="test_issue_type" required>
                <option value="">Select...</option>
                <option value="Property Damage">Property Damage</option>
                <option value="Unsatisfied with Quality">Unsatisfied with Quality</option>
                <option value="Staff Late/No Show">Staff Late/No Show</option>
                <option value="Other">Other</option>
            </select>
            
            <label>Issue Description:</label>
            <textarea name="test_issue_desc" rows="3" required placeholder="Describe the issue..."></textarea>
            
            <button type="submit">🧪 TEST DATABASE UPDATE</button>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- SECTION 6: RECENT ISSUES FROM DATABASE -->
<!-- ============================================ -->
<div class="section">
    <h2>6. ALL REPORTED ISSUES IN DATABASE</h2>
    
    <?php
    $issues_sql = "SELECT id, service_type, service_date, issue_type, issue_description, 
                   issue_report_date, issue_report_time, issue_photo1, issue_photo2, issue_photo3
                   FROM bookings 
                   WHERE issue_type IS NOT NULL AND issue_type != ''
                   ORDER BY issue_report_date DESC, issue_report_time DESC
                   LIMIT 20";
    
    $issues_result = $conn->query($issues_sql);
    
    if ($issues_result && $issues_result->num_rows > 0) {
        echo "<p class='success'>✅ Found " . $issues_result->num_rows . " reported issues</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Service</th><th>Issue Type</th><th>Reported</th><th>Photos</th></tr>";
        
        while ($issue = $issues_result->fetch_assoc()) {
            $photo_count = 0;
            if (!empty($issue['issue_photo1'])) $photo_count++;
            if (!empty($issue['issue_photo2'])) $photo_count++;
            if (!empty($issue['issue_photo3'])) $photo_count++;
            
            echo "<tr>";
            echo "<td class='info'>" . $issue['id'] . "</td>";
            echo "<td>" . htmlspecialchars($issue['service_type']) . "</td>";
            echo "<td class='warning'>" . htmlspecialchars($issue['issue_type']) . "</td>";
            echo "<td>" . htmlspecialchars($issue['issue_report_date'] ?? 'N/A') . "</td>";
            echo "<td>" . $photo_count . " file(s)</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No issues found in database</p>";
    }
    ?>
</div>

<!-- ============================================ -->
<!-- SECTION 7: JAVASCRIPT CONSOLE TEST -->
<!-- ============================================ -->
<div class="section">
    <h2>7. JAVASCRIPT AJAX TEST</h2>
    
    <p class='info'>Open browser console (F12) and click button below:</p>
    
    <button onclick="testAjaxSubmission()" style="background: #00ff00; color: #000; padding: 10px 20px; border: none; cursor: pointer; font-weight: bold;">
        🧪 TEST AJAX SUBMISSION
    </button>
    
    <div id="ajax-result" style="margin-top: 15px; padding: 15px; background: #000; border-radius: 5px;"></div>
</div>

<script>
function testAjaxSubmission() {
    console.log("🧪 Starting AJAX test...");
    
    const formData = new FormData();
    formData.append('report-booking-id', '1'); // Change to a real booking ID
    formData.append('issueType', 'TEST - Property Damage');
    formData.append('issueDetails', 'This is a test submission from debug page at ' + new Date().toLocaleString());
    formData.append('submissionDate', new Date().toISOString().split('T')[0]);
    formData.append('submissionTime', new Date().toTimeString().split(' ')[0]);
    
    console.log("📦 Sending FormData:");
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}:`, value);
    }
    
    fetch('FR_one-time.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("📥 Response status:", response.status);
        return response.text();
    })
    .then(data => {
        console.log("📄 Response data:", data);
        document.getElementById('ajax-result').innerHTML = '<pre>' + data + '</pre>';
    })
    .catch(error => {
        console.error("❌ Error:", error);
        document.getElementById('ajax-result').innerHTML = '<span class="error">Error: ' + error + '</span>';
    });
}
</script>

</body>
</html>