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
    $phone = trim($_POST['phone'] ?? '');
    
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
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, profile_pic = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $name, $phone, $profile_pic_path, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE user_id = ?");
                $stmt->bind_param("ssi", $name, $phone, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['username'] = $name; // Update Session Name
                $_SESSION['phone'] = $phone; // Update Session Phone
                if ($profile_pic_path) {
                    $_SESSION['profile_pic'] = $profile_pic_path;
                }

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
$stmt = $conn->prepare("SELECT name, email, phone, profile_pic FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fallback image (handled in navbar but kept here for form preview)
$current_pic = 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';

if (!empty($user['profile_pic'])) {
    if (filter_var($user['profile_pic'], FILTER_VALIDATE_URL)) {
        $current_pic = $user['profile_pic'];
    } elseif (file_exists($user['profile_pic'])) {
        $current_pic = $user['profile_pic'];
    }
}
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
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --bg-body: #f8fafc;
        }
        body {
            background-color: var(--bg-body);
        }
        .profile-wrapper {
            padding: 3rem 1.5rem;
            display: flex;
            justify-content: center;
        }
        .profile-card-v3 {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            border: 1px solid #f1f5f9;
        }
        .profile-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .profile-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .profile-header h1 span {
            font-size: 0.875rem;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .cancel-link {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            color: #64748b;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
            border-radius: 12px;
            transition: all 0.2s;
        }
        .cancel-link:hover {
            background: #f1f5f9;
            color: #ef4444;
        }
        .avatar-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 0.75rem;
            cursor: pointer;
        }
        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .avatar-container:hover .avatar-img {
            transform: scale(1.05);
        }
        .avatar-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .avatar-container:hover .avatar-overlay {
            opacity: 1;
        }
        .form-group-custom {
            margin-bottom: 1.5rem;
        }
        .form-group-custom label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.5rem;
        }
        .input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-container i {
            position: absolute;
            left: 1rem;
            color: #94a3b8;
        }
        .input-field {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 1rem;
            color: #1e293b;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f8fafc;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
            background: white;
        }
        .input-field:disabled {
            background: #f1f5f9;
            cursor: not-allowed;
            border-style: dashed;
        }
        .phone-row {
            display: flex;
            gap: 0.75rem;
        }
        .phone-pre {
            padding: 0.75rem 1rem;
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-weight: 700;
            color: #64748b;
        }
        .error-hint {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.35rem;
            display: none;
            align-items: center;
            gap: 0.25rem;
        }
        .error-hint i {
            font-size: 0.7rem;
        }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), #115e59);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 1rem;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(13, 148, 136, 0.4);
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="profile-wrapper">
        <div class="profile-card-v3">
            <div class="profile-header">
                <h1><span>Profile Settings</span>Edit Profile</h1>
                <a href="dashboard.php" class="cancel-link"><i class="fas fa-times"></i> Cancel</a>
            </div>

            <?php if ($message): ?>
                <div style="padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; 
                            background: <?php echo $messageType === 'success' ? '#d1fae5' : '#fee2e2'; ?>; 
                            color: <?php echo $messageType === 'success' ? '#065f46' : '#991b1b'; ?>;">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form id="editForm" method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm()">
                
                <div class="avatar-section">
                    <div class="avatar-container" onclick="document.getElementById('profilePicInput').click()">
                        <img src="<?php echo htmlspecialchars($current_pic); ?>" id="preview" class="avatar-img">
                        <div class="avatar-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.5rem;">Click to update photo</p>
                    <input type="file" name="profile_pic" id="profilePicInput" style="display: none;" accept="image/*" onchange="previewImage(this)">
                </div>

                <div class="form-group-custom">
                    <label>Full Name</label>
                    <div class="input-container">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" id="fName" class="input-field" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="e.g. John Doe">
                    </div>
                    <div id="nameErr" class="error-hint"><i class="fas fa-circle-exclamation"></i> Full name is required (min 3 chars).</div>
                </div>

                <div class="form-group-custom">
                    <label>Email Address</label>
                    <div class="input-container">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="input-field" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                </div>

                <div class="form-group-custom">
                    <label>Phone Number</label>
                    <div class="phone-row">
                        <div class="phone-pre">+91</div>
                        <div class="input-container" style="flex: 1;">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" id="fPhone" class="input-field" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="9876543210" maxlength="10">
                        </div>
                    </div>
                    <div id="phoneErr" class="error-hint"><i class="fas fa-circle-exclamation"></i> Valid 10-digit number required.</div>
                </div>

                <button type="submit" class="btn-submit">
                    Save Changes
                </button>

            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function validateForm() {
            let valid = true;
            const name = document.getElementById('fName');
            const phone = document.getElementById('fPhone');
            
            // Name check
            if (name.value.trim().length < 3) {
                document.getElementById('nameErr').style.display = 'flex';
                name.style.borderColor = '#ef4444';
                valid = false;
            } else {
                document.getElementById('nameErr').style.display = 'none';
                name.style.borderColor = '#e2e8f0';
            }

            // Phone check
            if (!/^\d{10}$/.test(phone.value)) {
                document.getElementById('phoneErr').style.display = 'flex';
                phone.style.borderColor = '#ef4444';
                valid = false;
            } else {
                document.getElementById('phoneErr').style.display = 'none';
                phone.style.borderColor = '#e2e8f0';
            }

            return valid;
        }

        // Real-time number filtering
        document.getElementById('fPhone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });
    </script>

</body>
</html>
