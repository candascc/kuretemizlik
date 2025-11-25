<?php
/**
 * Redis Cache Implementation
 * Enterprise-level distributed caching with file cache fallback
 */

class RedisCache implements CacheInterface
{
    private $redis;
    private $fallbackCache;
    private $isConnected = false;
    private $config;
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => $_ENV['REDIS_DATABASE'] ?? 0,
            'timeout' => 5,
            'read_timeout' => 0,
            'persistent' => true,
            'prefix' => $_ENV['REDIS_PREFIX'] ?? 'app:',
            'serializer' => 'json',
            'compression' => true,
            'fallback_enabled' => true
        ], $config);
        
        $this->fallbackCache = new Cache();
        $this->connect();
    }
    
    /**
     * Connect to Redis server
     */
    private function connect(): void
    {
        try {
            $this->redis = new Redis();
            
            if ($this->config['persistent']) {
                $this->redis->pconnect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout']
                );
            } else {
                $this->redis->connect(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['timeout']
                );
            }
            
            if ($this->config['password']) {
                $this->redis->auth($this->config['password']);
            }
            
            if ($this->config['database'] > 0) {
                $this->redis->select($this->config['database']);
            }
            
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
            $this->redis->setOption(Redis::OPT_PREFIX, $this->config['prefix']);
            $this->redis->setOption(Redis::OPT_READ_TIMEOUT, $this->config['read_timeout']);
            
            $this->isConnected = true;
            
            // Log successful connection
            Logger::info('Redis cache connected successfully', [
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'database' => $this->config['database']
            ]);
            
        } catch (Exception $e) {
            $this->isConnected = false;
            Logger::warning('Redis connection failed, using fallback cache', [
                'error' => $e->getMessage(),
                'host' => $this->config['host'],
                'port' => $this->config['port']
            ]);
        }
    }
    
    /**
     * Get value from cache
     */
    public function get(string $key, $default = null)
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->get($key, $default);
        }
        
        try {
            $value = $this->redis->get($key);
            
            if ($value === false) {
                return $default;
            }
            
            // Decompress if needed
            if ($this->config['compression'] && is_string($value)) {
                $value = gzuncompress($value);
            }
            
            return $value;
            
        } catch (Exception $e) {
            Logger::warning('Redis get failed, using fallback', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->get($key, $default);
        }
    }
    
    /**
     * Set value in cache
     */
    public function set(string $key, $value, int $ttl = 0): bool
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->set($key, $value, $ttl);
        }
        
        try {
            // Compress if needed
            if ($this->config['compression'] && is_string($value)) {
                $value = gzcompress($value, 6);
            }
            
            if ($ttl > 0) {
                $result = $this->redis->setex($key, $ttl, $value);
            } else {
                $result = $this->redis->set($key, $value);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Logger::warning('Redis set failed, using fallback', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->set($key, $value, $ttl);
        }
    }
    
    /**
     * Delete value from cache
     */
    public function delete(string $key): bool
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->delete($key);
        }
        
        try {
            $result = $this->redis->del($key);
            return $result > 0;
            
        } catch (Exception $e) {
            Logger::warning('Redis delete failed, using fallback', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->delete($key);
        }
    }
    
    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->has($key);
        }
        
        try {
            return $this->redis->exists($key) > 0;
            
        } catch (Exception $e) {
            Logger::warning('Redis exists failed, using fallback', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->has($key);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->clear();
        }
        
        try {
            $result = $this->redis->flushDB();
            
            // Also clear fallback cache
            $this->fallbackCache->clear();
            
            return $result;
            
        } catch (Exception $e) {
            Logger::warning('Redis clear failed, using fallback', [
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->clear();
        }
    }
    
    /**
     * Get multiple values
     */
    public function getMultiple(array $keys, $default = null): array
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->getMultiple($keys, $default);
        }
        
        try {
            $values = $this->redis->mget($keys);
            $result = [];
            
            foreach ($keys as $index => $key) {
                $result[$key] = $values[$index] !== false ? $values[$index] : $default;
            }
            
            return $result;
            
        } catch (Exception $e) {
            Logger::warning('Redis mget failed, using fallback', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->getMultiple($keys, $default);
        }
    }
    
    /**
     * Set multiple values
     */
    public function setMultiple(array $values, int $ttl = 0): bool
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->setMultiple($values, $ttl);
        }
        
        try {
            if ($ttl > 0) {
                $pipe = $this->redis->pipeline();
                foreach ($values as $key => $value) {
                    $pipe->setex($key, $ttl, $value);
                }
                $pipe->exec();
            } else {
                $this->redis->mset($values);
            }
            
            return true;
            
        } catch (Exception $e) {
            Logger::warning('Redis mset failed, using fallback', [
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->setMultiple($values, $ttl);
        }
    }
    
    /**
     * Delete multiple keys
     */
    public function deleteMultiple(array $keys): bool
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->deleteMultiple($keys);
        }
        
        try {
            $result = $this->redis->del($keys);
            return $result > 0;
            
        } catch (Exception $e) {
            Logger::warning('Redis mdel failed, using fallback', [
                'keys' => $keys,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->deleteMultiple($keys);
        }
    }
    
    /**
     * Increment value
     */
    public function increment(string $key, int $step = 1): int
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->increment($key, $step);
        }
        
        try {
            return $this->redis->incrBy($key, $step);
            
        } catch (Exception $e) {
            Logger::warning('Redis increment failed, using fallback', [
                'key' => $key,
                'step' => $step,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->increment($key, $step);
        }
    }
    
    /**
     * Decrement value
     */
    public function decrement(string $key, int $step = 1): int
    {
        if (!$this->isConnected) {
            return $this->fallbackCache->decrement($key, $step);
        }
        
        try {
            return $this->redis->decrBy($key, $step);
            
        } catch (Exception $e) {
            Logger::warning('Redis decrement failed, using fallback', [
                'key' => $key,
                'step' => $step,
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackCache->decrement($key, $step);
        }
    }
    
    /**
     * Remember pattern
     */
    public function remember(string $key, callable $callback, int $ttl = 0)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Tag-based cache operations
     */
    public function tag(string $tag): array
    {
        if (!$this->isConnected) {
            return [];
        }
        
        try {
            $pattern = "tag:{$tag}:*";
            return $this->redis->keys($pattern);
            
        } catch (Exception $e) {
            Logger::warning('Redis tag failed', [
                'tag' => $tag,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Invalidate by tag
     */
    public function forgetTag(string $tag): bool
    {
        if (!$this->isConnected) {
            return false;
        }
        
        try {
            $keys = $this->tag($tag);
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
            
            return true;
            
        } catch (Exception $e) {
            Logger::warning('Redis forgetTag failed', [
                'tag' => $tag,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Set with tags
     */
    public function setWithTags(string $key, $value, array $tags, int $ttl = 0): bool
    {
        $result = $this->set($key, $value, $ttl);
        
        if ($result && $this->isConnected) {
            try {
                foreach ($tags as $tag) {
                    $this->redis->sAdd("tag:{$tag}", $key);
                }
            } catch (Exception $e) {
                Logger::warning('Redis setWithTags failed', [
                    'key' => $key,
                    'tags' => $tags,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $result;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        if (!$this->isConnected) {
            return [
                'status' => 'fallback',
                'connected' => false,
                'fallback_stats' => $this->fallbackCache->getStats()
            ];
        }
        
        try {
            $info = $this->redis->info();
            
            return [
                'status' => 'redis',
                'connected' => true,
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info),
                'uptime' => $info['uptime_in_seconds'] ?? 0
            ];
            
        } catch (Exception $e) {
            Logger::warning('Redis stats failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => 'error',
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate hit rate
     */
    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
    
    /**
     * Get all keys matching pattern
     */
    public function keys(string $pattern = '*'): array
    {
        if (!$this->isConnected) {
            return [];
        }
        
        try {
            return $this->redis->keys($pattern);
            
        } catch (Exception $e) {
            Logger::warning('Redis keys failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get TTL for key
     */
    public function ttl(string $key): int
    {
        if (!$this->isConnected) {
            return -1;
        }
        
        try {
            return $this->redis->ttl($key);
            
        } catch (Exception $e) {
            Logger::warning('Redis ttl failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return -1;
        }
    }
    
    /**
     * Set expiration for key
     */
    public function expire(string $key, int $ttl): bool
    {
        if (!$this->isConnected) {
            return false;
        }
        
        try {
            return $this->redis->expire($key, $ttl);
            
        } catch (Exception $e) {
            Logger::warning('Redis expire failed', [
                'key' => $key,
                'ttl' => $ttl,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Check connection status
     */
    public function isConnected(): bool
    {
        return $this->isConnected;
    }
    
    /**
     * Reconnect to Redis
     */
    public function reconnect(): bool
    {
        $this->isConnected = false;
        $this->connect();
        return $this->isConnected;
    }
    
    /**
     * Close connection
     */
    public function close(): void
    {
        if ($this->isConnected && $this->redis) {
            $this->redis->close();
            $this->isConnected = false;
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }
}
