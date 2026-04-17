<template>
  <div class="travel-landing-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-content">
        <h1>Discover Your Next Adventure</h1>
        <p>Explore amazing destinations with curated tours and experiences</p>
        <div class="hero-search">
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Where do you want to go?" 
            class="hero-search-input"
          />
          <button @click="handleSearch" class="btn-search-hero">Search</button>
        </div>
      </div>
      <div class="hero-image">
        <img src="/images/travel-hero.jpg" alt="Travel" />
      </div>
    </section>

    <!-- Popular Destinations -->
    <section class="destinations-section">
      <div class="section-header">
        <h2>Popular Destinations</h2>
        <a href="#" class="view-all">View All →</a>
      </div>
      <div class="destinations-grid">
        <div 
          v-for="dest in popularDestinations" 
          :key="dest.id" 
          class="destination-card"
          @click="selectDestination(dest)"
        >
          <div class="destination-image">
            <img :src="dest.image" :alt="dest.name" />
            <div class="destination-overlay">
              <h3>{{ dest.name }}</h3>
              <p>{{ dest.tours }} tours available</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Tours -->
    <section class="tours-section">
      <div class="section-header">
        <h2>Featured Tours</h2>
        <a href="#" class="view-all">View All →</a>
      </div>
      <div class="tours-grid">
        <div 
          v-for="tour in featuredTours" 
          :key="tour.id" 
          class="tour-card"
          @click="selectTour(tour)"
        >
          <div class="tour-image">
            <img :src="tour.image" :alt="tour.title" />
            <span v-if="tour.discount" class="discount-badge">-{{ tour.discount }}%</span>
          </div>
          <div class="tour-content">
            <h4>{{ tour.title }}</h4>
            <p class="tour-location">{{ tour.destination }}</p>
            <div class="tour-meta">
              <span>{{ tour.duration }} days</span>
              <span>⭐ {{ tour.rating }}</span>
            </div>
            <div class="tour-price">
              <span v-if="tour.discount" class="original-price">{{ formatCurrency(tour.originalPrice) }}</span>
              <span class="price">{{ formatCurrency(tour.price) }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Why Choose Us -->
    <section class="features-section">
      <h2>Why Choose Us</h2>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
          </div>
          <h3>Secure Booking</h3>
          <p>Your payments are protected with advanced security</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <h3>24/7 Support</h3>
          <p>Our team is always available to help you</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <h3>Best Destinations</h3>
          <p>Curated selection of top-rated destinations</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <h3>Best Prices</h3>
          <p>Competitive prices with no hidden fees</p>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials-section">
      <h2>What Our Travelers Say</h2>
      <div class="testimonials-grid">
        <div v-for="testimonial in testimonials" :key="testimonial.id" class="testimonial-card">
          <div class="testimonial-content">
            <p>"{{ testimonial.text }}"</p>
          </div>
          <div class="testimonial-author">
            <div class="author-avatar">{{ testimonial.name[0] }}</div>
            <div class="author-info">
              <h4>{{ testimonial.name }}</h4>
              <p>{{ testimonial.tour }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <h2>Ready to Start Your Adventure?</h2>
      <p>Join thousands of happy travelers who explored the world with us</p>
      <button @click="handleGetStarted" class="btn-cta">Get Started</button>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Destination {
  id: number
  name: string
  image: string
  tours: number
}

interface Tour {
  id: number
  title: string
  destination: string
  image: string
  duration: number
  rating: number
  price: number
  originalPrice: number
  discount: number
}

interface Testimonial {
  id: number
  name: string
  tour: string
  text: string
}

const searchQuery = ref('')

const popularDestinations = ref<Destination[]>([
  { id: 1, name: 'Moscow', image: '/images/moscow.jpg', tours: 45 },
  { id: 2, name: 'Saint Petersburg', image: '/images/spb.jpg', tours: 38 },
  { id: 3, name: 'Sochi', image: '/images/sochi.jpg', tours: 52 },
  { id: 4, name: 'Istanbul', image: '/images/istanbul.jpg', tours: 67 },
])

const featuredTours = ref<Tour[]>([
  {
    id: 1,
    title: 'Golden Ring Tour',
    destination: 'Moscow Region',
    image: '/images/golden-ring.jpg',
    duration: 7,
    rating: 4.8,
    price: 45000,
    originalPrice: 50000,
    discount: 10,
  },
  {
    id: 2,
    title: 'Hermitage Visit',
    destination: 'Saint Petersburg',
    image: '/images/hermitage.jpg',
    duration: 3,
    rating: 4.9,
    price: 15000,
    originalPrice: 15000,
    discount: 0,
  },
  {
    id: 3,
    title: 'Black Sea Coast',
    destination: 'Sochi',
    image: '/images/black-sea.jpg',
    duration: 5,
    rating: 4.7,
    price: 35000,
    originalPrice: 40000,
    discount: 12,
  },
  {
    id: 4,
    title: 'Istanbul Adventure',
    destination: 'Istanbul',
    image: '/images/istanbul-tour.jpg',
    duration: 6,
    rating: 4.6,
    price: 55000,
    originalPrice: 60000,
    discount: 8,
  },
])

const testimonials = ref<Testimonial[]>([
  {
    id: 1,
    name: 'Anna Petrova',
    tour: 'Golden Ring Tour',
    text: 'An unforgettable experience! The guide was knowledgeable and the itinerary was perfect.',
  },
  {
    id: 2,
    name: 'Ivan Ivanov',
    tour: 'Black Sea Coast',
    text: 'Amazing trip with excellent service. Will definitely book again!',
  },
  {
    id: 3,
    name: 'Maria Smirnova',
    tour: 'Istanbul Adventure',
    text: 'Best travel experience ever. Everything was organized perfectly.',
  },
])

const handleSearch = () => {
  // Navigate to search page with query
  console.log('Searching for:', searchQuery.value)
}

const selectDestination = (dest: Destination) => {
  // Navigate to destination page
  console.log('Selected destination:', dest.name)
}

const selectTour = (tour: Tour) => {
  // Navigate to tour details page
  console.log('Selected tour:', tour.title)
}

const handleGetStarted = () => {
  // Navigate to sign up or search page
  console.log('Get started clicked')
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}
</script>

<style scoped>
.travel-landing-page {
  min-height: 100vh;
  background: #f9fafb;
}

.hero-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  padding: 80px 40px;
  background: white;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
}

.hero-content h1 {
  font-size: 48px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 16px;
}

.hero-content p {
  font-size: 18px;
  color: #6b7280;
  margin-bottom: 32px;
}

.hero-search {
  display: flex;
  gap: 12px;
}

.hero-search-input {
  flex: 1;
  padding: 16px 20px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 16px;
}

.hero-search-input:focus {
  outline: none;
  border-color: #3b82f6;
}

.btn-search-hero {
  padding: 16px 32px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-search-hero:hover {
  background: #2563eb;
}

.hero-image img {
  width: 100%;
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.destinations-section,
.tours-section,
.features-section,
.testimonials-section {
  padding: 80px 40px;
  max-width: 1400px;
  margin: 0 auto;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
}

.section-header h2 {
  font-size: 32px;
  font-weight: 700;
  color: #111827;
}

.view-all {
  color: #3b82f6;
  text-decoration: none;
  font-weight: 600;
}

.destinations-grid,
.tours-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
}

.destination-card {
  cursor: pointer;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 0.2s;
}

.destination-card:hover {
  transform: translateY(-4px);
}

.destination-image {
  position: relative;
  height: 250px;
}

.destination-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.destination-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 20px;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
  color: white;
}

.destination-overlay h3 {
  margin: 0 0 4px 0;
  font-size: 20px;
}

.destination-overlay p {
  margin: 0;
  font-size: 14px;
  opacity: 0.9;
}

.tour-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.2s;
}

.tour-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.tour-image {
  position: relative;
  height: 200px;
}

.tour-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.discount-badge {
  position: absolute;
  top: 12px;
  right: 12px;
  background: #ef4444;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.tour-content {
  padding: 20px;
}

.tour-content h4 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
}

.tour-location {
  margin: 0 0 12px 0;
  font-size: 14px;
  color: #6b7280;
}

.tour-meta {
  display: flex;
  gap: 16px;
  margin-bottom: 12px;
  font-size: 13px;
  color: #6b7280;
}

.tour-price {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.original-price {
  text-decoration: line-through;
  color: #9ca3af;
  font-size: 14px;
}

.price {
  font-size: 20px;
  font-weight: 700;
  color: #059669;
}

.features-section h2,
.testimonials-section h2 {
  text-align: center;
  margin-bottom: 48px;
  font-size: 32px;
  font-weight: 700;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 32px;
}

.feature-card {
  text-align: center;
  padding: 32px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.feature-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  background: #eff6ff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3b82f6;
}

.feature-card h3 {
  margin: 0 0 12px 0;
  font-size: 18px;
  font-weight: 600;
}

.feature-card p {
  margin: 0;
  font-size: 14px;
  color: #6b7280;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 24px;
}

.testimonial-card {
  background: white;
  border-radius: 12px;
  padding: 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.testimonial-content {
  margin-bottom: 24px;
}

.testimonial-content p {
  font-size: 16px;
  color: #374151;
  font-style: italic;
}

.testimonial-author {
  display: flex;
  align-items: center;
  gap: 16px;
}

.author-avatar {
  width: 48px;
  height: 48px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 600;
}

.author-info h4 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
}

.author-info p {
  margin: 0;
  font-size: 14px;
  color: #6b7280;
}

.cta-section {
  text-align: center;
  padding: 100px 40px;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  color: white;
}

.cta-section h2 {
  font-size: 36px;
  font-weight: 700;
  margin-bottom: 16px;
}

.cta-section p {
  font-size: 18px;
  opacity: 0.9;
  margin-bottom: 32px;
}

.btn-cta {
  padding: 16px 48px;
  background: white;
  color: #3b82f6;
  border: none;
  border-radius: 8px;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
}

.btn-cta:hover {
  background: #f9fafb;
}

@media (max-width: 768px) {
  .hero-section {
    grid-template-columns: 1fr;
    padding: 40px 20px;
  }
  
  .hero-content h1 {
    font-size: 32px;
  }
  
  .destinations-section,
  .tours-section,
  .features-section,
  .testimonials-section {
    padding: 40px 20px;
  }
  
  .section-header {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
}
</style>
