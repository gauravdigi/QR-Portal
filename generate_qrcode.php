<?php
session_start();
require 'vendor/autoload.php';
require 'config.php';
require 'error_handler.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;

function formatFileName($flatNo, $name) {
    $slug = strtolower(trim(preg_replace('/\s+/', '-', $name)));
    return $flatNo . '-' . $slug . '.png';
}
function formatName($name) {
    return strtolower(trim(preg_replace('/\s+/', '-', $name)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
        $name = $conn->real_escape_string($_POST['name']);
        $flat_no = $conn->real_escape_string($_POST['flat_no']);
        $type = $conn->real_escape_string($_POST['type']);
        $expiry_date = isset($_POST['expiry_date']) ? $conn->real_escape_string($_POST['expiry_date']) : null;

        if (strtolower($type) === 'guest') {
            // Auto-set expiry date
            $expiry_date = date('Y-m-d', strtotime('+4 days'));

            $monthStart = date('Y-m-01');
            $monthEnd   = date('Y-m-t');

            // Count guests in current month
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM users 
                WHERE flat_no = ? 
                  AND type = 'Guest' 
                  AND is_deleted = 0 
                  AND expiry_date BETWEEN ? AND ?
            ");
            $stmt->bind_param("sss", $flat_no, $monthStart, $monthEnd);
            $stmt->execute();
            $stmt->bind_result($guestCount);
            $stmt->fetch();
            $stmt->close();

            if ($guestCount >= 4) {
                echo json_encode([
                    'status' => 'error',
                    'message' => "Only 4 guest users are allowed per flat per month. Already $guestCount guests added for $flat_no."
                ]);
                exit;
            }

        } else {
            // For non-guest users — check active user limit
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM users 
                WHERE flat_no = ? 
                  AND is_deleted = 0 
                  AND expiry_date >= CURDATE()
                  AND type != 'Guest'
            ");
            $stmt->bind_param("s", $flat_no);
            $stmt->execute();
            $stmt->bind_result($flatCount);
            $stmt->fetch();
            $stmt->close();

            if ($flatCount >= 4) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => "Cannot add more than $flatCount active users for flat $flat_no."
                ]);
                exit;
            }

                // ✅ ONLY update expiry for non-guest user types
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET expiry_date = ? 
                    WHERE flat_no = ? 
                      AND is_deleted = 0 
                      AND expiry_date >= CURDATE()
                      AND type != 'Guest'
                ");
                $stmt->bind_param("ss", $expiry_date, $flat_no);
                $stmt->execute();
                $stmt->close();
        }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid photo file type.']);
            exit;
        }

        $newFileName = formatName($name) . '.' . $fileExtension;
        $uploadDir = './uploads/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
        $uploadPath = $uploadDir . $newFileName;

        if (!move_uploaded_file($fileTmpPath, $uploadPath)) {
            echo json_encode(['status' => 'error', 'message' => 'Error moving uploaded photo.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO users (name, type, flat_no, expiry_date, photo, is_deleted) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->bind_param("sssss", $name, $type, $flat_no, $expiry_date, $newFileName);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'DB Insert error: ' . $stmt->error]);
            exit;
        }
        $lastId = $conn->insert_id;
        $stmt->close();

       
        $secretKey = 'digisoftsolution';
        $hash = hash('sha256', $lastId . $expiry_date . $secretKey);
        $content = "http://ajowa.webdummy.info/validate_qrcode.php?id=$lastId&token=$hash&expiry=$expiry_date";

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($content)
            ->encoding(new Encoding('UTF-8'))
            ->size(280)
            ->margin(2)
            ->build();

        $tempQrPath = 'qrcodes/temp_qr.png';
        file_put_contents($tempQrPath, $result->getString());

        // === DESIGN CARD ===
        $cardWidth = 600;
        $cardHeight = 340;
        $background = imagecreatetruecolor($cardWidth, $cardHeight);

        // Colors
        $black = imagecolorallocate($background, 20, 20, 20);
        $white = imagecolorallocate($background, 255, 255, 255);
        imagefill($background, 0, 0, $black);

        // Border
        $borderColor = imagecolorallocate($background, 255, 255, 255);
        $borderThickness = 4;
        for ($i = 0; $i < $borderThickness; $i++) {
            imagerectangle($background, $i, $i, $cardWidth - 1 - $i, $cardHeight - 1 - $i, $borderColor);
        }

        // Title
        $labelText = "Acme Jubilee Owner Welfare Association";
        $labelFontSize = 16;
        $labelY = 30;
        $labelFontPath = __DIR__ . "/fonts/OpenSans-Regular.ttf";

        if (file_exists($labelFontPath)) {
            $bbox = imagettfbbox($labelFontSize, 0, $labelFontPath, $labelText);
            $textWidth = $bbox[2] - $bbox[0];
            $x = ($cardWidth - $textWidth) / 2;
            imagettftext($background, $labelFontSize, 0, $x, $labelY, $white, $labelFontPath, $labelText);
        }

        // QR code
        $qrImg = imagecreatefrompng($tempQrPath);
        imagecopyresampled($background, $qrImg, 340, 60, 0, 0, 220, 220, imagesx($qrImg), imagesy($qrImg));
        imagedestroy($qrImg);

        // === USER IMAGE - NO MASK ===
        $logoPath = $uploadPath;
        if (!file_exists($logoPath)) die("Logo not found at: $logoPath");

        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $userImg = imagecreatefromjpeg($logoPath);
                break;
            case 'png':
                $userImg = imagecreatefrompng($logoPath);
                break;
            case 'gif':
                $userImg = imagecreatefromgif($logoPath);
                break;
            default:
                die("Unsupported image format: $extension");
        }

        if (!$userImg) die("Failed to load user image from: $logoPath");

        // Resize and place directly
        $targetWidth = 150;
        $targetHeight = 150;
        $userResized = imagecreatetruecolor($targetWidth, $targetHeight);

        // Optional: fill with matching background to avoid contrast
        $fillBg = imagecolorallocate($userResized, 20, 20, 20); // match card bg
        imagefill($userResized, 0, 0, $fillBg);

        // Copy resized
        imagecopyresampled($userResized, $userImg, 0, 0, 0, 0, $targetWidth, $targetHeight, imagesx($userImg), imagesy($userImg));
        imagecopy($background, $userResized, 60, 80, 0, 0, $targetWidth, $targetHeight);

        // Cleanup
        imagedestroy($userImg);
        imagedestroy($userResized);


        
        
     function wrapTextTTF($text, $fontFile, $fontSize, $maxWidth) {
            $words = explode(' ', $text);
            $lines = [];
            $currentLine = '';

            foreach ($words as $word) {
                $testLine = $currentLine ? $currentLine . ' ' . $word : $word;
                $box = imagettfbbox($fontSize, 0, $fontFile, $testLine);
                $lineWidth = $box[2] - $box[0];

                if ($lineWidth > $maxWidth && $currentLine !== '') {
                    $lines[] = $currentLine;
                    $currentLine = $word;

                    // Handle single long words that are wider than $maxWidth
                    $boxWord = imagettfbbox($fontSize, 0, $fontFile, $word);
                    $wordWidth = $boxWord[2] - $boxWord[0];
                    if ($wordWidth > $maxWidth) {
                        // Split the word itself
                        $splitWord = '';
                        for ($i = 0; $i < mb_strlen($word); $i++) {
                            $splitWord .= mb_substr($word, $i, 1);
                            $boxSplit = imagettfbbox($fontSize, 0, $fontFile, $splitWord);
                            $splitWidth = $boxSplit[2] - $boxSplit[0];
                            if ($splitWidth > $maxWidth) {
                                $lines[] = mb_substr($splitWord, 0, -1);
                                $splitWord = mb_substr($splitWord, -1);
                            }
                        }
                        $currentLine = $splitWord;
                    }
                } else {
                    $currentLine = $testLine;
                }
            }

            if ($currentLine !== '') {
                $lines[] = $currentLine;
            }

            return implode("\n", $lines);
        }

        // Name & Flat number
        // if (file_exists($labelFontPath)) {
        //     imagettftext($background, 16, 0, 60, 260, $white, $labelFontPath, strtoupper($name));
        //     imagettftext($background, 12, 0, 60, 285, $white, $labelFontPath, "Flat No: " . $flat_no);
        // } else {
        //     imagestring($background, 5, 60, 260, strtoupper($name), $white);
        //     imagestring($background, 3, 60, 285, "Flat No: " . $flat_no, $white);
        // }

        if (file_exists($labelFontPath)) {
            $nameText = strtoupper($name);
            $flatText = "Flat No: " . $flat_no;

            $nameFontSize = 12;
            $flatFontSize = 12;

            // Add clearer spacing below the photo
            $photoBottom = 80 + 160; // photo top + photo height
            $padding = 20; // adjust this value to control spacing
            $startY = $photoBottom + $padding; // 260

            $x = 60; // aligned with photo left
            $wrappedNameText = wrapTextTTF($nameText, $labelFontPath, $nameFontSize, 180);
            $lines = explode("\n", $wrappedNameText);
            $lineHeight = $nameFontSize + 4;

            foreach ($lines as $i => $line) {
                imagettftext($background, $nameFontSize, 0, $x, $startY + ($i * $lineHeight), $white, $labelFontPath, $line);
            }
            $flatStartY = $startY + (count($lines) * $lineHeight) + 5;
            imagettftext($background, $flatFontSize, 0, $x, $flatStartY, $white, $labelFontPath, $flatText);
            // imagettftext($background, $nameFontSize, 0, $x, $startY, $white, $labelFontPath, $nameText);
            // imagettftext($background, $flatFontSize, 0, $x, $startY + 20, $white, $labelFontPath, $flatText);
        } else {
            imagestring($background, 5, 60, 260, $nameText, $white);
            imagestring($background, 3, 60, 280, $flatText, $white);
        }


        // Final save
        $qrFileName = formatFileName($flat_no, $name);
        $qrDir = 'qrcodes/';
        if (!file_exists($qrDir)) mkdir($qrDir, 0755, true);
        $qrFilePath = $qrDir . $qrFileName;
        imagepng($background, $qrFilePath);

        imagedestroy($background);
        unlink($tempQrPath);

        
        if($qrFileName){

        $stmt = $conn->prepare("UPDATE users SET qr_code = ? WHERE id = ?");
        $stmt->bind_param("si", $qrFileName, $lastId);
        $stmt->execute();
        $stmt->close();

        $cardHtml = '
            <div class="secure-card text-center">
                <img src="' . htmlspecialchars($qrFilePath) . '" alt="QR Card" class="img-fluid">
                <div class="mt-3">
                    <a href="' . htmlspecialchars($qrFilePath) . '" download class="btn btn-success">Download Card</a>
                </div>
            </div>
        ';

        echo json_encode([
            'status' => 'success',
            'message' => 'User successfully added.',
            'html' => $cardHtml
        ]);
        exit;
    }else{
        echo json_encode([
            'status' => 'error',
            'message' => 'Something went wrong while generating the QR code.',
        ]);
        exit;
    }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Photo upload error.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}
$conn->close();
?>
