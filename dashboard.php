<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username = "root";
$dbpassword = "";
$dbname = "redstream_db";
$conn = new mysqli($servername, $username, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_SESSION["email"];

$stmt = $conn->prepare("SELECT name, email, phone, bloodgroup, gender, birthdate, `weight(kg)`, state, zipcode, district, area, landmark, password, donations, received FROM registered_users WHERE email = ?");
if (!$stmt) {
    die("Error: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $bloodgroup, $gender, $birthdate, $weight, $state, $zipcode, $district, $area, $landmark, $password, $donations, $received);
$stmt->fetch();
$stmt->close();

// Calculate the number of donations from the "donations" table
$donations_count = 0;
if ($conn->query("SELECT COUNT(*) as total FROM donations WHERE user_email = '$email'")) {
    $result = $conn->query("SELECT COUNT(*) as total FROM donations WHERE user_email = '$email'");
    $data = $result->fetch_assoc();
    $donations_count = $data['total'];
}

// Update user information in the database
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["change_password"])) {
        // Password change form is submitted
        $current_password = $_POST["current_password"];
        $new_password = $_POST["new_password"];
        $confirm_new_password = $_POST["confirm_new_password"];

        // Validate the current password (You might want to check if it matches the one in the database)
        if (!password_verify($current_password, $password)) {
            // Incorrect current password
            echo "<script>alert('Old password is incorrect. Please try again.');</script>";
        } elseif (strlen($new_password) < 6) {
            // Validate the new password (You can add more validation rules if needed)
            echo "<script>alert('Password must be at least 6 characters long.');</script>";
        } elseif ($new_password !== $confirm_new_password) {
            // New password and Confirm new password do not match
            echo "<script>alert('New password and Confirm new password do not match.');</script>";
        } else {
            // Hash the new password before updating in the database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $stmt = $conn->prepare("UPDATE registered_users SET password=? WHERE email=?");
            if (!$stmt) {
                die("Error: " . $conn->error);
            }
            $stmt->bind_param("ss", $hashed_password, $email);
            $stmt->execute();
            $stmt->close();

            // Show success message
            echo "<script>alert('Password updated successfully.');</script>";
        }
    } else {
        // Other credentials form is submitted
        $name = $_POST["name"];
        $phone = $_POST["phone"];
        $bloodgroup = $_POST["bloodgroup"];
        $gender = $_POST["gender"];
        $birthdate = $_POST["birthdate"];
        $weight = $_POST["weight"];
        $state = $_POST["state"];
        $zipcode = $_POST["zipcode"];
        $district = $_POST["district"];
        $area = $_POST["area"];
        $landmark = $_POST["landmark"];

        $stmt = $conn->prepare("UPDATE registered_users SET name=?, phone=?, bloodgroup=?, gender=?, birthdate=?, `weight(kg)`=?, state=?, zipcode=?, district=?, area=?, landmark=? WHERE email=?");
        if (!$stmt) {
            die("Error: " . $conn->error);
        }
        $stmt->bind_param("sssssdssssss", $name, $phone, $bloodgroup, $gender, $birthdate, $weight, $state, $zipcode, $district, $area, $landmark, $email);
        $stmt->execute();
        $stmt->close();

        // Show success message
        echo "<script>alert('Information updated successfully.');</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Stream - Dashboard</title>

    <!-- favicon-->
    <link rel="shortcut icon" href="./favicon.svg" type="image/svg+xml">

    <!-- CSS Styles -->
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
    /* New CSS for Dashboard Section */
    hr {
      border: none; /* Remove the default border */
      height: 1px; /* Set the height to control the thickness */
      background-color: #c5c7c9; /* Set the desired color */
      margin: 20px 0; /* Add some margin to separate form sections */
    }
    .dashboard-section {
      padding: 60px 0;
    }

    .dashboard-container {
      display: flex;
      justify-content: space-between;
      max-width: 1200px;
      margin: 0 auto;
    }

    .dashboard-form-container {
      flex-basis: 65%;
      margin-top: 6%;
    }

    .dashboard-form {
      background-color: #f4f4f4;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 30px;
    }

    .dashboard-fields {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      grid-gap: 20px;
    }

    .dashboard-field {
      margin-bottom: 20px;
    }

    .donation-password-section {
      flex-basis: 30%;
      margin-top: 6%;
    }

    .donation-received-box {
      background-color: #f4f4f4;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .donation-received-box label {
      font-weight: 600;
    }

    .count {
      font-size: 24px;
      font-weight: 700;
      color: #6c63ff;
    }

    .password-change {
      background-color: #f4f4f4;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .password-change h2 {
      font-size: 20px;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .save-changes-btn,
    .logout-btn {
      display: block;
      width: 100%;
      max-width: 200px;
      margin: 0 auto;
      text-align: center;
      background-color: #6c63ff;
      color: #fff;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }

    .save-changes-btn:hover,
    .logout-btn:hover {
      background-color: #524dff;
    }

        /*css for the date and weight fields of form*/
    .dashboard-field input[type="number"],
  .dashboard-field input[type="date"] {
    border: 1px solid #ccc;
    padding: 10px;
    border-radius: 4px;
    transition: border-color 0.3s ease;
  }

  .dashboard-field input[type="number"]:focus,
  .dashboard-field input[type="date"]:focus {
    border-color: #6c63ff;
  }

  /* CSS for the password change fields */
  .password-change input[type="password"] {
    border: 1px solid #ccc;
    padding: 10px;
    border-radius: 4px;
    transition: border-color 0.3s ease;
  }

  .password-change input[type="password"]:focus {
    border-color: #6c63ff;
  }
  </style>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800&family=Roboto:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>
<header class="header">
    <div class="header-top">
      <div class="container">
        <ul class="contact-list">
          <li class="contact-item">
            <ion-icon name="mail-outline"></ion-icon>
            <a href="mailto:redstream001@gmail.com" class="contact-link">redstream001@gmail.com</a>
          </li>
          <li class="contact-item">
            <ion-icon name="call-outline"></ion-icon>
            <a href="tel:+917558951351" class="contact-link">+91-7558-951-351</a>
          </li>
        </ul>
        <ul class="social-list">
          <li>
            <a href="https://www.facebook.com/andro.pool.54?mibextid=ZbWKwL" class="social-link">
              <ion-icon name="logo-facebook"></ion-icon>
            </a>
          </li>
          <li>
            <a href="https://www.instagram.com/_vladimir_putin.___/" class="social-link">
              <ion-icon name="logo-instagram"></ion-icon>
            </a>
          </li>
          <li>
            <a href="https://twitter.com/Annabel07785340" class="social-link">
              <ion-icon name="logo-twitter"></ion-icon>
            </a>
          </li>
          <li>
            <a href="https://youtu.be/Af0gk_kiGac" class="social-link">
              <ion-icon name="logo-youtube"></ion-icon>
            </a>
          </li>
        </ul>
      </div>
    </div>
    <div class="header-bottom" data-header>
      <div class="container">
        <a href="#" class="logo">Red Stream</a>
        <nav class="navbar container" data-navbar>
          <ul class="navbar-list">
            <li>
              <a href="index.php" class="navbar-link" data-nav-link>Home</a>
            </li>
            <li>
              <a href="#service" class="navbar-link" data-nav-link>Find donor</a>
            </li>
            <li>
              <a href="about.html" class="navbar-link" data-nav-link>About Us</a>
            </li>
            <li>
              <a href="#blog" class="navbar-link" data-nav-link>Blog</a>
            </li>
            <li>
              <a href="contact.php" class="navbar-link" data-nav-link>Contact</a>
            </li>
          </ul>
        </nav>
        <a href="register.php" class="btn">Login / Register</a>
        <button class="nav-toggle-btn" aria-label="Toggle menu" data-nav-toggler>
          <ion-icon name="menu-sharp" aria-hidden="true" class="menu-icon"></ion-icon>
          <ion-icon name="close-sharp" aria-hidden="true" class="close-icon"></ion-icon>
        </button>
      </div>
    </div>
  </header>

  <section class="section dashboard-section" id="dashboard">
    <div class="container">
      <div class="dashboard-container">
        <!-- User Information Form Section -->
        <div class="dashboard-form-container">
          <form class="dashboard-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="dashboard-title">Dashboard  |  Welcome, <?php echo $name; ?> ! </div>
            <hr>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="dashboard-fields">
                    <div class="dashboard-field">
                        <label>Name:</label>
                        <input type="text" name="name" value="<?php echo $name; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>Email:</label>
                        <input type="text" name="email" value="<?php echo $email; ?>" disabled>
                    </div>
                    <div class="dashboard-field">
                        <label>Phone:</label>
                        <input type="text" name="phone" value="<?php echo $phone; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>Blood Group:</label>
                        <input type="text" name="bloodgroup" value="<?php echo $bloodgroup; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>Gender:</label>
                        <input type="text" name="gender" value="<?php echo $gender; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>Birthdate:</label>
                        <input type="date" name="birthdate" value="<?php echo $birthdate; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>Weight (kg):</label>
                        <input type="number" name="weight" value="<?php echo $weight; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>State:</label>
                        <input type="text" name="state" value="<?php echo $state; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>Zipcode:</label>
                        <input type="text" name="zipcode" value="<?php echo $zipcode; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>District:</label>
                        <input type="text" name="district" value="<?php echo $district; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>Area:</label>
                        <input type="text" name="area" value="<?php echo $area; ?>">
                    </div>
                    <div class="dashboard-field">
                        <label>Landmark:</label>
                        <input type="text" name="landmark" value="<?php echo $landmark; ?>">
                    </div>
                </div>

                <button type="submit" class="btn save-changes-btn">Update your details</button>
          </form>
        </div>

        <!-- Donation/Received and Password Change Section -->
        <div class="donation-password-section">
          <!-- Donation and Received Counts Box -->
          <div class="donation-received-box">
            <div class="donation-count">
              <label>Donations:</label>
              <div class="count"><?php echo $donations; ?></div>
            </div>
            <div class="received-count">
              <label>Received:</label>
              <div class="count"><?php echo $received; ?></div>
            </div>
          </div>

          <!-- Password Change Section -->
<section class="password-change">
    <h2>Password Change</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="dashboard-field">
            <label>Current Password:</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="dashboard-field">
            <label>New Password:</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="dashboard-field">
            <label>Confirm New Password:</label>
            <input type="password" name="confirm_new_password" required>
        </div>
        <button type="submit" name="change_password" class="btn">Change Password</button>
    </form>
</section>

          <br>
          <a href="logout.php" class="btn logout-btn">Logout</a>
        </div>
      </div>
    </div>
  </section>


<!--FOOTER-->
<footer class="footer">
    <div class="footer-top section">
      <div class="container">
        <div class="footer-brand">
          <a href="#" class="logo">Red Stream</a>
          <p class="footer-text">
            We are passionate about connecting blood donors with recipients and bridging the gap in the healthcare industry. 
            We strive to create a community that fosters empathy, support, and solidarity among individuals who are committed to making a difference.
          </p>
          <div class="schedule">
            <div class="schedule-icon">
              <ion-icon name="time-outline"></ion-icon>
            </div>
            <span class="span">
              24 X 7:<br>
              365 Days
            </span>
          </div>
        </div>
        <ul class="footer-list">
          <li>
            <p class="footer-list-title">Other Links</p>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">Home</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">Find donor</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">About us</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">Blog</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">Contact</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">Login / Register</span>
            </a>
          </li>
        </ul>
        <ul class="footer-list">
          <li>
            <p class="footer-list-title">Our Services</p>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">xxxxxxxxx</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">xxxxxxxxx</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">xxxxxxxxx</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">xxxxxxxxx</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">xxxxxxxxx</span>
            </a>
          </li>
          <li>
            <a href="#" class="footer-link">
              <ion-icon name="add-outline"></ion-icon>
              <span class="span">xxxxxxxxx</span>
            </a>
          </li>
        </ul>
        <ul class="footer-list">
          <li>
            <p class="footer-list-title">Contact Us</p>
          </li>
          <li class="footer-item">
            <div class="item-icon">
              <ion-icon name="location-outline"></ion-icon>
            </div>
            <a href="https://goo.gl/maps/BYA5MxQUg5B8ZFLcA">
            <address class="item-text">
              Near Thaluk Headquarters,<br>
              Vaikom, Kottayam, Kerala IN
            </address>
          </a>
          </li>
          <li class="footer-item">
            <div class="item-icon">
              <ion-icon name="call-outline"></ion-icon>
            </div>
            <a href="tel:+917052101786" class="footer-link">+91-7558-951-351</a>
          </li>
          <li class="footer-item">
            <div class="item-icon">
              <ion-icon name="mail-outline"></ion-icon>
            </div>
            <a href="mailto:help@example.com" class="footer-link">redstream001@gmail.com</a>
          </li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="container">
        <p class="copyright">
          &copy; 2024 All Rights Reserved by Red Stream
        </p>
        <ul class="social-list">
          <li>
            <a href="https://www.facebook.com/andro.pool.54?mibextid=ZbWKwL" class="social-link">
              <ion-icon name="logo-facebook"></ion-icon>
            </a>
          </li>
          <li>
            <a href="https://www.instagram.com/_vladimir_putin.___/" class="social-link">
              <ion-icon name="logo-instagram"></ion-icon>
            </a>
          </li>
          <li>
            <a href="https://twitter.com/Annabel07785340" class="social-link">
              <ion-icon name="logo-twitter"></ion-icon>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </footer>

  <!--BACK TO TOP-->
  <a href="#top" class="back-top-btn" aria-label="back to top" data-back-top-btn>
    <ion-icon name="caret-up" aria-hidden="true"></ion-icon>
  </a>

  <!--custom js link-->
  <script src="./assets/js/script.js" defer></script>
  <!--ionicon link-->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

</body>
</html>