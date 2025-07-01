<?php
// Include required files
require_once '../api/db.php';
require_once '../../config/user_auth.php';
require_once '../../config/membership_functions.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Get user info
$userInfo = getUserInfo();
$userId = $userInfo['id'];

// Check if user has video access (must have active membership or unused free trial)
$hasVideoAccess = userHasVideoAccess($userId);

if (!$hasVideoAccess) {
    header('Location: membership.php');
    exit;
}

// Get video series and videos
$videoSeries = [];
$selectedSeries = null;
$seriesVideos = [];

try {
    // Get all active video series
    $stmt = $pdo->query("
        SELECT vs.*, COUNT(v.id) as video_count
        FROM video_series vs
        LEFT JOIN videos v ON vs.id = v.series_id AND v.status = 'active'
        WHERE vs.status = 'active'
        GROUP BY vs.id
        ORDER BY vs.sort_order, vs.title
    ");
    $videoSeries = $stmt->fetchAll();
    
    // Get selected series
    $seriesId = isset($_GET['series']) ? (int)$_GET['series'] : null;
    
    if ($seriesId) {
        // Get series details
        $stmt = $pdo->prepare("
            SELECT * FROM video_series 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$seriesId]);
        $selectedSeries = $stmt->fetch();
        
        if ($selectedSeries) {
            // Get videos in this series
            $stmt = $pdo->prepare("
                SELECT * FROM videos 
                WHERE series_id = ? AND status = 'active'
                ORDER BY sort_order, title
            ");
            $stmt->execute([$seriesId]);
            $seriesVideos = $stmt->fetchAll();
        }
    }
    
} catch (Exception $e) {
    error_log('Error fetching videos: ' . $e->getMessage());
}

// Include the layout configuration
if (file_exists(__DIR__ . '/../../templates/config.php')) {
    include __DIR__ . '/../../templates/config.php';
}

// Set up page-specific configuration
setupPageConfig([
    'pageTitle' => 'Member Videos - Class Booking System',
    'cssPath' => '../../assets/css/custom.css',
    'navItems' => getUserNavigation('videos'),
    'footerLinks' => getPublicFooterLinks(),
    'additionalCSS' => [
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
    ],
    'additionalJS' => [
        'https://vjs.zencdn.net/8.6.1/video.js'
    ]
]);

$content = '';

$content .= <<<HTML
<div class="row mb-4">
    <div class="col-12">
        <h1><i class="fas fa-play-circle me-2"></i>Member Videos</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Videos</li>
            </ol>
        </nav>
    </div>
</div>
HTML;

if ($selectedSeries) {
    // Show selected series and its videos
    $content .= <<<HTML
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3><i class="fas fa-video me-2"></i>{$selectedSeries['title']}</h3>
                <a href="videos.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>Back to Series
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted">{$selectedSeries['description']}</p>
                
                <div class="row">
HTML;
    
    if ($seriesVideos) {
        foreach ($seriesVideos as $video) {
            $duration = $video['duration_seconds'] ? gmdate("i:s", $video['duration_seconds']) : 'N/A';
            $fileSize = $video['file_size'] ? round($video['file_size'] / (1024*1024), 1) . ' MB' : 'N/A';
            $thumbnailPath = $video['thumbnail_path'] ? '../' . $video['thumbnail_path'] : '../assets/images/video-placeholder.jpg';
            
            $content .= <<<HTML
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="video-thumbnail position-relative">
                                <img src="{$thumbnailPath}" class="card-img-top" alt="{$video['title']}" style="height: 200px; object-fit: cover;">
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <button class="btn btn-primary btn-lg rounded-circle" onclick="playVideo({$video['id']}, '{$video['title']}', '../{$video['file_path']}')">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                                <div class="position-absolute bottom-0 end-0 bg-dark text-white px-2 py-1 m-2 rounded">
                                    {$duration}
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">{$video['title']}</h5>
                                <p class="card-text">{$video['description']}</p>
                                <small class="text-muted">
                                    <i class="fas fa-file me-1"></i>Format: {$video['format']} • 
                                    <i class="fas fa-hdd me-1"></i>Size: {$fileSize}
                                </small>
                            </div>
                        </div>
                    </div>
HTML;
        }
    } else {
        $content .= <<<HTML
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <h4>No Videos Available</h4>
                            <p class="mb-0">This series doesn't have any videos yet. Check back later!</p>
                        </div>
                    </div>
HTML;
    }
    
    $content .= <<<HTML
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
    
} else {
    // Show video series overview
    $content .= <<<HTML
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-crown me-2"></i>
            <strong>Member Exclusive Content:</strong> Access our complete library of instructional videos as part of your membership!
        </div>
    </div>
</div>

<div class="row">
HTML;
    
    if ($videoSeries) {
        foreach ($videoSeries as $series) {
            $coverImage = $series['cover_image'] ? '../' . $series['cover_image'] : '../assets/images/series-placeholder.jpg';
            $videoCount = $series['video_count'];
            
            $content .= <<<HTML
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
            <img src="{$coverImage}" class="card-img-top" alt="{$series['title']}" style="height: 200px; object-fit: cover;">
            <div class="card-body">
                <h5 class="card-title">{$series['title']}</h5>
                <p class="card-text">{$series['description']}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-video me-1"></i>{$videoCount} videos
                    </small>
                    <a href="videos.php?series={$series['id']}" class="btn btn-primary btn-sm">
                        <i class="fas fa-play me-2"></i>Watch Series
                    </a>
                </div>
            </div>
        </div>
    </div>
HTML;
        }
    } else {
        $content .= <<<HTML
    <div class="col-12">
        <div class="alert alert-info text-center">
            <i class="fas fa-video fa-3x mb-3"></i>
            <h4>No Video Series Available</h4>
            <p class="mb-0">Video content is being prepared. Check back soon!</p>
        </div>
    </div>
HTML;
    }
    
    $content .= <<<HTML
</div>
HTML;
}

// Video player modal
$content .= <<<HTML
<!-- Video Player Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel">Video Player</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <video id="videoPlayer" class="w-100" controls>
                    <p>Your browser doesn't support HTML5 video.</p>
                </video>
            </div>
        </div>
    </div>
</div>

<script>
function playVideo(videoId, title, videoPath) {
    // Set video source and title
    const videoPlayer = document.getElementById('videoPlayer');
    const modalTitle = document.getElementById('videoModalLabel');
    
    videoPlayer.src = videoPath;
    modalTitle.textContent = title;
    
    // Show modal
    const videoModal = new bootstrap.Modal(document.getElementById('videoModal'));
    videoModal.show();
    
    // Pause video when modal is closed
    document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
        videoPlayer.pause();
        videoPlayer.currentTime = 0;
    });
}

// Handle video errors
document.getElementById('videoPlayer').addEventListener('error', function(e) {
    console.error('Video error:', e);
    const videoPlayer = document.getElementById('videoPlayer');
    const errorMsg = document.createElement('div');
    errorMsg.className = 'alert alert-danger';
    errorMsg.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error loading video. Please try again later.';
    videoPlayer.parentNode.replaceChild(errorMsg, videoPlayer);
});
</script>

<style>
.video-thumbnail {
    cursor: pointer;
    transition: transform 0.2s ease;
}

.video-thumbnail:hover {
    transform: scale(1.05);
}

.video-thumbnail .btn {
    opacity: 0.9;
    transition: opacity 0.2s ease;
}

.video-thumbnail:hover .btn {
    opacity: 1;
}
</style>
HTML;

// Include the base template
if (file_exists(__DIR__ . '/../../templates/base.php')) {
    include __DIR__ . '/../../templates/base.php';
} else {
    echo $content;
}
?> 