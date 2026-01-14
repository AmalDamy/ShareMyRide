<?php
require_once 'config.php';
require_once 'db_connect.php';

// Create uploads directory if it doesn't exist
$target_dir = "uploads/profile_pics/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $message = "Name cannot be empty.";
        $messageType = "error";
    } else {
        $profile_pic_path = null;

        // Handle File Upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['profile_pic']['tmp_name'];
            $file_name = basename($_FILES['profile_pic']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = "user_" . $user_id . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    $profile_pic_path = $target_file;
                } else {
                    $message = "Error uploading file.";
                    $messageType = "error";
                }
            } else {
                $message = "Invalid file type. Only JPG, PNG, and GIF allowed.";
                $messageType = "error";
            }
        }

        // Update Database
        if ($messageType !== "error") {
            if ($profile_pic_path) {
                $stmt = $conn->prepare("UPDATE users SET name = ?, profile_pic = ? WHERE user_id = ?");
                $stmt->bind_param("ssi", $name, $profile_pic_path, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ? WHERE user_id = ?");
                $stmt->bind_param("si", $name, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['username'] = $name; // Update Session Name
                // if ($profile_pic_path) { $_SESSION['profile_pic'] = $profile_pic_path; } // Optional: Update Session Pic if you store it there

                // Redirect to Dashboard on Success
                header("Location: dashboard.php?msg=profile_updated");
                exit;
            } else {
                $message = "Error updating profile.";
                $messageType = "error";
            }
        }
    }
}

// Fetch Current Data
$stmt = $conn->prepare("SELECT name, email, profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Use default if nothing in DB
$current_pic = !empty($user['profile_pic']) && file_exists($user['profile_pic']) 
               ? $user['profile_pic'] 
               : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'; // Fallback URL or local default
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-upload-wrapper {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            cursor: pointer;
        }
        .profile-img-preview {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: opacity 0.3s;
        }
        .upload-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
            color: #fff;
            font-size: 2rem;
            pointer-events: none;
        }
        .profile-upload-wrapper:hover .profile-img-preview {
            opacity: 0.5;
            filter: brightness(0.7);
        }
        .profile-upload-wrapper:hover .upload-icon {
            display: block;
        }
    </style>
</head>
<body style="background-color: #f3f4f6;">

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo">ShareMyRide</a>
            <div class="nav-links" id="navLinks">
                <a href="dashboard.php" style="color: var(--primary-teal); font-weight: 700;">Dashboard</a>
                <a href="find_ride.php">Find Ride</a>
                <a href="offer_ride.php">Offer Ride</a>
                <a href="long_trip.php">Long Trip</a>
                <a href="logout.php" style="color: var(--error-red); font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
            <button class="mobile-menu-toggle" onclick="document.getElementById('navLinks').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <div class="container" style="padding: 4rem 0; max-width: 600px;">
        
        <div class="search-card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                <h1 style="margin: 0; font-size: 1.8rem; color: var(--text-dark);">Edit Profile</h1>
                <a href="dashboard.php" style="color: var(--text-gray);"><i class="fas fa-times"></i> Cancel</a>
            </div>

            <?php if ($message): ?>
                <div class="<?php echo $messageType === 'success' ? 'success-message' : 'error-message'; ?>" 
                     style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; 
                            background: <?php echo $messageType === 'success' ? '#d1fae5' : '#fee2e2'; ?>; 
                            color: <?php echo $messageType === 'success' ? '#065f46' : '#991b1b'; ?>;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                
                <!-- Profile Image Upload -->
                <div class="profile-upload-wrapper" onclick="document.getElementById('profilePicInput').click()">
                    <img src="<?php echo htmlspecialchars($current_pic); ?>" class="profile-img-preview" id="previewImg">
                    <i class="fas fa-camera upload-icon"></i>
                </div>
                <div style="text-align: center; margin-bottom: 2rem;">
                    <span style="font-size: 0.9rem; color: var(--text-gray);">Click image to change</span>
                </div>
                <input type="file" name="profile_pic" id="profilePicInput" style="display: none;" accept="image/*" onchange="previewFile()">

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label>Email Address</label>
                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background: #f9fafb; cursor: not-allowed;">
                    <span style="font-size: 0.8rem; color: var(--text-gray);">Email cannot be changed directly. Contact support if needed.</span>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;">
                    Save Profile
                </button>

            </form>
        </div>

    </div>

    <script>
        function previewFile() {
            const preview = document.getElementById('previewImg');
            const file = document.querySelector('input[type=file]').files[0];
            const reader = new FileReader();

            reader.addEventListener("load", function () {
                preview.src = reader.result;
            }, false);

            if (file) {
                reader.readAsDataURL(file);
            }
        }
    </script>

</body>
</html>
