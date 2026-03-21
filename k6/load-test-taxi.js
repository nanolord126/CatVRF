import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Rate, Counter, Gauge } from 'k6/metrics';

const rideCreationDuration = new Trend('taxi_ride_creation_duration');
const surgePricingDuration = new Trend('taxi_surge_pricing_duration');
const locationUpdateDuration = new Trend('taxi_location_update_duration');
const errorRate = new Rate('taxi_errors');
const activeRides = new Gauge('taxi_active_rides');
const requestCounter = new Counter('taxi_requests');

export const options = {
  stages: [
    { duration: '1m', target: 30 },   // Initial drivers coming online
    { duration: '10m', target: 100 }, // Peak hours - multiple concurrent rides
    { duration: '5m', target: 50 },   // Winding down
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.15'],
    'taxi_errors': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const drivers = Array.from({ length: 200 }, (_, i) => i + 201);
const passengers = Array.from({ length: 500 }, (_, i) => i + 2001);
const zones = Array.from({ length: 10 }, (_, i) => i + 501);

// Mock Moscow locations for variety
const locations = [
  { lat: 55.7558, lng: 37.6173 }, // Red Square
  { lat: 55.7505, lng: 37.6175 }, // Kremlin
  { lat: 55.7614, lng: 37.5779 }, // Belorusskaya
  { lat: 55.7447, lng: 37.6727 }, // Komsomolskaya
  { lat: 55.6761, lng: 37.6412 }, // Dmitrovskoe
];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function getRandomLocation() {
  return getRandomElement(locations);
}

function addNoise(location, radius = 0.01) {
  return {
    lat: location.lat + (Math.random() - 0.5) * radius,
    lng: location.lng + (Math.random() - 0.5) * radius,
  };
}

let onlineDriverCount = 0;

export default function () {
  const driverId = getRandomElement(drivers);
  const passengerId = getRandomElement(passengers);
  const zoneId = getRandomElement(zones);
  const token = `taxi-token-${driverId}`;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
  };

  // Simulate driver coming online
  if (Math.random() < 0.1) {
    group('Driver Goes Online', () => {
      let response = http.patch(`${BASE_URL}/api/drivers/${driverId}/status`, {
        is_online: true,
        location: getRandomLocation(),
      }, { headers, tags: { name: 'DriverOnline' } });

      check(response, {
        'driver online status is 200': (r) => r.status === 200,
      }) || errorRate.add(1);

      onlineDriverCount++;
      activeRides.set(onlineDriverCount);
      requestCounter.add(1);
    });

    sleep(0.5);
  }

  group('Passenger Requests Ride', () => {
    const pickupLocation = getRandomLocation();
    const dropoffLocation = getRandomLocation();

    let start = new Date();

    let response = http.post(`${BASE_URL}/api/rides`, {
      passenger_id: passengerId,
      pickup: pickupLocation,
      dropoff: dropoffLocation,
      ride_class: ['economy', 'comfort', 'business'][Math.floor(Math.random() * 3)],
    }, { headers, tags: { name: 'RequestRide' } });

    rideCreationDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'ride request status is 201': (r) => r.status === 201,
      'ride has ID': (r) => r.json('id') !== null,
      'ride status is searching': (r) => r.json('status') === 'searching',
      'estimated price provided': (r) => r.json('estimated_price') > 0,
    }) || errorRate.add(1);

    const rideId = response.json('id');

    sleep(0.5);

    // Get surge pricing for this zone
    group('Check Surge Pricing', () => {
      let surgePricingStart = new Date();

      let surgeResponse = http.get(`${BASE_URL}/api/surge-zones/${zoneId}`, {
        headers,
        tags: { name: 'CheckSurge' },
      });

      surgePricingDuration.add(new Date() - surgePricingStart);
      requestCounter.add(1);

      check(surgeResponse, {
        'surge zone status is 200': (r) => r.status === 200,
        'surge multiplier is >= 1': (r) => r.json('surge_multiplier') >= 1.0,
        'surge multiplier is <= 3': (r) => r.json('surge_multiplier') <= 3.0,
      }) || errorRate.add(1);
    });

    sleep(0.3);

    // Simulate driver accepting ride
    if (Math.random() < 0.7) {
      group('Driver Accepts Ride', () => {
        let acceptResponse = http.post(`${BASE_URL}/api/rides/${rideId}/assign`, {
          driver_id: driverId,
        }, { headers, tags: { name: 'DriverAccepts' } });

        check(acceptResponse, {
          'accept ride status is 200': (r) => r.status === 200,
          'ride status is assigned': (r) => r.json('status') === 'assigned',
        }) || errorRate.add(1);

        requestCounter.add(1);
      });

      sleep(1);

      // Simulate driver en route updates
      group('Driver Location Updates', () => {
        for (let i = 0; i < 5; i++) {
          let locationUpdateStart = new Date();

          let updateResponse = http.patch(`${BASE_URL}/api/drivers/${driverId}/location`, {
            latitude: addNoise(getRandomLocation()).lat,
            longitude: addNoise(getRandomLocation()).lng,
            timestamp: new Date().toISOString(),
          }, { headers, tags: { name: 'LocationUpdate' } });

          locationUpdateDuration.add(new Date() - locationUpdateStart);
          requestCounter.add(1);

          check(updateResponse, {
            'location update status is 200': (r) => r.status === 200,
          }) || errorRate.add(1);

          sleep(0.2); // Simulate location updates every ~0.2s
        }
      });

      sleep(0.5);

      // Complete ride
      group('Ride Completion', () => {
        let completeResponse = http.patch(`${BASE_URL}/api/rides/${rideId}`, {
          status: 'completed',
          final_fare: Math.floor(Math.random() * 5000) + 1000,
        }, { headers, tags: { name: 'CompleteRide' } });

        check(completeResponse, {
          'complete ride status is 200': (r) => r.status === 200,
          'ride status is completed': (r) => r.json('status') === 'completed',
        }) || errorRate.add(1);

        requestCounter.add(1);

        activeRides.set(onlineDriverCount - 1);
      });

      sleep(0.5);

      // Rate exchange
      group('Rate Exchange', () => {
        // Passenger rates driver
        let passengerRateResponse = http.post(`${BASE_URL}/api/rides/${rideId}/rate`, {
          rating: Math.floor(Math.random() * 5) + 1,
          comment: 'Good ride!',
        }, { headers, tags: { name: 'PassengerRate' } });

        check(passengerRateResponse, {
          'passenger rate status is 200 or 404': (r) => r.status === 200 || r.status === 404,
        });

        requestCounter.add(1);
      });
    }
  });

  sleep(2);
}
