<?php
/**
 * File Upload Helper for Profile Photos
 * Handles validation, uploading, and resizing of profile images
 */

/**
 * Upload a profile photo
 * @param array $file The $_FILES array element
 * @param string $type Either 'user' or 'instructor'
 * @param int $id The user or instructor ID
 * @return string|false Returns the filename on success, false on failure
 */
function uploadProfilePhoto($file, $type, $id) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Invalid file type. Only JPEG, PNG, and GIF are allowed.");
    }
    
    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception("File size too large. Maximum size is 5MB.");
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = __DIR__ . "/../public/uploads/profiles/{$type}s/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $id . '_' . time() . '_' . uniqid() . '.' . strtolower($extension);
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Failed to upload file.");
    }
    
    // Resize image to standard profile photo size (300x300)
    resizeProfilePhoto($filepath, 300, 300);
    
    return $filename;
}

/**
 * Resize an image to specified dimensions
 * @param string $filepath Path to the image file
 * @param int $width Target width
 * @param int $height Target height
 */
function resizeProfilePhoto($filepath, $width, $height) {
    // Get image info
    $imageInfo = getimagesize($filepath);
    if (!$imageInfo) {
        return; // Skip if not a valid image
    }
    
    $originalWidth = $imageInfo[0];
    $originalHeight = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // Skip if image is already smaller than target size
    if ($originalWidth <= $width && $originalHeight <= $height) {
        return;
    }
    
    // Calculate aspect ratio and crop dimensions
    $aspectRatio = $originalWidth / $originalHeight;
    $targetAspectRatio = $width / $height;
    
    if ($aspectRatio > $targetAspectRatio) {
        // Image is wider, crop width
        $newWidth = $originalHeight * $targetAspectRatio;
        $newHeight = $originalHeight;
        $cropX = ($originalWidth - $newWidth) / 2;
        $cropY = 0;
    } else {
        // Image is taller, crop height
        $newWidth = $originalWidth;
        $newHeight = $originalWidth / $targetAspectRatio;
        $cropX = 0;
        $cropY = ($originalHeight - $newHeight) / 2;
    }
    
    // Create image resource from file
    switch ($mimeType) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filepath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filepath);
            break;
        default:
            return; // Skip unsupported formats
    }
    
    if (!$source) {
        return;
    }
    
    // Create destination image
    $destination = imagecreatetruecolor($width, $height);
    
    // Preserve transparency for PNG and GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $width, $height, $transparent);
    }
    
    // Resize and crop
    imagecopyresampled(
        $destination, $source,
        0, 0, $cropX, $cropY,
        $width, $height, $newWidth, $newHeight
    );
    
    // Save the resized image
    switch ($mimeType) {
        case 'image/jpeg':
            imagejpeg($destination, $filepath, 85);
            break;
        case 'image/png':
            imagepng($destination, $filepath, 8);
            break;
        case 'image/gif':
            imagegif($destination, $filepath);
            break;
    }
    
    // Clean up memory
    imagedestroy($source);
    imagedestroy($destination);
}

/**
 * Delete an old profile photo
 * @param string $filename The filename to delete
 * @param string $type Either 'user' or 'instructor'
 */
function deleteProfilePhoto($filename, $type) {
    if (empty($filename)) {
        return;
    }
    
    $filepath = __DIR__ . "/../public/uploads/profiles/{$type}s/" . $filename;
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

/**
 * Get the URL for a profile photo
 * @param string $filename The filename
 * @param string $type Either 'user' or 'instructor'
 * @param string $default Default image URL if no photo exists
 * @return string The photo URL
 */
function getProfilePhotoUrl($filename, $type, $default = null) {
    if (empty($filename)) {
        return $default ?: "data:image/svg+xml;base64," . base64_encode(getDefaultProfileSvg());
    }
    
    // Check if file exists on filesystem
    $filepath = __DIR__ . "/../public/uploads/profiles/{$type}s/" . $filename;
    if (!file_exists($filepath)) {
        return $default ?: "data:image/svg+xml;base64," . base64_encode(getDefaultProfileSvg());
    }
    
    // Determine the correct relative path based on current location
    $currentPath = $_SERVER['SCRIPT_NAME'];
    
    if (strpos($currentPath, '/admin/') !== false) {
        // Called from admin directory - need to go up one level
        return "../public/uploads/profiles/{$type}s/" . $filename;
    } elseif (strpos($currentPath, '/public/') !== false) {
        // Called from public directory or subdirectory
        return "uploads/profiles/{$type}s/" . $filename;
    } else {
        // Called from root or other location
        return "public/uploads/profiles/{$type}s/" . $filename;
    }
}

/**
 * Get a default profile photo SVG
 * @return string SVG content
 */
function getDefaultProfileSvg() {
    return '<svg width="300" height="300" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
        <rect width="300" height="300" fill="#f8f9fa"/>
        <circle cx="150" cy="120" r="50" fill="#dee2e6"/>
        <path d="M100 220 Q100 180 150 180 Q200 180 200 220 L200 300 L100 300 Z" fill="#dee2e6"/>
        <text x="150" y="270" text-anchor="middle" fill="#6c757d" font-family="Arial, sans-serif" font-size="16">No Photo</text>
    </svg>';
}
?> 