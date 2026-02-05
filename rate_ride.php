<?php
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Ride - ShareMyRide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 10px;
            font-size: 2.5rem;
            margin: 2rem 0;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            color: #ccc;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #f59e0b;
        }
    </style>
</head>
<body>
    
    <div class="container" style="max-width: 500px; margin-top: 4rem; text-align: center;">
        
        <div class="search-card" style="display: block;">
            <div style="width: 80px; height: 80px; background: #d1fae5; border-radius: 50%; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 1.5rem;">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 style="color: var(--text-dark); margin-bottom: 0.5rem;">Ride Completed!</h1>
            <p style="color: var(--text-gray);">You have arrived at your destination.</p>
            
            <hr style="border: 0; border-top: 1px solid #eee; margin: 2rem 0;">
            
            <h3 style="margin-bottom: 1rem;">How was your ride?</h3>
            
            <form id="ratingForm" onsubmit="submitRating(event)">
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5"><label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <textarea class="form-input" id="comment" rows="3" placeholder="Write a short review (optional)..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Submit Feedback</button>
            </form>
            
            <a href="dashboard.php" style="display: block; margin-top: 1.5rem; color: var(--text-gray); font-size: 0.9rem;">Skip Feedback</a>
        </div>

    </div>

    <!-- Scripts -->
    <script src="js/ride_manager.js"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const rideId = urlParams.get('ride_id');
        const requestId = urlParams.get('request_id');

        async function submitRating(e) {
            e.preventDefault();
            
            const rating = document.querySelector('input[name="rating"]:checked');
            if(!rating) {
                await RideManager.showAlert('Rating Required', "Please select a star rating!", 'error');
                return;
            }
            
            const comment = document.getElementById('comment').value;
            const val = rating.value;

            const btn = e.target.querySelector('button');
            btn.innerHTML = 'Submitting...';
            btn.disabled = true;

            try {
                // api_reviews.php to be created
                const response = await fetch('api_reviews.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        ride_id: rideId,
                        request_id: requestId,
                        rating: val,
                        comment: comment
                    })
                });
                
                const res = await response.json();
                
                if(res.success) {
                    await RideManager.showAlert('Review Submitted', 'Thank you for your feedback!', 'success');
                    window.location.href = 'dashboard.php';
                } else {
                    await RideManager.showAlert('Submission Error', res.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Submit Feedback';
                }
            } catch(e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>
