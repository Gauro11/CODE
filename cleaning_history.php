<?php

// require_once 'connection.php';


// $start_date = $_GET['start_date'] ?? date('Y-m-01');
// $end_date = $_GET['end_date'] ?? date('Y-m-d');


// $query = "SELECT 
//     id,
//     full_name,
//     email,
//     phone,
//     service_type,
//     property_type,
//     address,
//     frequency,
//     preferred_day,
//     start_date,
//     end_date,
//     estimated_sessions,
//     remaining_sessions,
//     cleaners,
//     drivers,
//     status,
//     created_at
// FROM bookings
// WHERE booking_type = 'Recurring'
// AND (
//     (start_date BETWEEN ? AND ?)
//     OR (end_date BETWEEN ? AND ?)
//     OR (start_date <= ? AND end_date >= ?)
// )
// ORDER BY start_date DESC";

// $stmt = $conn->prepare($query);
// $stmt->bind_param("ssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
// $stmt->execute();
// $result = $stmt->get_result();
// $bookings = $result->fetch_all(MYSQLI_ASSOC);

// $total_bookings = count($bookings);
// $total_sessions = array_sum(array_column($bookings, 'estimated_sessions'));
// ?>

// <!DOCTYPE html>
// <html lang="en">
// <head>
//     <meta charset="UTF-8">
//     <meta name="viewport" content="width=device-width, initial-scale=1.0">
//     <title>Recurring Bookings List</title>
//     <style>
//         * {
//             margin: 0;
//             padding: 0;
//             box-sizing: border-box;
//         }
        
//         body {
//             font-family: Arial, sans-serif;
//             background: #f5f5f5;
//             padding: 20px;
//         }
        
//         .container {
//             max-width: 1400px;
//             margin: 0 auto;
//             background: white;
//             padding: 30px;
//             border-radius: 10px;
//             box-shadow: 0 2px 10px rgba(0,0,0,0.1);
//         }
        
//         .header {
//             display: flex;
//             justify-content: space-between;
//             align-items: center;
//             margin-bottom: 30px;
//             padding-bottom: 20px;
//             border-bottom: 3px solid #4CAF50;
//         }
        
//         h1 {
//             color: #333;
//             font-size: 28px;
//         }
        
//         .action-buttons {
//             display: flex;
//             gap: 10px;
//         }
        
//         .btn {
//             padding: 10px 20px;
//             border: none;
//             border-radius: 5px;
//             cursor: pointer;
//             font-size: 14px;
//             font-weight: bold;
//             text-decoration: none;
//             display: inline-block;
//         }
        
//         .btn-print {
//             background: #2196F3;
//             color: white;
//         }
        
//         .btn-print:hover {
//             background: #1976D2;
//         }
        
//         .filters {
//             background: #f9f9f9;
//             padding: 20px;
//             border-radius: 8px;
//             margin-bottom: 30px;
//         }
        
//         .filter-row {
//             display: flex;
//             gap: 15px;
//             align-items: flex-end;
//         }
        
//         .filter-group {
//             flex: 1;
//             display: flex;
//             flex-direction: column;
//         }
        
//         .filter-group label {
//             margin-bottom: 5px;
//             color: #555;
//             font-size: 14px;
//             font-weight: bold;
//         }
        
//         .filter-group input {
//             padding: 10px;
//             border: 2px solid #ddd;
//             border-radius: 5px;
//             font-size: 14px;
//         }
        
//         .btn-filter {
//             background: #4CAF50;
//             color: white;
//             padding: 10px 30px;
//             border: none;
//             border-radius: 5px;
//             cursor: pointer;
//             font-weight: bold;
//             font-size: 14px;
//         }
        
//         .btn-filter:hover {
//             background: #45a049;
//         }
        
//         .summary {
//             display: grid;
//             grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
//             gap: 20px;
//             margin-bottom: 30px;
//         }
        
//         .summary-card {
//             background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
//             color: white;
//             padding: 25px;
//             border-radius: 10px;
//             text-align: center;
//         }
        
//         .summary-card h3 {
//             font-size: 36px;
//             margin-bottom: 8px;
//         }
        
//         .summary-card p {
//             font-size: 14px;
//             opacity: 0.95;
//         }
        
//         .booking-card {
//             background: white;
//             border: 2px solid #e0e0e0;
//             border-radius: 10px;
//             padding: 25px;
//             margin-bottom: 20px;
//             transition: all 0.3s;
//         }
        
//         .booking-card:hover {
//             border-color: #4CAF50;
//             box-shadow: 0 4px 12px rgba(0,0,0,0.1);
//         }
        
//         .booking-header {
//             display: flex;
//             justify-content: space-between;
//             align-items: center;
//             margin-bottom: 20px;
//             padding-bottom: 15px;
//             border-bottom: 2px solid #f0f0f0;
//         }
        
//         .booking-id {
//             font-size: 18px;
//             font-weight: bold;
//             color: #4CAF50;
//         }
        
//         .status-badge {
//             padding: 6px 12px;
//             border-radius: 20px;
//             font-size: 12px;
//             font-weight: bold;
//         }
        
//         .status-confirmed {
//             background: #4CAF50;
//             color: white;
//         }
        
//         .status-ongoing {
//             background: #2196F3;
//             color: white;
//         }
        
//         .status-completed {
//             background: #9E9E9E;
//             color: white;
//         }
        
//         .status-pending {
//             background: #FF9800;
//             color: white;
//         }
        
//         .booking-info {
//             display: grid;
//             grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
//             gap: 15px;
//             margin-bottom: 15px;
//         }
        
//         .info-item {
//             display: flex;
//             flex-direction: column;
//         }
        
//         .info-label {
//             font-size: 12px;
//             color: #888;
//             margin-bottom: 4px;
//             text-transform: uppercase;
//             font-weight: bold;
//         }
        
//         .info-value {
//             font-size: 15px;
//             color: #333;
//             font-weight: 500;
//         }
        
//         .progress-bar {
//             margin-top: 15px;
//             padding-top: 15px;
//             border-top: 1px solid #f0f0f0;
//         }
        
//         .progress-label {
//             display: flex;
//             justify-content: space-between;
//             margin-bottom: 8px;
//             font-size: 13px;
//             color: #666;
//         }
        
//         .progress-track {
//             height: 10px;
//             background: #e0e0e0;
//             border-radius: 10px;
//             overflow: hidden;
//         }
        
//         .progress-fill {
//             height: 100%;
//             background: linear-gradient(90deg, #4CAF50, #45a049);
//             transition: width 0.3s;
//         }
        
//         .no-data {
//             text-align: center;
//             padding: 60px 20px;
//             color: #999;
//         }
        
//         .no-data h3 {
//             font-size: 24px;
//             margin-bottom: 10px;
//         }
        
//         @media print {
//             body {
//                 background: white;
//                 padding: 0;
//             }
            
//             .filters,
//             .action-buttons {
//                 display: none !important;
//             }
            
//             .container {
//                 box-shadow: none;
//                 padding: 20px;
//             }
            
//             .booking-card {
//                 page-break-inside: avoid;
//                 border: 1px solid #ddd;
//                 margin-bottom: 15px;
//             }
//         }
//     </style>
// </head>
// <body>
    
//     <div class="container">
        
//         <div class="header">

        
//             <h1>📋 Recurring Bookings List</h1>
//             <div class="action-buttons">
//                 <button onclick="window.print()" class="btn btn-print">🖨️ Print / Save PDF</button>
//             </div>
//         </div>
        
//         <!-- Filters -->
//         <div class="filters">
//             <form method="GET" action="">
//                 <div class="filter-row">
//                     <div class="filter-group">
//                         <label>Start Date:</label>
//                         <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
//                     </div>
//                     <div class="filter-group">
//                         <label>End Date:</label>
//                         <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
//                     </div>
//                     <button type="submit" class="btn-filter">🔍 Show List</button>
//                 </div>
//             </form>
//         </div>
        
       
        
      
//         <?php if ($total_bookings > 0): ?>
//             <?php foreach ($bookings as $booking): ?>
//                 <?php
//                 $completed_sessions = $booking['estimated_sessions'] - $booking['remaining_sessions'];
//                 $progress_percent = ($completed_sessions / $booking['estimated_sessions']) * 100;
//                 ?>
//                 <div class="booking-card">
//                     <div class="booking-header">
//                         <div class="booking-id">Booking #<?php echo $booking['id']; ?></div>
//                         <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
//                             <?php echo $booking['status']; ?>
//                         </span>
//                     </div>
                    
//                     <div class="booking-info">
//                         <div class="info-item">
//                             <span class="info-label">Client Name</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['full_name']); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Contact</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['phone']); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Email</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['email']); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Service Type</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['service_type']); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Property Type</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['property_type']); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Frequency</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['frequency']); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Preferred Day</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['preferred_day']); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Contract Period</span>
//                             <span class="info-value">
//                                 <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - 
//                                 <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
//                             </span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Address</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['address']); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Cleaners</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['cleaners'] ?: 'Not assigned'); ?></span>
//                         </div>
//                         <div class="info-item">
//                             <span class="info-label">Drivers</span>
//                             <span class="info-value"><?php echo htmlspecialchars($booking['drivers'] ?: 'Not assigned'); ?></span>
//                         </div>
//                     </div>
                    
//                     <div class="progress-bar">
//                         <div class="progress-label">
//                             <span><strong>Session Progress:</strong> <?php echo $completed_sessions; ?> / <?php echo $booking['estimated_sessions']; ?> completed</span>
//                             <span><strong><?php echo round($progress_percent); ?>%</strong></span>
//                         </div>
//                         <div class="progress-track">
//                             <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
//                         </div>
//                     </div>
//                 </div>
//             <?php endforeach; ?>
//         <?php else: ?>
            
//         <?php endif; ?>
//     </div>
// </body>
// </html>