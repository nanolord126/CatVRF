/**
 * WebRTC Live Streaming - SQL Queries & Database Examples
 * 
 * Полезные SQL запросы для управления и анализа P2P соединений
 */

-- ════════════════════════════════════════════════════════════════════════════════
-- BASIC QUERIES
-- ════════════════════════════════════════════════════════════════════════════════

-- 1. Получить все активные соединения для стрима
SELECT * FROM stream_peer_connections
WHERE stream_id = 1
  AND status = 'connected'
  AND deleted_at IS NULL
ORDER BY created_at DESC;

-- 2. Подсчёт пиров по статусу
SELECT 
    stream_id,
    status,
    COUNT(*) as count
FROM stream_peer_connections
WHERE deleted_at IS NULL
GROUP BY stream_id, status
ORDER BY stream_id DESC;

-- 3. Получить пиры для конкретного тенанта и стрима
SELECT 
    spc.*,
    u.name as user_name,
    u.email as user_email,
    e.title as stream_title
FROM stream_peer_connections spc
JOIN users u ON spc.user_id = u.id
JOIN events e ON spc.stream_id = e.id
WHERE spc.tenant_id = 1
  AND spc.stream_id = 1
  AND spc.deleted_at IS NULL;

-- 4. Найти пиры с неудачными соединениями
SELECT 
    peer_id,
    user_id,
    stream_id,
    status,
    tags->>'failed_reason' as failure_reason,
    created_at,
    updated_at
FROM stream_peer_connections
WHERE status = 'failed'
  AND deleted_at IS NULL
ORDER BY updated_at DESC
LIMIT 100;

-- ════════════════════════════════════════════════════════════════════════════════
-- ANALYTICS QUERIES
-- ════════════════════════════════════════════════════════════════════════════════

-- 5. Статистика P2P vs SFU соединений за день
SELECT 
    DATE(created_at) as date,
    connection_type,
    COUNT(*) as count,
    COUNT(CASE WHEN status = 'connected' THEN 1 END) as connected,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
    ROUND(100.0 * COUNT(CASE WHEN status = 'connected' THEN 1 END) / COUNT(*), 2) as success_rate
FROM stream_peer_connections
WHERE created_at >= NOW() - INTERVAL 24 HOUR
GROUP BY DATE(created_at), connection_type
ORDER BY date DESC, connection_type;

-- 6. Какие пиры получали ICE candidates?
SELECT 
    peer_id,
    user_id,
    COUNT(CAST(json_array_length(ice_candidates) AS INTEGER)) as ice_candidates_count,
    MAX(updated_at) as last_candidate_at
FROM stream_peer_connections
WHERE ice_candidates IS NOT NULL
  AND deleted_at IS NULL
GROUP BY peer_id, user_id
ORDER BY ice_candidates_count DESC;

-- 7. Среднее время подключения (от joining к connected)
SELECT 
    stream_id,
    AVG(EXTRACT(EPOCH FROM (
        (SELECT CAST(updated_at AS TIMESTAMP)
         FROM stream_peer_connections spc2
         WHERE spc2.peer_id = spc1.peer_id
           AND spc2.status = 'connected'
         LIMIT 1)
        - spc1.created_at
    ))) as avg_connection_time_seconds
FROM stream_peer_connections spc1
WHERE status = 'connecting'
  AND deleted_at IS NULL
GROUP BY stream_id
HAVING AVG(EXTRACT(EPOCH FROM (
    (SELECT CAST(updated_at AS TIMESTAMP)
     FROM stream_peer_connections spc2
     WHERE spc2.peer_id = spc1.peer_id
       AND spc2.status = 'connected'
     LIMIT 1)
    - spc1.created_at
))) > 0
ORDER BY avg_connection_time_seconds DESC;

-- 8. Топ геолокации пиров (по IP из tags)
SELECT 
    tags->>'ip' as ip_address,
    COUNT(*) as peer_count,
    MAX(created_at) as last_connection
FROM stream_peer_connections
WHERE tags->>'ip' IS NOT NULL
  AND deleted_at IS NULL
GROUP BY tags->>'ip'
ORDER BY peer_count DESC
LIMIT 20;

-- 9. Пиры по браузерам/операционным системам
SELECT 
    SUBSTRING(tags->>'user_agent', 1, 50) as user_agent,
    COUNT(*) as count
FROM stream_peer_connections
WHERE deleted_at IS NULL
GROUP BY user_agent
ORDER BY count DESC;

-- 10. Стримы с наибольшим количеством пиров
SELECT 
    e.id,
    e.title,
    COUNT(spc.id) as peer_count,
    COUNT(CASE WHEN spc.status = 'connected' THEN 1 END) as connected_peers,
    COUNT(CASE WHEN spc.connection_type = 'p2p' THEN 1 END) as p2p_peers,
    COUNT(CASE WHEN spc.connection_type = 'sfu' THEN 1 END) as sfu_peers
FROM events e
LEFT JOIN stream_peer_connections spc 
    ON e.id = spc.stream_id 
    AND spc.deleted_at IS NULL
WHERE e.is_live = 1
GROUP BY e.id, e.title
ORDER BY peer_count DESC;

-- ════════════════════════════════════════════════════════════════════════════════
-- MAINTENANCE QUERIES
-- ════════════════════════════════════════════════════════════════════════════════

-- 11. Удалить закрытые соединения старше 60 минут
DELETE FROM stream_peer_connections
WHERE status = 'closed'
  AND updated_at < NOW() - INTERVAL 60 MINUTE;

-- 12. Пометить как closed все соединения старше 24 часов
UPDATE stream_peer_connections
SET status = 'closed', updated_at = NOW()
WHERE status != 'closed'
  AND created_at < NOW() - INTERVAL 24 HOUR
  AND deleted_at IS NULL;

-- 13. Очистить orphaned пиры (для удалённых стримов)
DELETE FROM stream_peer_connections spc
WHERE stream_id NOT IN (SELECT id FROM events)
  AND deleted_at IS NULL;

-- 14. Пиры без связанного пользователя (data integrity check)
SELECT spc.*
FROM stream_peer_connections spc
LEFT JOIN users u ON spc.user_id = u.id
WHERE u.id IS NULL
  AND spc.deleted_at IS NULL;

-- ════════════════════════════════════════════════════════════════════════════════
-- TOPOLOGY ANALYSIS
-- ════════════════════════════════════════════════════════════════════════════════

-- 15. История переключений P2P → SFU
SELECT 
    stream_id,
    user_id,
    COUNT(CASE WHEN connection_type = 'p2p' THEN 1 END) as was_p2p,
    COUNT(CASE WHEN connection_type = 'sfu' THEN 1 END) as now_sfu,
    MAX(updated_at) as last_switch
FROM stream_peer_connections
WHERE deleted_at IS NULL
GROUP BY stream_id, user_id
HAVING COUNT(DISTINCT connection_type) > 1
ORDER BY last_switch DESC;

-- 16. Стримы, которые требуют SFU (>15 пиров)
SELECT 
    e.id,
    e.title,
    COUNT(spc.id) as peer_count,
    CASE WHEN COUNT(spc.id) > 15 THEN 'NEEDS_SFU' ELSE 'P2P_OK' END as topology_status,
    e.topology as current_topology
FROM events e
LEFT JOIN stream_peer_connections spc 
    ON e.id = spc.stream_id 
    AND spc.status = 'connected'
    AND spc.deleted_at IS NULL
WHERE e.is_live = 1
GROUP BY e.id, e.title, e.topology
HAVING COUNT(spc.id) > 10
ORDER BY peer_count DESC;

-- ════════════════════════════════════════════════════════════════════════════════
-- AUDIT & COMPLIANCE
-- ════════════════════════════════════════════════════════════════════════════════

-- 17. Все пиры с correlation_id для аудита
SELECT 
    correlation_id,
    peer_id,
    user_id,
    stream_id,
    status,
    created_at,
    updated_at
FROM stream_peer_connections
WHERE correlation_id IS NOT NULL
  AND deleted_at IS NULL
ORDER BY created_at DESC
LIMIT 100;

-- 18. Потребление памяти (count of large ice_candidates arrays)
SELECT 
    peer_id,
    ROUND(OCTET_LENGTH(ice_candidates) / 1024.0, 2) as ice_candidates_kb,
    JSON_ARRAY_LENGTH(ice_candidates) as candidate_count
FROM stream_peer_connections
WHERE ice_candidates IS NOT NULL
  AND deleted_at IS NULL
ORDER BY OCTET_LENGTH(ice_candidates) DESC
LIMIT 20;

-- 19. Мониторинг SFU соединений
SELECT 
    stream_id,
    COUNT(*) as total_peers,
    COUNT(CASE WHEN connection_type = 'sfu' THEN 1 END) as sfu_peers,
    COUNT(CASE WHEN connection_type = 'p2p' THEN 1 END) as p2p_peers,
    ROUND(100.0 * COUNT(CASE WHEN connection_type = 'sfu' THEN 1 END) / COUNT(*), 2) as sfu_percentage
FROM stream_peer_connections
WHERE deleted_at IS NULL
  AND status = 'connected'
GROUP BY stream_id
HAVING COUNT(CASE WHEN connection_type = 'sfu' THEN 1 END) > 0
ORDER BY sfu_percentage DESC;

-- 20. Качество соединений (какие пиры имеют все ICE candidates)
SELECT 
    peer_id,
    user_id,
    stream_id,
    status,
    CASE 
        WHEN ice_candidates IS NULL THEN 'NO_CANDIDATES'
        WHEN JSON_ARRAY_LENGTH(ice_candidates) > 5 THEN 'GOOD'
        WHEN JSON_ARRAY_LENGTH(ice_candidates) > 0 THEN 'FAIR'
        ELSE 'POOR'
    END as connection_quality,
    JSON_ARRAY_LENGTH(COALESCE(ice_candidates, '[]')) as candidate_count
FROM stream_peer_connections
WHERE status = 'connected'
  AND deleted_at IS NULL
ORDER BY connection_quality DESC, candidate_count DESC;

-- ════════════════════════════════════════════════════════════════════════════════
-- INDEXES (для оптимизации)
-- ════════════════════════════════════════════════════════════════════════════════

-- Убедитесь, что миграция создала эти индексы:
-- CREATE INDEX idx_stream_tenant_status ON stream_peer_connections(stream_id, tenant_id, status);
-- CREATE INDEX idx_tenant_user_created ON stream_peer_connections(tenant_id, user_id, created_at);
-- CREATE UNIQUE INDEX idx_stream_peer_unique ON stream_peer_connections(stream_id, peer_id);
