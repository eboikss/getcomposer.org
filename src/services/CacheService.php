<?php

/**
 * Cache Service for optimizing expensive operations
 */
class CacheService
{
    private $cacheDir;
    private $defaultTtl = 3600; // 1 hour

    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../cache/app';
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cached data or execute callback if cache miss
     */
    public function remember($key, $callback, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTtl;
        $cacheFile = $this->getCacheFile($key);
        
        // Check if cache exists and is still valid
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
            return unserialize(file_get_contents($cacheFile));
        }
        
        // Cache miss - execute callback and store result
        $data = $callback();
        file_put_contents($cacheFile, serialize($data), LOCK_EX);
        
        return $data;
    }

    /**
     * Cache with file modification time dependency
     */
    public function rememberFile($key, $filePath, $callback, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTtl;
        $cacheFile = $this->getCacheFile($key);
        
        // Check if cache exists, is valid, and source file hasn't changed
        if (file_exists($cacheFile) && 
            file_exists($filePath) && 
            filemtime($cacheFile) >= filemtime($filePath) &&
            (time() - filemtime($cacheFile)) < $ttl) {
            return unserialize(file_get_contents($cacheFile));
        }
        
        // Cache miss - execute callback and store result
        $data = $callback();
        file_put_contents($cacheFile, serialize($data), LOCK_EX);
        
        return $data;
    }

    /**
     * Cache with directory modification time dependency
     */
    public function rememberDirectory($key, $dirPath, $callback, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTtl;
        $cacheFile = $this->getCacheFile($key);
        
        // Get latest modification time in directory
        $latestMtime = $this->getDirectoryMtime($dirPath);
        
        // Check if cache exists, is valid, and directory hasn't changed
        if (file_exists($cacheFile) && 
            filemtime($cacheFile) >= $latestMtime &&
            (time() - filemtime($cacheFile)) < $ttl) {
            return unserialize(file_get_contents($cacheFile));
        }
        
        // Cache miss - execute callback and store result
        $data = $callback();
        file_put_contents($cacheFile, serialize($data), LOCK_EX);
        
        return $data;
    }

    /**
     * Clear cache for a specific key
     */
    public function forget($key)
    {
        $cacheFile = $this->getCacheFile($key);
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /**
     * Clear all cache
     */
    public function flush()
    {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    private function getCacheFile($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    private function getDirectoryMtime($dirPath)
    {
        if (!is_dir($dirPath)) {
            return 0;
        }
        
        $latestMtime = filemtime($dirPath);
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $mtime = $file->getMTime();
            if ($mtime > $latestMtime) {
                $latestMtime = $mtime;
            }
        }
        
        return $latestMtime;
    }
}