<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Disable HTML error display for API endpoints
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Clean any buffered output and set JSON header
ob_clean();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Customer reviews data - this could come from a database in the future
    $reviews = [
        [
            'name' => 'Sarah M.',
            'rating' => 5,
            'review' => 'Excellent Krav Maga training facility with knowledgeable instructors. Really helped build my confidence and self-defense skills!',
            'photo' => 'reviews/sk.png',
            'date' => '2024-01-15',
            'verified' => true
        ],
        [
            'name' => 'Mark C.',
            'rating' => 5,
            'review' => 'Great atmosphere and practical techniques. The instructors are patient and professional. Highly recommend for anyone serious about self-defense.',
            'photo' => 'reviews/mc.png',
            'date' => '2024-01-10',
            'verified' => true
        ],
        [
            'name' => 'Lisa G.',
            'rating' => 5,
            'review' => 'My children love the junior Krav Maga classes. They\'ve learned so much and gained confidence while having fun.',
            'photo' => 'reviews/lg.png',
            'date' => '2024-01-08',
            'verified' => true
        ],
        [
            'name' => 'David A.',
            'rating' => 5,
            'review' => 'Top-notch Krav Maga training in Colchester. The techniques are practical and the training is intense but safe.',
            'photo' => 'reviews/da.png',
            'date' => '2024-01-05',
            'verified' => true
        ],
        [
            'name' => 'Hannah A.',
            'rating' => 5,
            'review' => 'Amazing instructors who really care about your progress. The Krav Maga classes are challenging but incredibly rewarding.',
            'photo' => 'reviews/ha.png',
            'date' => '2023-12-28',
            'verified' => true
        ],
        [
            'name' => 'James S.',
            'rating' => 5,
            'review' => 'Best decision I made was joining this Krav Maga school. Excellent training, great community, and real-world applicable skills.',
            'photo' => 'reviews/js.png',
            'date' => '2023-12-22',
            'verified' => true
        ],
        [
            'name' => 'Rachel R.',
            'rating' => 5,
            'review' => 'Professional, friendly, and effective training. I feel so much more confident in my ability to defend myself.',
            'photo' => 'reviews/rr.png',
            'date' => '2023-12-20',
            'verified' => true
        ],
        [
            'name' => 'Ben M.',
            'rating' => 5,
            'review' => 'Outstanding Krav Maga instruction. The techniques are authentic and the training environment is supportive yet challenging.',
            'photo' => 'reviews/bm.png',
            'date' => '2023-12-15',
            'verified' => true
        ],
        [
            'name' => 'Emma A.',
            'rating' => 5,
            'review' => 'Joined as a complete beginner and the instructors made me feel welcome from day one. Amazing transformation in just a few months!',
            'photo' => 'reviews/ea.png',
            'date' => '2023-12-10',
            'verified' => true
        ],
        [
            'name' => 'Chris B.',
            'rating' => 5,
            'review' => 'Fantastic facility with top-quality equipment. The variety of classes keeps training interesting and challenging.',
            'photo' => 'reviews/cb.png',
            'date' => '2023-12-05',
            'verified' => true
        ],
        [
            'name' => 'Amanda K.',
            'rating' => 5,
            'review' => 'Love the supportive community here. Everyone encourages each other to improve and reach their goals.',
            'photo' => 'reviews/ak.png',
            'date' => '2023-12-01',
            'verified' => true
        ],
        [
            'name' => 'Ryan O.',
            'rating' => 5,
            'review' => 'Practical self-defense that actually works. The instructors have real-world experience and it shows in their teaching.',
            'photo' => 'reviews/ro.png',
            'date' => '2023-11-28',
            'verified' => true
        ]
    ];

    // Add some additional metadata
    $response = [
        'success' => true,
        'count' => count($reviews),
        'reviews' => $reviews,
        'average_rating' => 5.0,
        'total_reviews' => count($reviews),
        'last_updated' => date('Y-m-d H:i:s')
    ];

    // Return JSON response
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Error handling
    error_log('Reviews API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to load reviews at this time',
        'message' => 'Please try again later'
    ]);
}
?> 