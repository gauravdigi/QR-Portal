<?php include 'header.php'; 
session_start();
?>

    

        <?php
        $type = $user['type'];
        $expiryDate = strtotime($user['expiry_date']);

        $today = strtotime(date('d-m-Y'));
        $tomorrow = strtotime('+1 day');
        $tenDaysFromNow = strtotime('+10 days');

        $daysLeft = ($expiryDate - $today) / (60 * 60 * 24); // Days difference
        $daysLeft = floor($daysLeft);

        // Default
        $badgeClass = 'success';
        $noteMessage = '';
        
        if ($expiryDate < $today) {
            $exipredmsg = 'Expired';
            // Already expired
            $badgeClass = 'danger';
            $borderClass = 'danger';
            $noteMessage = '<div class="alert alert-danger mt-3 mb-0 p-2 text-center small rounded-3">
                                <strong>Note:</strong> Your club card has expired. Please contact RWA Office.
                            </div>';
        } elseif ($expiryDate == $today) {
            // Expires today
            $exipredmsg = '';
            $badgeClass = 'success';
            $borderClass = 'success';
            $noteMessage = '<div class="alert alert-warning mt-3 mb-0 p-2 text-center small rounded-3">
                                <strong>Note:</strong> Your club card will expire <strong>today</strong>. Please contact RWA Office.
                            </div>';
        } elseif ($expiryDate == $tomorrow) {
            // Expires tomorrow
            $exipredmsg = '';
            $borderClass = 'success';
            $badgeClass = 'success';
            $noteMessage = '<div class="alert alert-warning mt-3 mb-0 p-2 text-center small rounded-3">
                                <strong>Note:</strong> Your club card will expire <strong>tomorrow</strong>. Please contact RWA Office.
                            </div>';
        } elseif ($expiryDate <= $tenDaysFromNow) {
            // Within next 10 days
            $exipredmsg = '';
            $borderClass = 'success';
            $badgeClass = 'success';
            $noteMessage = '<div class="alert alert-info mt-3 mb-0 p-2 text-center small rounded-3">
                                <strong>Note:</strong> Your club card will expire in <strong>' . $daysLeft . ' days</strong>. Please contact RWA Office.
                            </div>';
        }else{
            $borderClass = 'success';
        }

    ?>
<div class="card shadow rounded-4 border-2 border-<?php echo $borderClass; ?> p-4 mx-auto" style="max-width: 500px;">
    <h3 class="text-center mb-3">Acme Jubilee Owner Welfare Association</h3>
    <div class="text-center mb-0">
        <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" 
             alt="User Photo" 
             class="rounded-circle shadow-sm" 
             style="width: 120px; height: 120px; object-fit: cover;">
         <h4 class="mt-3 mb-0 text-capitalize">
            <?php echo htmlspecialchars(ucwords($user['name'])); ?>
        </h4>
        <p class="text-muted mb-1">Member Type: <strong><?php echo htmlspecialchars($user['type']); ?></strong></p>
        <p class="text-muted mb-0">Flat No: <strong><?php echo htmlspecialchars($user['flat_no']); ?></strong></p>

        <p class="text-danger text-center fw-bold mb-0" style="font-size:3em;"><?php echo $exipredmsg; ?></p>
    </div>

    <hr>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="fw-semibold">Expiry Date:</span>
        <span class="badge bg-<?php echo $badgeClass; ?> p-2">
            <?php echo htmlspecialchars(date('j F Y', strtotime($user['expiry_date']))); ?>
        </span>
    </div>

    <?php if($type != 'Guest'){ echo $noteMessage; } ?>
</div>


 <?php include 'footer.php'; ?>