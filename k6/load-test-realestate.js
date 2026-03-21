import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter, Gauge } from 'k6/metrics';

const listingCreationDuration = new Trend('realestate_listing_creation_duration');
const viewingBookingDuration = new Trend('realestate_viewing_booking_duration');
const analyticsQueryDuration = new Trend('realestate_analytics_query_duration');
const offerCounter = new Counter('realestate_offers_total');
const activeListings = new Gauge('realestate_active_listings');

export const options = {
  stages: [
    { duration: '2m', target: 15 },   // Agents browsing & listing
    { duration: '10m', target: 50 },  // Peak browsing
    { duration: '5m', target: 20 },   // Cool down
  ],
  thresholds: {
    'http_req_duration': ['p(95)<600', 'p(99)<1200'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const agents = Array.from({ length: 20 }, (_, i) => i + 501);
const customers = Array.from({ length: 500 }, (_, i) => i + 3001);
const properties = Array.from({ length: 100 }, (_, i) => i + 401);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function generatePropertyData() {
  const types = ['apartment', 'house', 'commercial', 'land'];
  const cities = ['moscow', 'spb', 'ekb'];
  
  return {
    type: getRandomElement(types),
    address: `City Center, Street ${Math.floor(Math.random() * 100)}`,
    area: Math.floor(Math.random() * 300) + 50,
    rooms: Math.floor(Math.random() * 5) + 1,
    floor: Math.floor(Math.random() * 20) + 1,
    total_floors: Math.floor(Math.random() * 30) + 10,
    build_year: Math.floor(Math.random() * 20) + 2000,
    city: getRandomElement(cities),
  };
}

export default function () {
  const agentId = getRandomElement(agents);
  const customerId = getRandomElement(customers);
  const propertyId = getRandomElement(properties);
  const token = `realestate-token-${agentId}`;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': agentId.toString(),
  };

  // Agent Creates Listing
  if (Math.random() < 0.2) {
    group('Agent Creates Property Listing', () => {
      const propertyData = generatePropertyData();

      let start = new Date();

      let listingResponse = http.post(`${BASE_URL}/api/properties`, {
        ...propertyData,
        sale_price: Math.floor(Math.random() * 1000000000) + 500000000,
        commission_percent: Math.random() * 2 + 1,
        condition: ['new', 'renovated', 'needs_renovation'][Math.floor(Math.random() * 3)],
      }, { headers, tags: { name: 'CreateListing' } });

      listingCreationDuration.add(new Date() - start);
      activeListings.set(activeListings.value() + 1);

      check(listingResponse, {
        'create listing status is 201': (r) => r.status === 201,
        'listing has ID': (r) => r.json('id') !== null,
        'listing is active': (r) => r.json('status') === 'active',
      });
    });

    sleep(1);
  }

  // Customer Searches & Filters
  group('Customer Searches Properties', () => {
    let searchStart = new Date();

    let searchResponse = http.get(`${BASE_URL}/api/properties`, {
      headers: {
        'Content-Type': 'application/json',
      },
      queryParams: {
        city: ['moscow', 'spb', 'ekb'][Math.floor(Math.random() * 3)],
        type: ['apartment', 'house'][Math.floor(Math.random() * 2)],
        min_price: Math.floor(Math.random() * 500000000),
        max_price: Math.floor(Math.random() * 1500000000) + 500000000,
        min_area: Math.floor(Math.random() * 100),
        max_area: Math.floor(Math.random() * 300) + 100,
        page: 1,
        limit: 50,
      },
      tags: { name: 'SearchProperties' },
    });

    check(searchResponse, {
      'search status is 200': (r) => r.status === 200,
      'search returns data': (r) => Array.isArray(r.json('data')) && r.json('data').length > 0,
      'pagination included': (r) => r.json('pagination') !== null,
    });

    sleep(2); // Simulate browsing results
  });

  // Viewing Appointment Booking
  group('Book Viewing Appointment', () => {
    let viewingStart = new Date();

    let viewingResponse = http.post(`${BASE_URL}/api/viewings`, {
      property_id: propertyId,
      client_id: customerId,
      datetime: new Date(Date.now() + 86400000).toISOString(), // Tomorrow
      agent_id: agentId,
      notes: 'Very interested',
    }, { headers, tags: { name: 'BookViewing' } });

    viewingBookingDuration.add(new Date() - viewingStart);

    check(viewingResponse, {
      'viewing booking is 201': (r) => r.status === 201,
      'viewing has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);

  // Analytics Queries
  if (Math.random() < 0.3) {
    group('Price Analytics & Market Trends', () => {
      let analyticsStart = new Date();

      let analyticsResponse = http.get(`${BASE_URL}/api/properties/analytics/price-per-sqm`, {
        headers: {
          'Content-Type': 'application/json',
        },
        queryParams: {
          city: 'moscow',
          property_type: 'apartment',
        },
        tags: { name: 'AnalyticsQuery' },
      });

      analyticsQueryDuration.add(new Date() - analyticsStart);

      check(analyticsResponse, {
        'analytics query is 200': (r) => r.status === 200,
        'analytics has price data': (r) => r.json('average_price') > 0,
        'price trends available': (r) => r.json('price_trends') !== null,
      });
    });

    sleep(0.5);
  }

  // Make Offer (for interested customers)
  if (Math.random() < 0.1) {
    group('Customer Makes Offer', () => {
      const offerPrice = Math.floor(Math.random() * 900000000) + 600000000;

      let offerResponse = http.post(`${BASE_URL}/api/properties/${propertyId}/make-offer`, {
        offered_price: offerPrice,
        comment: 'Ready to negotiate',
      }, { headers, tags: { name: 'MakeOffer' } });

      offerCounter.add(1);

      check(offerResponse, {
        'offer creation is 201 or 200': (r) => r.status === 201 || r.status === 200,
        'offer has ID': (r) => r.json('offer_id') !== null || r.json('id') !== null,
      });

      sleep(0.5);

      // Check offer negotiation (seller response)
      const offerId = offerResponse.json('offer_id') || offerResponse.json('id');
      if (offerId) {
        let negotiationResponse = http.get(`${BASE_URL}/api/offers/${offerId}`, {
          headers,
          tags: { name: 'CheckOffer' },
        });

        check(negotiationResponse, {
          'offer check is 200': (r) => r.status === 200,
          'offer has status': (r) => ['pending', 'accepted', 'rejected', 'counter'].includes(r.json('status')),
        });
      }
    });
  }

  sleep(2);
}
