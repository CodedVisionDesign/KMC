<?php
require_once 'includes/admin_common.php';

// Handle file uploads and AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle file upload
    if (isset($_FILES['video_file']) && !isset($_POST['action'])) {
        $uploadDir = '../public/uploads/videos/';
        $thumbnailDir = '../public/uploads/thumbnails/';
        
        // Create directories if they don't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }
        
        $file = $_FILES['video_file'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $seriesId = !empty($_POST['series_id']) ? (int)$_POST['series_id'] : null;
        $sortOrder = (int)$_POST['sort_order'] ?? 0;
        
        // Validate file
        $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error'] = 'Invalid file type. Please upload MP4, AVI, MOV, or WMV files only.';
            header('Location: videos.php');
            exit;
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Generate thumbnail (placeholder for now)
            $thumbnailName = pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
            $thumbnailPath = $thumbnailDir . $thumbnailName;
            
            // Create a simple placeholder thumbnail (you might want to use FFmpeg for real thumbnails)
            copy('../public/assets/images/video-placeholder.jpg', $thumbnailPath);
            
            // Save to database
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO videos (title, description, file_path, thumbnail_path, series_id, sort_order, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([$title, $description, $fileName, $thumbnailName, $seriesId, $sortOrder]);
                
                $_SESSION['success'] = 'Video uploaded successfully!';
            } catch (PDOException $e) {
                error_log('Error saving video: ' . $e->getMessage());
                $_SESSION['error'] = 'Error saving video to database.';
            }
        } else {
            $_SESSION['error'] = 'Error uploading file.';
        }
        
        header('Location: videos.php');
        exit;
    }
    
    // Handle AJAX requests
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        
        try {
            switch ($_POST['action']) {
                case 'delete_video':
                    $videoId = (int)$_POST['video_id'];
                    
                    // Get file paths before deleting from database
                    $stmt = $pdo->prepare("SELECT file_path, thumbnail_path FROM videos WHERE id = ?");
                    $stmt->execute([$videoId]);
                    $video = $stmt->fetch();
                    
                    if ($video) {
                        // Delete from database
                        $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
                        $stmt->execute([$videoId]);
                        
                        // Delete files
                        $videoFile = '../public/uploads/videos/' . $video['file_path'];
                        $thumbnailFile = '../public/uploads/thumbnails/' . $video['thumbnail_path'];
                        
                        if (file_exists($videoFile)) {
                            unlink($videoFile);
                        }
                        if (file_exists($thumbnailFile)) {
                            unlink($thumbnailFile);
                        }
                        
                        echo json_encode(['success' => true, 'message' => 'Video deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Video not found']);
                    }
                    break;
                    
                case 'update_video':
                    $videoId = (int)$_POST['video_id'];
                    $title = $_POST['title'] ?? '';
                    $description = $_POST['description'] ?? '';
                    $seriesId = !empty($_POST['series_id']) ? (int)$_POST['series_id'] : null;
                    $sortOrder = (int)$_POST['sort_order'] ?? 0;
                    $status = $_POST['status'] ?? 'active';
                    
                    $stmt = $pdo->prepare("
                        UPDATE videos 
                        SET title = ?, description = ?, series_id = ?, sort_order = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $description, $seriesId, $sortOrder, $status, $videoId]);
                    
                    echo json_encode(['success' => true, 'message' => 'Video updated successfully']);
                    break;
                    
                case 'create_series':
                    $title = $_POST['series_name'] ?? '';
                    $description = $_POST['series_description'] ?? '';
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO video_series (title, description, created_at)
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$title, $description]);
                    
                    echo json_encode(['success' => true, 'message' => 'Series created successfully']);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
            }
        } catch (Exception $e) {
            error_log('Video management error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

// Handle success/error messages
$message = '';
if (isset($_SESSION['success'])) {
    $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['error']);
}

$activeTab = $_GET['tab'] ?? 'videos';

// Fetch videos with series information
$videos = [];
try {
    $stmt = $pdo->query("
        SELECT v.*, vs.title as series_name
        FROM videos v
        LEFT JOIN video_series vs ON v.series_id = vs.id
        ORDER BY vs.title, v.sort_order, v.created_at DESC
    ");
    $videos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching videos: ' . $e->getMessage());
}

// Fetch video series
$videoSeries = [];
try {
    $stmt = $pdo->query("
        SELECT vs.*, COUNT(v.id) as video_count
        FROM video_series vs
        LEFT JOIN videos v ON vs.id = v.series_id
        GROUP BY vs.id
        ORDER BY vs.title
    ");
    $videoSeries = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching video series: ' . $e->getMessage());
}

// Get statistics
$totalVideos = count($videos);
$activeVideos = count(array_filter($videos, function($v) { return $v['status'] === 'active'; }));
$totalSeries = count($videoSeries);

$content = <<<HTML
{$message}

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-video me-2"></i>Video Management</h1>
    <div>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-2"></i>Upload Video
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#seriesModal">
            <i class="fas fa-plus me-2"></i>Create Series
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Total Videos</h5>
                        <h2>{$totalVideos}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-video fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Active Videos</h5>
                        <h2>{$activeVideos}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-play fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Series</h5>
                        <h2>{$totalSeries}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-list fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" id="videoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'videos' ? 'active' : ''; ?>" id="videos-tab" data-bs-toggle="tab" data-bs-target="#videos" type="button" role="tab">
            <i class="fas fa-video me-2"></i>All Videos
            <span class="badge bg-primary ms-2">{$totalVideos}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $activeTab === 'series' ? 'active' : ''; ?>" id="series-tab" data-bs-toggle="tab" data-bs-target="#series" type="button" role="tab">
            <i class="fas fa-list me-2"></i>Video Series
            <span class="badge bg-info ms-2">{$totalSeries}</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="videoTabContent">
    <!-- Videos Tab -->
    <div class="tab-pane fade <?php echo $activeTab === 'videos' ? 'show active' : ''; ?>" id="videos" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-video me-2"></i>Video Library</h5>
                <div class="d-flex align-items-center">
                    <label for="seriesFilter" class="form-label me-2 mb-0">Filter by Series:</label>
                    <select class="form-select form-select-sm" id="seriesFilter" style="width: auto;">
                        <option value="">All Series</option>
HTML;

foreach ($videoSeries as $series) {
    $content .= <<<HTML
                        <option value="{$series['id']}">{$series['title']}</option>
HTML;
}

$content .= <<<HTML
                    </select>
                </div>
            </div>
            <div class="card-body">
HTML;

if (empty($videos)) {
    $content .= <<<HTML
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No videos uploaded yet. Click "Upload Video" to get started!
                </div>
HTML;
} else {
    $content .= <<<HTML
                <div class="row" id="videoGrid">
HTML;

    foreach ($videos as $video) {
        $statusClass = $video['status'] === 'active' ? 'success' : 'secondary';
        $statusText = ucfirst($video['status']);
        $seriesName = $video['series_name'] ?? 'No Series';
        $thumbnailPath = '../public/uploads/thumbnails/' . $video['thumbnail_path'];
        $thumbnailUrl = file_exists($thumbnailPath) ? $thumbnailPath : '../public/assets/images/video-placeholder.jpg';
        
        $content .= <<<HTML
                    <div class="col-md-6 col-lg-4 mb-4 video-item" data-series="{$video['series_id']}">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="{$thumbnailUrl}" class="card-img-top" alt="Video thumbnail" style="height: 200px; object-fit: cover;">
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge bg-{$statusClass}">{$statusText}</span>
                                </div>
                                <div class="position-absolute top-50 start-50 translate-middle">
                                    <a href="../public/uploads/videos/{$video['file_path']}" target="_blank" class="btn btn-primary btn-lg rounded-circle">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title">{$video['title']}</h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Series: {$seriesName}<br>
                                        Order: {$video['sort_order']}<br>
                                        Created: {$date('M j, Y', strtotime($video['created_at']))}
                                    </small>
                                </p>
                                <p class="card-text">{$video['description']}</p>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100" role="group">
                                    <button class="btn btn-outline-primary btn-sm" onclick="editVideo({$video['id']}, '{$addslashes($video['title'])}', '{$addslashes($video['description'])}', {$video['series_id']}, {$video['sort_order']}, '{$video['status']}')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteVideo({$video['id']}, '{$addslashes($video['title'])}')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
HTML;
    }
    
    $content .= <<<HTML
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>

    <!-- Series Tab -->
    <div class="tab-pane fade <?php echo $activeTab === 'series' ? 'show active' : ''; ?>" id="series" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list me-2"></i>Video Series</h5>
            </div>
            <div class="card-body">
HTML;

if (empty($videoSeries)) {
    $content .= <<<HTML
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No video series created yet. Click "Create Series" to organize your videos!
                </div>
HTML;
} else {
    $content .= <<<HTML
                <div class="row">
HTML;

    foreach ($videoSeries as $series) {
        $content .= <<<HTML
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">{$series['title']}</h6>
                                <p class="card-text">{$series['description']}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">{$series['video_count']} videos</small>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
HTML;
    }
    
    $content .= <<<HTML
                </div>
HTML;
}

$content .= <<<HTML
            </div>
        </div>
    </div>
</div>

<!-- Upload Video Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload New Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Video Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="series_id" class="form-label">Video Series (Optional)</label>
                                <select class="form-select" id="series_id" name="series_id">
                                    <option value="">No Series</option>
HTML;

foreach ($videoSeries as $series) {
    $content .= <<<HTML
                                    <option value="{$series['id']}">{$series['title']}</option>
HTML;
}

$content .= <<<HTML
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="video_file" class="form-label">Video File</label>
                        <input type="file" class="form-control" id="video_file" name="video_file" accept="video/*" required>
                        <div class="form-text">Supported formats: MP4, AVI, MOV, WMV. Max file size: 100MB</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-2"></i>Upload Video
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Video Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editVideoId" name="video_id">
                    <div class="mb-3">
                        <label for="editTitle" class="form-label">Video Title</label>
                        <input type="text" class="form-control" id="editTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editSeriesId" class="form-label">Video Series</label>
                                <select class="form-select" id="editSeriesId" name="series_id">
                                    <option value="">No Series</option>
HTML;

foreach ($videoSeries as $series) {
    $content .= <<<HTML
                                    <option value="{$series['id']}">{$series['title']}</option>
HTML;
}

$content .= <<<HTML
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editSortOrder" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="editSortOrder" name="sort_order" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-select" id="editStatus" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateVideo()">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Series Modal -->
<div class="modal fade" id="seriesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Video Series</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="seriesForm">
                    <div class="mb-3">
                        <label for="seriesName" class="form-label">Series Name</label>
                        <input type="text" class="form-control" id="seriesName" name="series_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="seriesDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="seriesDescription" name="series_description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createSeries()">
                    <i class="fas fa-plus me-2"></i>Create Series
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Filter videos by series
document.getElementById('seriesFilter').addEventListener('change', function() {
    const selectedSeries = this.value;
    const videoItems = document.querySelectorAll('.video-item');
    
    videoItems.forEach(item => {
        if (selectedSeries === '' || item.dataset.series === selectedSeries) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

function editVideo(id, title, description, seriesId, sortOrder, status) {
    document.getElementById('editVideoId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editDescription').value = description;
    document.getElementById('editSeriesId').value = seriesId || '';
    document.getElementById('editSortOrder').value = sortOrder;
    document.getElementById('editStatus').value = status;
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function updateVideo() {
    const formData = new FormData(document.getElementById('editForm'));
    formData.append('action', 'update_video');
    
    fetch('videos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating video: ' + error);
    });
}

function deleteVideo(id, title) {
    if (confirm('Are you sure you want to delete the video "' + title + '"? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'delete_video');
        formData.append('video_id', id);
        
        fetch('videos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting video: ' + error);
        });
    }
}

function createSeries() {
    const formData = new FormData(document.getElementById('seriesForm'));
    formData.append('action', 'create_series');
    
    fetch('videos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error creating series: ' + error);
    });
}

// Set active tab based on URL parameter
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        const tabElement = document.getElementById(tab + '-tab');
        if (tabElement) {
            tabElement.click();
        }
    }
});
</script>
HTML;

// Render the admin page
renderAdminPage($content, [
    'pageDescription' => 'Upload, manage, and organize video content'
]); 