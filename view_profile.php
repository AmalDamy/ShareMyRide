<?php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$view_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$view_user_id) {
    header("Location: dashboard.php");
    exit;
}

// Fetch User Info
$userQ = $conn->prepare("SELECT name, profile_pic, rating, rating_count, created_at, is_verified FROM users WHERE user_id = ?");
$userQ->bind_param("i", $view_user_id);
$userQ->execute();
$userData = $userQ->get_result()->fetch_assoc();

if (!$userData) {
    echo "<script>alert('User not found.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Fetch Reviews for this user
$reviewsQ = $conn->prepare("SELECT r.*, u.name as reviewer_name, u.profile_pic as reviewer_pic FROM reviews r JOIN users u ON r.reviewer_id = u.user_id WHERE r.reviewee_id = ? ORDER BY r.created_at DESC");
$reviewsQ->bind_param("i", $view_user_id);
$reviewsQ->execute();
$reviewsResult = $reviewsQ->get_result();
$reviews = [];
while($row = $reviewsResult->fetch_assoc()) {
    $reviews[] = $row;
}

// Fetch completed rides count
$ridesCountQ = $conn->prepare("SELECT COUNT(*) as cnt FROM rides WHERE driver_id = ? AND status = 'completed'");
$ridesCountQ->bind_param("i", $view_user_id);
$ridesCountQ->execute();
$ridesCount = $ridesCountQ->get_result()->fetch_assoc()['cnt'];

// Display Path for Profile Pic
$profilePicPath = 'https://ui-avatars.com/api/?name=' . urlencode($userData['name']) . '&background=random';
if (!empty($userData['profile_pic'])) {
    if (filter_var($userData['profile_pic'], FILTER_VALIDATE_URL)) {
        $profilePicPath = $userData['profile_pic'];
    } elseif (file_exists($userData['profile_pic'])) {
        $profilePicPath = $userData['profile_pic'];
    } elseif (file_exists('uploads/' . $userData['profile_pic'])) {
        $profilePicPath = 'uploads/' . $userData['profile_pic'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($userData['name']); ?>'s Profile - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--dark-teal), var(--primary-teal));
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 0 0 40px 40px;
            margin-bottom: -4rem;
        }
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        .profile-img-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: var(--shadow-md);
            margin: -75px auto 1rem;
            object-fit: cover;
            background: #f1f5f9;
        }
        .verification-badge {
            background: #dcfce7;
            color: #166534;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 2rem 0;
            text-align: center;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            padding: 1.5rem 0;
        }
        .stat-item h3 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--dark-teal);
        }
        .stat-item p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .review-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid #f1f5f9;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .reviewer-img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <nav class="navbar" style="position: absolute; background: transparent; box-shadow: none;">
        <div class="container nav-content">
            <a href="index.php" class="logo" style="color: white;">ShareMyRide</a>
            <div class="nav-links">
                <a href="dashboard.php" style="color: white; font-weight: 700;">Dashboard</a>
                <a href="find_ride.php" style="color: white;">Find Ride</a>
                <a href="offer_ride.php" style="color: white;">Offer Ride</a>
                <a href="logout.php" style="color: #fecaca; font-weight: 600;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <header class="profile-header">
        <div class="container">
            <h1 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($userData['name']); ?></h1>
            <p style="opacity: 0.9;">Member since <?php echo date('M Y', strtotime($userData['created_at'])); ?></p>
        </div>
    </header>

    <main class="container" style="padding-bottom: 4rem;">
        <div class="profile-card">
            <img src="<?php echo htmlspecialchars($profilePicPath); ?>" alt="Profile" class="profile-img-large">
            
            <div style="text-align: center;">
                <h2><?php echo htmlspecialchars($userData['name']); ?></h2>
                <?php if ($userData['is_verified']): ?>
                <div class="verification-badge">
                    <i class="fas fa-check-circle"></i> Verified User
                </div>
                <?php else: ?>
                <div class="verification-badge" style="background: #fef2f2; color: #991b1b;">
                    <i class="fas fa-clock"></i> Identity Pending
                </div>
                <?php endif; ?>

                <div class="stat-grid">
                    <div class="stat-item">
                        <h3><?php echo number_format($userData['rating'], 1); ?> ⭐</h3>
                        <p>Avg Rating</p>
                    </div>
                    <div class="stat-item">
                        <h3><?php echo $userData['rating_count']; ?></h3>
                        <p>Reviews</p>
                    </div>
                    <div class="stat-item">
                        <h3><?php echo $ridesCount; ?></h3>
                        <p>Rides Done</p>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-star" style="color: var(--accent-yellow);"></i> 
                    Recent Reviews
                </h3>

                <?php if (empty($reviews)): ?>
                <div style="text-align: center; padding: 2rem; color: var(--text-gray); background: #f9fafb; border-radius: 12px;">
                    <i class="fas fa-comment-slash fa-2x" style="margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No reviews yet for this user.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <img src="uploads/<?php echo htmlspecialchars($review['reviewer_pic']); ?>" alt="Reviewer" class="reviewer-img">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.9rem;"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-gray);"><?php echo date('d M Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                            <div style="color: var(--accent-yellow); font-weight: 700;">
                                <?php for($i=0; $i<$review['rating']; $i++) echo "⭐"; ?>
                            </div>
                        </div>
                        <p style="color: var(--text-dark); font-size: 0.95rem; margin: 0; line-height: 1.4;">
                            "<?php echo htmlspecialchars($review['comment']); ?>"
                        </p>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="margin-top: 2.5rem; text-align: center;">
                <button onclick="history.back()" class="btn btn-outline" style="border-radius: 50px; padding: 0.75rem 2rem;">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
        </div>
    </main>

    <footer style="text-align: center; padding: 2rem; color: var(--text-gray); font-size: 0.85rem;">
        &copy; <?php echo date('Y'); ?> ShareMyRide. Safety First.
    </footer>
</body>
</html>
