/**
 * CatFloat Rewards API Service
 * 
 * API client for CatFloat Rewards system with 15-day Smart Hold.
 */

export interface CatFloatBalance {
  locked_balance: number;
  available_balance: number;
  total_balance: number;
}

export interface BonusBatch {
  id: number;
  original_amount: number;
  remaining_locked: number;
  unlocked_amount: number;
  unlock_percentage: number;
  vested_until: string;
  days_remaining: number;
  source: string;
  is_fully_vested: boolean;
}

export interface StreakInfo {
  streak_days: number;
  streak_level: string;
  multiplier: number;
  color: string;
  is_premium: boolean;
  acceleration_bonus: number;
  next_level: string | null;
  days_to_next_level: number | null;
}

export interface YieldTransaction {
  date: string;
  total_float: number;
  user_yield: number;
  platform_yield: number;
  yield_rate: number;
}

/**
 * Float Yield Summary - user's float yield information
 * ВАЖНО: По законам РФ float yield - только бонусы, не деньги
 */
export interface FloatYieldSummary {
  total_locked: number;
  today_yield: number;
  monthly_yield: number;
  expected_yield: number;
  user_rate: number;
  platform_rate: number;
}

/**
 * Platform Float Statistics
 */
export interface PlatformFloatStatistics {
  total_platform_yield: number;
  total_user_yield: number;
  total_active_float: number;
  days: number;
  start_date: string;
  end_date: string;
}

/**
 * Float Yield Rates Configuration
 */
export interface FloatYieldRates {
  platform_rate: number;
  user_rate: number;
  min_float_for_yield: number;
  payout_frequency: string;
}

export interface CatFloatDashboard {
  balances: CatFloatBalance;
  streak: StreakInfo;
  multiplier: number;
}

export interface MarketplaceInfo {
  commission_rate: number;
  min_discount: number;
  max_discount: number;
}

/**
 * CatFloat API Service
 */
class CatFloatApiService {
  private baseUrl = '/api/catfloat';

  /**
   * Get user's locked balance
   */
  async getBalance(): Promise<CatFloatBalance> {
    const response = await fetch(`${this.baseUrl}/balance`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch balance');
    }

    const data = await response.json();
    return data.data;
  }

  /**
   * Get all bonus batches for user
   */
  async getBatches(): Promise<BonusBatch[]> {
    const response = await fetch(`${this.baseUrl}/batches`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch batches');
    }

    const data = await response.json();
    return data.data;
  }

  /**
   * Award a locked bonus batch
   */
  async awardBonus(data: {
    user_id: number;
    tenant_id: number;
    amount: number;
    source: string;
    vesting_curve: string;
    source_type?: string;
    source_id?: number;
  }): Promise<any> {
    const response = await fetch(`${this.baseUrl}/award`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error('Failed to award bonus');
    }

    return response.json();
  }

  /**
   * Unlock bonuses
   */
  async unlockBonus(data: {
    batch_id: number;
    instant?: boolean;
    instant_unlock_price?: number;
  }): Promise<any> {
    const response = await fetch(`${this.baseUrl}/unlock`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error('Failed to unlock bonus');
    }

    return response.json();
  }

  /**
   * Log user activity
   */
  async logActivity(data: {
    action_type: string;
    metadata?: Record<string, any>;
  }): Promise<any> {
    const response = await fetch(`${this.baseUrl}/activity`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error('Failed to log activity');
    }

    return response.json();
  }

  /**
   * Process user streak
   */
  async processStreak(): Promise<any> {
    const response = await fetch(`${this.baseUrl}/streak`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to process streak');
    }

    return response.json();
  }

  /**
   * Get streak information
   */
  async getStreakInfo(): Promise<StreakInfo> {
    const response = await fetch(`${this.baseUrl}/streak/info`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch streak info');
    }

    const data = await response.json();
    return data.data;
  }

  /**
   * Claim yield
   */
  async claimYield(): Promise<any> {
    const response = await fetch(`${this.baseUrl}/yield/claim`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to claim yield');
    }

    return response.json();
  }

  /**
   * Calculate yield
   */
  async calculateYield(): Promise<any> {
    const response = await fetch(`${this.baseUrl}/yield/calculate`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to calculate yield');
    }

    return response.json();
  }

  /**
   * Get yield history
   */
  async getYieldHistory(limit = 30): Promise<YieldTransaction[]> {
    const response = await fetch(`${this.baseUrl}/yield/history?limit=${limit}`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch yield history');
    }

    const data = await response.json();
    return data.data;
  }

  /**
   * Sell locked bonus on marketplace
   */
  async sellLockedBonus(data: {
    batch_id: number;
    discount: number;
  }): Promise<any> {
    const response = await fetch(`${this.baseUrl}/marketplace/sell`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error('Failed to sell bonus');
    }

    return response.json();
  }

  /**
   * Get marketplace info
   */
  async getMarketplaceInfo(): Promise<MarketplaceInfo> {
    const response = await fetch(`${this.baseUrl}/marketplace/info`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch marketplace info');
    }

    const data = await response.json();
    return data.data;
  }

  /**
   * Get dashboard data
   */
  async getDashboard(): Promise<CatFloatDashboard> {
    const response = await fetch(`${this.baseUrl}/dashboard`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch dashboard');
    }

    const data = await response.json();
    return data.data;
  }

  /**
   * Get user float yield summary
   * ВАЖНО: По законам РФ float yield - только бонусы, не деньги
   */
  async getFloatYieldSummary(userId: number, tenantId: number): Promise<FloatYieldSummary> {
    const response = await fetch(`${this.baseUrl}/float-yield/summary`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ user_id: userId, tenant_id: tenantId }),
    });

    if (!response.ok) {
      throw new Error('Failed to fetch float yield summary');
    }

    const data = await response.json();
    return data.data;
  }

  /**
   * Get platform float statistics
   */
  async getPlatformFloatStatistics(tenantId?: number, days = 30): Promise<PlatformFloatStatistics> {
    const params = new URLSearchParams({
      days: days.toString(),
    });

    if (tenantId) {
      params.append('tenant_id', tenantId.toString());
    }

    const response = await fetch(`${this.baseUrl}/float-yield/statistics?${params}`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch platform float statistics');
    }

    const data = await response.json();
    return data.data;
  }

  /**
   * Process daily float yield (admin only)
   */
  async processDailyFloatYield(date?: string): Promise<any> {
    const response = await fetch(`${this.baseUrl}/float-yield/process`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(date ? { date } : {}),
    });

    if (!response.ok) {
      throw new Error('Failed to process daily float yield');
    }

    return response.json();
  }

  /**
   * Get float yield rates configuration
   */
  async getFloatYieldRates(): Promise<FloatYieldRates> {
    const response = await fetch(`${this.baseUrl}/float-yield/rates`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Failed to fetch float yield rates');
    }

    const data = await response.json();
    return data.data;
  }
}

export const catfloatApi = new CatFloatApiService();
