<?php
session_start();
include "db.php";

// Check if tenant is logged in
if (!isset($_SESSION['tenant_id']) || $_SESSION['user_type'] !== 'tenant') {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

// Fetch tenant's personal information
$tenant_query = $conn->prepare("
    SELECT t.*, r.room_number, r.monthly_rent 
    FROM tenants t 
    LEFT JOIN rooms r ON t.room_id = r.room_id 
    WHERE t.tenant_id = ?
");
$tenant_query->bind_param("i", $tenant_id);
$tenant_query->execute();
$tenant_result = $tenant_query->get_result(); // Get result object
$tenant = $tenant_result->fetch_assoc(); // Now use fetch_assoc on result

// Count tenant's pending maintenance requests
$pending_query = $conn->prepare("SELECT COUNT(*) as count FROM maintenance_requests WHERE tenant_id = ? AND status = 'Pending'");
$pending_query->bind_param("i", $tenant_id);
$pending_query->execute();
$pending_result = $pending_query->get_result(); // Get result object
$pending_row = $pending_result->fetch_assoc(); // Now use fetch_assoc
$pending_count = $pending_row['count'];

// Count tenant's paid payments
$paid_query = $conn->prepare("SELECT COUNT(*) as count FROM payments WHERE tenant_id = ? AND (remarks LIKE '%paid%' OR remarks LIKE '%completed%')");
$paid_query->bind_param("i", $tenant_id);
$paid_query->execute();
$paid_result = $paid_query->get_result(); // Get result object
$paid_row = $paid_result->fetch_assoc(); // Now use fetch_assoc
$paid_count = $paid_row['count'];

// Get tenant's recent payments (for the table)
$recent_payments = $conn->prepare("
    SELECT * FROM payments 
    WHERE tenant_id = ? 
    ORDER BY payment_date DESC 
    LIMIT 3
");
$recent_payments->bind_param("i", $tenant_id);
$recent_payments->execute();
$payments_result = $recent_payments->get_result(); // Get result object
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tenant Dashboard</title>
  <link rel="stylesheet" href="tdash.css">
  <style>
    .paid {
        color: #10b981;
        font-weight: bold;
    }
    .pending {
        color: #f59e0b;
        font-weight: bold;
    }
    .welcome-message {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    .welcome-message h2 {
        margin: 0 0 10px 0;
    }
    .tenant-info {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .info-badge {
        background: rgba(255,255,255,0.2);
        padding: 5px 10px;
        border-radius: 5px;
        font-size: 14px;
    }
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .card h3 {
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 10px;
    }
    .card p {
        font-size: 16px;
        color: #1f2937;
        line-height: 1.5;
    }
    .table-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    th, td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    th {
        background: #f9fafb;
        font-weight: 600;
        color: #4b5563;
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">Boarding House</div>
      <ul class="menu">
        <li class="active"><a href="tdash.php">Dashboard</a></li>
        <li><a href="tmain.php">Maintenance Request</a></li>
        <li><a href="tpay.php">Payments</a></li>
        <li><a href="trom.php">Rooms</a></li>
        <li><a href="tprof.php">Profile</a></li>
        <li class="logout"><a href="logout.php">Logout</a></li>
      </ul>
    </aside>

    <!-- Main content -->
    <main class="main">
      <!-- Welcome Message -->
      <div class="welcome-message">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['tenant_name']); ?>!</h2>
        <div class="tenant-info">
          <span class="info-badge">Tenant ID: <?php echo $_SESSION['tenant_id']; ?></span>
          <?php if ($tenant && isset($tenant['room_number'])): ?>
          <span class="info-badge">Room: <?php echo htmlspecialchars($tenant['room_number']); ?></span>
          <?php endif; ?>
          <?php if ($tenant && isset($tenant['status'])): ?>
          <span class="info-badge">Status: <?php echo htmlspecialchars($tenant['status']); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="topbar">
        <h1>My Dashboard</h1>
        <div class="profile"><?php echo htmlspecialchars($_SESSION['tenant_name']); ?></div>
      </div>

      <div class="cards">
        <div class="card">
          <h3>My Information</h3>
          <?php if ($tenant): ?>
          <p style="font-size: 16px; margin-top: 10px;">
            <strong>Name:</strong> <?php echo htmlspecialchars($tenant['full_name']); ?><br>
            <strong>Room:</strong> <?php echo htmlspecialchars($tenant['room_number'] ?? 'N/A'); ?><br>
            <strong>Rent:</strong> ₱<?php echo number_format($tenant['monthly_rent'] ?? 0, 2); ?><br>
            <strong>Contact:</strong> <?php echo htmlspecialchars($tenant['contact_number'] ?? 'N/A'); ?>
          </p>
          <?php else: ?>
          <p>Information not available</p>
          <?php endif; ?>
        </div>
        <div class="card">
          <h3>Pending Requests</h3>
          <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo $pending_count; ?></p>
        </div>
        <div class="card">
          <h3>Paid Payments</h3>
          <p style="font-size: 32px; font-weight: bold; margin: 10px 0;"><?php echo $paid_count; ?></p>
        </div>
      </div>

      <div class="table-section">
        <h2>Recent Payments</h2>
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($payments_result->num_rows > 0): ?>
                <?php while ($payment = $payments_result->fetch_assoc()): ?>
                <tr>
                  <td><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                  <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                  <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                  <td class="<?php 
                      if (isset($payment['remarks']) && (stripos($payment['remarks'], 'paid') !== false || stripos($payment['remarks'], 'completed') !== false)) {
                          echo 'paid';
                      } else {
                          echo 'pending';
                      }
                  ?>">
                    <?php 
                    if (isset($payment['remarks']) && (stripos($payment['remarks'], 'paid') !== false || stripos($payment['remarks'], 'completed') !== false)) {
                        echo 'Paid';
                    } else {
                        echo 'Pending';
                    }
                    ?>
                  </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px;">No payment records found</td>
                </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>