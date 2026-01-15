<?php
session_start(); 
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check which form was submitted
    if (isset($_POST['admin_login'])) {
        // Admin/Staff Login (with password)
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        $stmt = $conn->prepare("SELECT user_id, full_name, username, password, role FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_type'] = 'admin';
                header("Location: dash.php");
                exit();
            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "Account not found!";
        }
        $stmt->close();
        
    } elseif (isset($_POST['tenant_login'])) {
        // Tenant Login (ONLY Tenant ID - No password needed)
        $tenant_id = (int)trim($_POST['tenant_id']);
        
        if ($tenant_id > 0) {
            // Check if tenant exists and is active
            $stmt = $conn->prepare("SELECT tenant_id, full_name, email, contact_number, room_id FROM tenants WHERE tenant_id = ? AND status = 'Active'");
            $stmt->bind_param("i", $tenant_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $tenant = $result->fetch_assoc();
                
                // Set tenant session
                $_SESSION['tenant_id'] = $tenant['tenant_id'];
                $_SESSION['tenant_name'] = $tenant['full_name'];
                $_SESSION['tenant_email'] = $tenant['email'];
                $_SESSION['tenant_contact'] = $tenant['contact_number'];
                $_SESSION['tenant_room_id'] = $tenant['room_id'];
                $_SESSION['user_type'] = 'tenant';
                
                // Get room number
                $room_stmt = $conn->prepare("SELECT room_number FROM rooms WHERE room_id = ?");
                $room_stmt->bind_param("i", $tenant['room_id']);
                $room_stmt->execute();
                $room_result = $room_stmt->get_result();
                if ($room_row = $room_result->fetch_assoc()) {
                    $_SESSION['tenant_room'] = $room_row['room_number'];
                }
                $room_stmt->close();
                
                header("Location: tdash.php");
                exit();
            } else {
                $error = "Tenant ID not found or account inactive!";
            }
            $stmt->close();
        } else {
            $error = "Please enter a valid Tenant ID!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="login.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <title>Boarding House Login</title>
    <style>
      .login-type-selector {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
        gap: 10px;
      }
      .login-type-btn {
        padding: 10px 20px;
        border: none;
        background: #f0f0f0;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
        flex: 1;
        max-width: 200px;
      }
      .login-type-btn.active {
        background: #4a6cf7;
        color: white;
      }
      .login-form {
        display: block;
      }
      .login-form.hidden {
        display: none;
      }
      .tenant-note {
        background: #e8f4fd;
        border: 1px solid #b6d4fe;
        padding: 10px;
        border-radius: 5px;
        margin: 10px 0;
        font-size: 14px;
        color: #084298;
      }
      .tenant-note i {
        margin-right: 5px;
      }
      .form-group {
        margin-bottom: 15px;
      }
      .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
      }
    </style>
  </head>
  <body>
    <div class="container" id="container">
      <div class="form-container sign-up">
        <form action="signup.php" method="POST">
          <h1>Create Account</h1>
          <div class="social-icons">
            <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
            <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
            <a href="#" class="icon"><i class="fa-brands fa-linkedin"></i></a>
          </div>
          <span>or use your email for registration</span>
          <input type="text" name="name" placeholder="Name" required />
          <input type="email" name="email" placeholder="Email" required />
          <input type="password" name="password" placeholder="Password" required/>
          <button type="submit">Sign up</button>
        </form>
      </div>
      
      <div class="form-container sign-in">
        <!-- Login Type Selector -->
        <div class="login-type-selector">
          <button type="button" class="login-type-btn active" id="adminLoginBtn">Admin Login</button>
          <button type="button" class="login-type-btn" id="tenantLoginBtn">Tenant Login</button>
        </div>
        
        <!-- Admin Login Form -->
        <form id="adminLoginForm" action="login.php" method="POST" class="login-form">
          <h1>Admin Sign in</h1>
          <div class="social-icons">
            <a href="#" class="icon"><i class="fa-brands fa-google-plus-g"></i></a>
            <a href="#" class="icon"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="#" class="icon"><i class="fa-brands fa-github"></i></a>
            <a href="#" class="icon"><i class="fa-brands fa-linkedin"></i></a>
          </div>
          <span>or use your username & password</span>
          <?php if ($error && isset($_POST['admin_login'])): ?>
          <div style="color: red; margin: 10px 0; text-align: center;">
              <?php echo htmlspecialchars($error); ?>
          </div>
          <?php endif; ?>
          <input type="hidden" name="admin_login" value="1">
          <input type="text" name="username" placeholder="Username" required />
          <input type="password" name="password" placeholder="Password" required/>
          <a href="">Forget Your Password</a>
          <button type="submit">Sign In</button>
        </form>
        
        <!-- Tenant Login Form -->
        <form id="tenantLoginForm" action="login.php" method="POST" class="login-form hidden">
          <h1>Tenant Sign in</h1>
          <div class="tenant-note">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> Use your Tenant ID to login (no password required)
          </div>
          <?php if ($error && isset($_POST['tenant_login'])): ?>
          <div style="color: red; margin: 10px 0; text-align: center;">
              <?php echo htmlspecialchars($error); ?>
          </div>
          <?php endif; ?>
          <input type="hidden" name="tenant_login" value="1">
          <div class="form-group">
            <label for="tenant_id">Your Tenant ID</label>
            <input type="number" id="tenant_id" name="tenant_id" placeholder="Enter your Tenant ID" required />
          </div>
          <div class="tenant-note">
            <i class="fas fa-question-circle"></i>
            <strong>Don't know your Tenant ID?</strong> Contact the boarding house administrator.
          </div>
          <button type="submit">Enter Tenant Portal</button>
        </form>
      </div>
      
      <div class="toggle-container">
        <div class="toggle">
          <div class="toggle-panel toggle-left">
            <h1>Welcome Back!</h1>
            <p>Enter your personal details to use all of site features</p>
            <button class="hidden" id="login">Sign In</button>
          </div>
          <div class="toggle-panel toggle-right">
            <h1>Hello Friend!</h1>
            <p>Register with your personal details to use all of site features</p>
            <button class="hidden" id="register">Sign Up</button>
          </div>
        </div>
      </div>
    </div>

    <script src="login.js"></script>
    <script>
      // Toggle between Admin and Tenant login forms
      document.getElementById('adminLoginBtn').addEventListener('click', function() {
        document.getElementById('adminLoginBtn').classList.add('active');
        document.getElementById('tenantLoginBtn').classList.remove('active');
        document.getElementById('adminLoginForm').classList.remove('hidden');
        document.getElementById('tenantLoginForm').classList.add('hidden');
      });
      
      document.getElementById('tenantLoginBtn').addEventListener('click', function() {
        document.getElementById('tenantLoginBtn').classList.add('active');
        document.getElementById('adminLoginBtn').classList.remove('active');
        document.getElementById('tenantLoginForm').classList.remove('hidden');
        document.getElementById('adminLoginForm').classList.add('hidden');
      });
      
      // Focus on tenant ID field when switching to tenant login
      document.getElementById('tenantLoginBtn').addEventListener('click', function() {
        setTimeout(function() {
          document.getElementById('tenant_id').focus();
        }, 100);
      });
    </script>
  </body>
</html>