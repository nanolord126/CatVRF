import axios from 'axios';

const BEAUTY_API_BASE = '/api/beauty';

export interface MasterMatchingRequest {
  user_id: number;
  photo: File;
  service_type?: string;
  preferred_gender?: string;
  max_distance?: number;
  min_rating?: number;
  price_min?: number;
  price_max?: number;
  inn?: string;
  business_card_id?: number;
}

export interface DynamicPricingRequest {
  master_id: number;
  service_id: number;
  time_slot?: string;
  base_price?: number;
  inn?: string;
  business_card_id?: number;
}

export interface VideoCallRequest {
  user_id: number;
  master_id: number;
  scheduled_for?: string;
  duration_minutes?: number;
}

export interface LoyaltyActionRequest {
  user_id: number;
  action: string;
  appointment_id?: number;
  referral_code?: string;
}

export interface FraudDetectionRequest {
  user_id: number;
  action: string;
  appointment_id?: number;
  master_id?: number;
  amount?: number;
}

class BeautyService {
  private tenantId: string;
  private correlationId: string;

  constructor() {
    this.tenantId = localStorage.getItem('tenant_id') || '1';
    this.correlationId = crypto.randomUUID();
  }

  private getHeaders() {
    return {
      'X-Tenant-ID': this.tenantId,
      'X-Correlation-ID': this.correlationId,
      'Content-Type': 'application/json',
    };
  }

  // Master Matching by Photo
  async matchMastersByPhoto(data: MasterMatchingRequest) {
    const formData = new FormData();
    formData.append('user_id', data.user_id.toString());
    formData.append('photo', data.photo);
    
    if (data.service_type) formData.append('service_type', data.service_type);
    if (data.preferred_gender) formData.append('preferred_gender', data.preferred_gender);
    if (data.max_distance) formData.append('max_distance', data.max_distance.toString());
    if (data.min_rating) formData.append('min_rating', data.min_rating.toString());
    if (data.price_min) formData.append('price_min', data.price_min.toString());
    if (data.price_max) formData.append('price_max', data.price_max.toString());
    if (data.inn) formData.append('inn', data.inn);
    if (data.business_card_id) formData.append('business_card_id', data.business_card_id.toString());

    return axios.post(`${BEAUTY_API_BASE}/masters/match-by-photo`, formData, {
      headers: {
        'X-Tenant-ID': this.tenantId,
        'X-Correlation-ID': this.correlationId,
      },
    });
  }

  async getMatchHistory(userId: number) {
    return axios.get(`${BEAUTY_API_BASE}/masters/match-history`, {
      params: { user_id: userId },
      headers: this.getHeaders(),
    });
  }

  // Dynamic Pricing
  async calculateDynamicPricing(data: DynamicPricingRequest) {
    return axios.post(`${BEAUTY_API_BASE}/pricing/calculate`, data, {
      headers: this.getHeaders(),
    });
  }

  async getPriceHistory(serviceId: number) {
    return axios.get(`${BEAUTY_API_BASE}/pricing/history`, {
      params: { service_id: serviceId },
      headers: this.getHeaders(),
    });
  }

  // Video Calls
  async initiateVideoCall(data: VideoCallRequest) {
    return axios.post(`${BEAUTY_API_BASE}/video-calls/initiate`, data, {
      headers: this.getHeaders(),
    });
  }

  async endVideoCall(callId: string, durationSeconds: number, reason: string = 'user_ended') {
    return axios.post(`${BEAUTY_API_BASE}/video-calls/end`, {
      call_id: callId,
      duration_seconds: durationSeconds,
      reason,
    }, {
      headers: this.getHeaders(),
    });
  }

  // Loyalty
  async processLoyaltyAction(data: LoyaltyActionRequest) {
    return axios.post(`${BEAUTY_API_BASE}/loyalty/action`, data, {
      headers: this.getHeaders(),
    });
  }

  async getLoyaltyStatus(userId: number) {
    return axios.get(`${BEAUTY_API_BASE}/loyalty/status`, {
      params: { user_id: userId },
      headers: this.getHeaders(),
    });
  }

  async generateReferralCode(userId: number) {
    return axios.post(`${BEAUTY_API_BASE}/loyalty/referral/generate`, {
      user_id: userId,
    }, {
      headers: this.getHeaders(),
    });
  }

  // Fraud Detection
  async analyzeFraud(data: FraudDetectionRequest) {
    return axios.post(`${BEAUTY_API_BASE}/fraud/analyze`, data, {
      headers: this.getHeaders(),
    });
  }

  async addSuspiciousIP(ipAddress: string) {
    return axios.post(`${BEAUTY_API_BASE}/fraud/suspicious-ip`, {
      ip_address: ipAddress,
    }, {
      headers: this.getHeaders(),
    });
  }

  async recordFailedPayment(userId: number) {
    return axios.post(`${BEAUTY_API_BASE}/fraud/failed-payment`, {
      user_id: userId,
    }, {
      headers: this.getHeaders(),
    });
  }

  // Common Beauty endpoints
  async getMasters(params?: any) {
    return axios.get(`${BEAUTY_API_BASE}/masters`, {
      params,
      headers: this.getHeaders(),
    });
  }

  async getMaster(masterId: number) {
    return axios.get(`${BEAUTY_API_BASE}/masters/${masterId}`, {
      headers: this.getHeaders(),
    });
  }

  async getSalons(params?: any) {
    return axios.get(`${BEAUTY_API_BASE}/salons`, {
      params,
      headers: this.getHeaders(),
    });
  }

  async getSalon(salonId: number) {
    return axios.get(`${BEAUTY_API_BASE}/salons/${salonId}`, {
      headers: this.getHeaders(),
    });
  }

  async getServices(params?: any) {
    return axios.get(`${BEAUTY_API_BASE}/services`, {
      params,
      headers: this.getHeaders(),
    });
  }

  async getService(serviceId: number) {
    return axios.get(`${BEAUTY_API_BASE}/services/${serviceId}`, {
      headers: this.getHeaders(),
    });
  }

  async getAppointments(params?: any) {
    return axios.get(`${BEAUTY_API_BASE}/appointments`, {
      params,
      headers: this.getHeaders(),
    });
  }

  async createAppointment(data: any) {
    return axios.post(`${BEAUTY_API_BASE}/appointments`, data, {
      headers: this.getHeaders(),
    });
  }

  async cancelAppointment(appointmentId: number) {
    return axios.post(`${BEAUTY_API_BASE}/appointments/${appointmentId}/cancel`, {}, {
      headers: this.getHeaders(),
    });
  }
}

export default new BeautyService();
