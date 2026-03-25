<?php

declare(strict_types=1);

/**
 * USER TASTE PROFILE v2.0 - QUICK REFERENCE GUIDE
 * CANON 2026: One-page reference for developers
 */

// ========== API QUICK REFERENCE ==========

/**
 * QUICK START: 5-minute implementation
 * 
 * 1. Get service:
 *    $service = app(\App\Services\ML\UserTasteProfileService::class);
 * 
 * 2. Create profile:
 *    $profile = $service->getOrCreateProfile($userId, $tenantId);
 * 
 * 3. Record interaction:
 *    $service->recordInteraction($userId, $tenantId, 'purchase', [
 *        'product_id' => 123,
 *        'amount' => 5000,
 *    ]);
 * 
 * 4. Update preferences:
 *    $profile = $service->updateExplicitPreferences($userId, $tenantId, [
 *        'sizes' => ['clothing' => ['top' => 'M']],
 *    ]);
 * 
 * 5. Check readiness:
 *    if ($profile->isReadyForRecommendations()) {
 *        // Use in recommendations
 *    }
 */

// ========== PROFILE STRUCTURE AT A GLANCE ==========

/**
 * UserTasteProfile JSON Structure:
 * 
 * {
 *   "explicit_preferences": {
 *     "sizes": { "clothing": { "top": "M", "bottom": "32" } },
 *     "dietary": { "type": ["vegan"], "allergies": ["nuts"] },
 *     "style": { "colors": ["blue"], "brands": ["Nike"] }
 *   },
 *   "implicit_scores": {
 *     "category_scores": { "fashion_women": 0.94, "beauty": 0.87 },
 *     "behavioral_metrics": {
 *       "session_duration_hours": 12.5,
 *       "purchase_frequency": 0.15,
 *       "price_sensitivity": 0.45,
 *       "brand_loyalty": 0.72
 *     }
 *   },
 *   "embeddings": {
 *     "main": [0.12, -0.45, ...768 values...],
 *     "categories": {
 *       "fashion_women": [0.23, -0.34, ...768 values...]
 *     }
 *   },
 *   "metadata": {
 *     "data_quality_score": 0.78,
 *     "total_interactions": 45,
 *     "recommendation_influence": 0.7,
 *     "ml_model_version": "taste-v2.3-20260325",
 *     "allow_personalization": true
 *   }
 * }
 */

// ========== INTERACTION TYPES ==========

/**
 * Supported Interaction Types:
 * 
 * - 'product_view'      (0.1 weight) - User viewed product
 * - 'product_search'    (0.15 weight) - User searched for something
 * - 'cart_add'          (0.5 weight) - User added to cart
 * - 'cart_remove'       (0.3 weight) - User removed from cart
 * - 'purchase'          (1.0 weight) - User purchased
 * - 'review'            (0.7 weight) - User left review
 * - 'rating'            (0.6 weight) - User rated product
 * - 'wishlist_add'      (0.3 weight) - User added to wishlist
 * 
 * All weights are in config/taste-ml.php
 */

// ========== KEY METHODS REFERENCE ==========

/**
 * UserTasteProfile Model Methods:
 * ─────────────────────────────────
 * $profile->getDataQualityScore()        // Returns 0-1 quality metric
 * $profile->getRecommendationInfluence() // Returns 0-0.7 (capped)
 * $profile->getCategoryScores()          // Returns ['fashion_women' => 0.94]
 * $profile->getMainEmbedding()           // Returns 768-dim array or null
 * $profile->getCategoryEmbedding('name') // Returns category-specific embedding
 * $profile->isReadyForRecommendations()  // Boolean (>=10 interactions, quality>=0.6)
 * $profile->isColdStart()                // Boolean (<5 interactions)
 * $profile->needsRecalculation()         // Boolean (not updated in 24h)
 * $profile->getTotalInteractions()       // Returns integer count
 * $profile->getExplicitPreferences()     // Returns array of user preferences
 * 
 * UserTasteProfileService Methods:
 * ────────────────────────────────
 * $service->getOrCreateProfile(userId, tenantId, correlationId)
 *   // Get or create profile with defaults
 * 
 * $service->updateExplicitPreferences(userId, tenantId, prefs, correlationId)
 *   // Update user-provided preferences (merged, not replaced)
 * 
 * $service->updateImplicitScores(userId, tenantId, scores, metrics, embeddings, correlationId)
 *   // Update ML-computed scores (called by MLRecalculateUserTastesJob)
 * 
 * $service->recordInteraction(userId, tenantId, type, details, correlationId)
 *   // Log user action (view, purchase, review, etc.)
 * 
 * $service->setPersonalizationEnabled(userId, tenantId, enabled)
 *   // Toggle recommendations (data remains for future use)
 */

// ========== STATUS CHECKS QUICK REFERENCE ==========

/**
 * // Check profile readiness
 * if ($profile->isReadyForRecommendations()) {
 *     // Can use in RecommendationService
 *     // Has >= 10 interactions AND quality >= 0.6
 * }
 * 
 * // Check if profile is new (cold start)
 * if ($profile->isColdStart()) {
 *     // Has < 5 interactions
 *     // Should show popular items instead of personalized
 * }
 * 
 * // Check if profile needs update
 * if ($profile->needsRecalculation()) {
 *     // Not updated in 24 hours
 *     // MLRecalculateUserTastesJob will update at 03:00 UTC daily
 * }
 * 
 * // Check recommendation influence
 * $influence = $profile->getRecommendationInfluence();
 * if ($influence >= 0.5) {
 *     // Strong personalization, use ML-based recommendations
 * } else {
 *     // Weak personalization, mix with popular items
 * }
 * 
 * // Check data quality
 * $quality = $profile->getDataQualityScore();
 * // 0.0-0.3: Poor (mostly empty profile)
 * // 0.3-0.6: Fair (some data)
 * // 0.6-0.85: Good (mature profile)
 * // 0.85-1.0: Excellent (complete profile)
 */

// ========== COMMON TASKS ==========

/**
 * Task: Add user-provided preferences
 * ────────────────────────────────────
 * $service = app(\App\Services\ML\UserTasteProfileService::class);
 * $profile = $service->updateExplicitPreferences(
 *     auth()->id(),
 *     auth()->user()->tenant_id,
 *     [
 *         'sizes' => ['clothing' => ['top' => 'M', 'bottom' => '32']],
 *         'dietary' => ['type' => ['vegan'], 'allergies' => ['nuts']],
 *     ]
 * );
 * 
 * Task: Track product view
 * ────────────────────────
 * $service->recordInteraction(
 *     auth()->id(),
 *     auth()->user()->tenant_id,
 *     'product_view',
 *     ['product_id' => $product->id, 'category' => $category]
 * );
 * 
 * Task: Track purchase
 * ───────────────────
 * $service->recordInteraction(
 *     auth()->id(),
 *     auth()->user()->tenant_id,
 *     'purchase',
 *     ['order_id' => $order->id, 'amount' => $order->total]
 * );
 * 
 * Task: Get profile for recommendations
 * ─────────────────────────────────────
 * $profile = \App\Models\UserTasteProfile::where([
 *     'user_id' => auth()->id(),
 *     'tenant_id' => auth()->user()->tenant_id,
 * ])->first();
 * 
 * if ($profile?->isReadyForRecommendations()) {
 *     $scores = $profile->getCategoryScores();
 *     // Use in RecommendationService
 * }
 * 
 * Task: Disable personalization
 * ──────────────────────────────
 * $service->setPersonalizationEnabled(
 *     auth()->id(),
 *     auth()->user()->tenant_id,
 *     false  // Disable
 * );
 * // Profile data saved, but won't be used for recommendations
 */

// ========== CONFIGURATION OVERRIDE ==========

/**
 * Override config values in .env:
 * 
 * TASTE_EMBEDDINGS_MODEL=text-embedding-3-small  (default: text-embedding-3-large)
 * TASTE_EMBEDDINGS_DIMENSIONS=384                (default: 768)
 * TASTE_MODEL_VERSION=taste-v2.4-custom          (default: taste-v2.3-20260325)
 * TASTE_QUALITY_MIN_INTERACTIONS=20              (default: 10)
 * TASTE_RECOMMENDATION_MAX_INFLUENCE=0.8         (default: 0.7)
 * OPENAI_API_KEY=sk-proj-...                     (required)
 * REDIS_HOST=127.0.0.1                           (default)
 * REDIS_PORT=6379                                (default)
 * LOG_CHANNEL=audit                              (recommended)
 */

// ========== LOGGING AND MONITORING ==========

/**
 * Check audit logs:
 * ─────────────────
 * tail -f storage/logs/audit.log
 * 
 * Query audit events:
 * ───────────────────
 * Log::channel('audit')->info('User profile accessed', [
 *     'user_id' => $userId,
 *     'correlation_id' => $correlationId,
 * ]);
 * 
 * Monitor job execution:
 * ──────────────────────
 * // Job runs daily at 03:00 UTC
 * // Check logs for: "ML Taste Profile Job completed"
 * // Check database: SELECT * FROM user_taste_profiles WHERE updated_at >= NOW() - INTERVAL 1 DAY
 * 
 * Monitor embeddings caching:
 * ───────────────────────────
 * // Embeddings cached in Redis for 24 hours
 * redis-cli KEYS "taste:embedding:*" | wc -l  // Count cached embeddings
 * redis-cli TTL "taste:embedding:user:1"      // Check TTL
 * 
 * Monitor score caching:
 * ──────────────────────
 * // Scores cached in Redis for 1 hour
 * redis-cli KEYS "taste:score:*" | wc -l      // Count cached scores
 */

// ========== TROUBLESHOOTING QUICK REFERENCE ==========

/**
 * Problem: Profile not ready for recommendations
 * ───────────────────────────────────────────────
 * Check: $profile->getTotalInteractions() >= 10
 * Check: $profile->getDataQualityScore() >= 0.6
 * Check: $profile->allow_personalization == true
 * Solution: Record more interactions via recordInteraction()
 * Timeline: Will improve as user interacts with platform
 * 
 * Problem: Recommendation influence too low
 * ──────────────────────────────────────────
 * Check: Capped at 0.7 by design
 * Check: $profile->getRecommendationInfluence()
 * Solution: Mix personalized + popular recommendations
 * Why: CANON 2026 requirement - never 100% personalized
 * 
 * Problem: Embeddings are null
 * ─────────────────────────────
 * Check: OPENAI_API_KEY is set
 * Check: Redis connection working
 * Solution: Wait for MLRecalculateUserTastesJob (runs at 03:00 UTC)
 * Or: Manually trigger: Artisan::call('queue:work')
 * 
 * Problem: Data quality score not improving
 * ──────────────────────────────────────────
 * Check: Recording interactions with correct types
 * Check: Weights in config/taste-ml.php
 * Solution: Ensure diverse interaction types (not just views)
 * Timeline: Recalculated daily at 03:00 UTC
 * 
 * Problem: Scheduler not running
 * ────────────────────────────────
 * Check: MLServiceProvider registered in config/app.php
 * Check: Job registered in app/Console/Kernel.php
 * Check: Queue worker running: php artisan queue:work
 * Solution: Run: php artisan schedule:list (should show job)
 * Test: php artisan schedule:test --name=taste-profiles-recalculate
 */

// ========== DATABASE QUICK QUERIES ==========

/**
 * Find user profile:
 * ──────────────────
 * SELECT * FROM user_taste_profiles WHERE user_id = 1 AND tenant_id = 1;
 * 
 * Find cold start profiles:
 * ─────────────────────────
 * SELECT * FROM user_taste_profiles WHERE JSON_EXTRACT(metadata, '$.total_interactions') < 5;
 * 
 * Find ready profiles:
 * ───────────────────
 * SELECT * FROM user_taste_profiles 
 * WHERE JSON_EXTRACT(metadata, '$.total_interactions') >= 10 
 * AND JSON_EXTRACT(metadata, '$.data_quality_score') >= 0.6;
 * 
 * Average quality score:
 * ──────────────────────
 * SELECT AVG(JSON_EXTRACT(metadata, '$.data_quality_score')) as avg_quality
 * FROM user_taste_profiles WHERE is_active = 1;
 * 
 * Recent profile changes:
 * ──────────────────────
 * SELECT * FROM user_taste_profile_history 
 * ORDER BY created_at DESC LIMIT 10;
 * 
 * Profiles needing recalculation:
 * ───────────────────────────────
 * SELECT * FROM user_taste_profiles 
 * WHERE updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
 */

// ========== FILE LOCATIONS FOR REFERENCE ==========

/**
 * Source Code:
 * ────────────
 * Models:         app/Models/UserTasteProfile*.php
 * Services:       app/Services/ML/*.php
 * Jobs:           app/Jobs/ML/MLRecalculateUserTastesJob.php
 * Configuration:  config/taste-ml.php
 * Provider:       app/Providers/MLServiceProvider.php
 * Database:       database/migrations/2026_03_25_...php
 * 
 * Tests:
 * ──────
 * Feature Tests:  tests/Feature/ML/UserTasteProfileTest.php
 * Unit Tests:     tests/Unit/ML/TasteMLServiceTest.php
 * 
 * Documentation:
 * ──────────────
 * Architecture:     TASTE_PROFILE_V2_DOCUMENTATION.php
 * Setup Guide:      TASTE_PROFILE_SETUP.php
 * Scheduler Setup:  KERNEL_SCHEDULE_REGISTRATION.md
 * Integration:      USER_TASTE_PROFILE_INTEGRATION_GUIDE.php
 * Status:           USER_TASTE_PROFILE_v2_COMPLETION_STATUS.php
 * Quick Reference:  This file
 */

// ========== END OF QUICK REFERENCE ==========
