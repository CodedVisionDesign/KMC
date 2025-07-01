document.addEventListener("DOMContentLoaded", () => {
  const glideSlides = document.querySelector(".glide__slides");

  if (!glideSlides) {
    console.error("Glide slides container not found");
    return;
  }

  // Check if Glide is available with fallback
  const glideAvailable = typeof Glide !== "undefined";
  if (!glideAvailable) {
    console.warn(
      "Glide.js is not loaded. Creating fallback carousel functionality."
    );

    // Simple fallback carousel
    window.Glide = function (selector, options) {
      const container = document.querySelector(selector);
      return {
        mount: function () {
          if (container) {
            const slides = container.querySelectorAll(".glide__slide");
            let currentSlide = 0;

            // Basic auto-rotation for fallback
            if (slides.length > 1) {
              setInterval(() => {
                slides[currentSlide].style.display = "none";
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].style.display = "block";
              }, 4000);
            }
          }
        },
      };
    };
  }

  // Initialize Glide
  const glide = new Glide("#testimonials-glide", {
    type: "carousel",
    perView: 3,
    gap: 32,
    autoplay: 3000,
    hoverpause: true,
    animationDuration: 1000,
    dragThreshold: 50,
    swipeThreshold: 80,
    breakpoints: {
      1024: { perView: 2 },
      768: { perView: 1 },
    },
  });

  // Fallback reviews data using the provided review images
  const fallbackReviews = [
    {
      name: "Sarah M.",
      rating: 5,
      review:
        "Excellent Krav Maga training facility with knowledgeable instructors. Really helped build my confidence and self-defense skills!",
      photo: "reviews/sk.png",
    },
    {
      name: "Mark C.",
      rating: 5,
      review:
        "Great atmosphere and practical techniques. The instructors are patient and professional. Highly recommend for anyone serious about self-defense.",
      photo: "reviews/mc.png",
    },
    {
      name: "Lisa G.",
      rating: 5,
      review:
        "My children love the junior Krav Maga classes. They've learned so much and gained confidence while having fun.",
      photo: "reviews/lg.png",
    },
    {
      name: "David A.",
      rating: 5,
      review:
        "Top-notch Krav Maga training in Colchester. The techniques are practical and the training is intense but safe.",
      photo: "reviews/da.png",
    },
    {
      name: "Hannah A.",
      rating: 5,
      review:
        "Amazing instructors who really care about your progress. The Krav Maga classes are challenging but incredibly rewarding.",
      photo: "reviews/ha.png",
    },
    {
      name: "James S.",
      rating: 5,
      review:
        "Best decision I made was joining this Krav Maga school. Excellent training, great community, and real-world applicable skills.",
      photo: "reviews/js.png",
    },
    {
      name: "Rachel R.",
      rating: 5,
      review:
        "Professional, friendly, and effective training. I feel so much more confident in my ability to defend myself.",
      photo: "reviews/rr.png",
    },
    {
      name: "Ben M.",
      rating: 5,
      review:
        "Outstanding Krav Maga instruction. The techniques are authentic and the training environment is supportive yet challenging.",
      photo: "reviews/bm.png",
    },
  ];

  function renderReviews(reviews) {
    // Clear existing content
    glideSlides.innerHTML = "";

    // Validate data
    if (!Array.isArray(reviews) || reviews.length === 0) {
      throw new Error("Invalid reviews data");
    }

    // Shuffle reviews for variety
    const shuffledReviews = reviews.sort(() => 0.5 - Math.random());

    shuffledReviews.forEach((review) => {
      const slide = document.createElement("li");
      slide.className = "glide__slide";

      // Create star rating
      const stars = review.rating
        ? "★".repeat(Math.min(review.rating, 5))
        : "★★★★★";

      slide.innerHTML = `
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body text-center p-4">
            <div class="mb-3">
              <img src="https://www.google.com/favicon.ico" alt="Google Review" class="me-2" style="width: 20px; height: 20px;">
              <span class="text-muted small">Google Review</span>
            </div>
            <div class="rating mb-3" style="color: #ffc107; font-size: 1.4em;">${stars}</div>
            <p class="review-text text-muted mb-4" style="font-style: italic;">"${
              review.review || "Excellent service and training!"
            }"</p>
            <div class="reviewer-info d-flex align-items-center justify-content-center">
              <img src="../assets/${
                review.photo ? review.photo : "reviews/sk.png"
              }" alt="${review.name || "Reviewer"}" 
                   class="reviewer-image me-3" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #e9ecef;"
                   onerror="this.src='../assets/images/video-placeholder.jpg';">
              <div>
                <h6 class="mb-0 text-primary">${
                  review.name || "Happy Customer"
                }</h6>
                <small class="text-muted">Verified Member</small>
              </div>
            </div>
          </div>
        </div>
      `;
      glideSlides.appendChild(slide);
    });

    // Mount Glide
    try {
      glide.mount();
    } catch (error) {
      console.error("Error mounting Glide:", error);
      // If Glide fails, still show the reviews in a static layout
      glideSlides.parentElement.parentElement.style.overflow = "hidden";
    }
  }

  function showReviewsError() {
    glideSlides.innerHTML = `
      <li class="glide__slide">
        <div class="card border-0 shadow-sm">
          <div class="card-body text-center p-4">
            <i class="fas fa-star text-warning mb-3" style="font-size: 2rem;"></i>
            <h5>Customer Reviews</h5>
            <p class="text-muted">We're experiencing technical difficulties loading our reviews.</p>
            <p>Visit our <a href="https://search.google.com/local/reviews?placeid=ChIJx0XryPwF2UcRnQYv0rLcmRs" target="_blank" class="text-decoration-none">Google Reviews</a> to see what our customers say!</p>
            <button onclick="location.reload()" class="btn btn-primary btn-sm mt-2">Try Again</button>
          </div>
        </div>
      </li>
    `;
    try {
      glide.mount();
    } catch (error) {
      console.error("Error mounting Glide for error state:", error);
    }
  }

  // Fetch and populate reviews with retry (optional - falls back to static reviews)
  async function loadReviews(attempts = 3) {
    try {
      const response = await fetch("api/reviews.php");
      if (!response.ok) {
        throw new Error(`Network response was not ok: ${response.statusText}`);
      }
      const reviews = await response.json();
      return reviews;
    } catch (error) {
      if (attempts > 1) {
        console.warn(`Retrying fetch (${attempts - 1} attempts left)...`);
        return loadReviews(attempts - 1);
      }
      throw error;
    }
  }

  // Load reviews with timeout - fallback to static reviews if API fails
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 5000);

  loadReviews()
    .then((reviews) => {
      clearTimeout(timeoutId);
      console.log("Loaded reviews from API");
      renderReviews(reviews);
    })
    .catch((error) => {
      clearTimeout(timeoutId);
      console.log("API failed, using fallback reviews:", error.message);

      // Use fallback data (the static reviews)
      try {
        renderReviews(fallbackReviews);
      } catch (fallbackError) {
        console.error("Fallback reviews failed:", fallbackError);
        showReviewsError();
      }
    });
});
