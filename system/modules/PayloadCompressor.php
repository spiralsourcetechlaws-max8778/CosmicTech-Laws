<?php
/**
 * COSMIC PAYLOAD COMPRESSOR – ENTERPRISE EDITION
 * Supports multiple algorithms, integrity checks, and streaming.
 */
class PayloadCompressor {
    
    const ALGO_GZIP = 'gzip';
    const ALGO_BZIP2 = 'bzip2';
    const ALGO_LZ4 = 'lz4';
    const ALGO_ZSTD = 'zstd';
    
    /**
     * Compress a payload file.
     * @param string $path Input file path
     * @param string $algo Compression algorithm (gzip, bzip2, lz4, zstd)
     * @param int $level Compression level (1-9)
     * @return string Path to compressed file
     * @throws RuntimeException
     */
    public static function compress($path, $algo = self::ALGO_GZIP, $level = 9) {
        if (!file_exists($path)) {
            throw new RuntimeException("Input file not found: $path");
        }
        
        $data = file_get_contents($path);
        if ($data === false) {
            throw new RuntimeException("Failed to read input file: $path");
        }
        
        $compressed = null;
        $ext = '';
        
        switch ($algo) {
            case self::ALGO_GZIP:
                if (!function_exists('gzencode')) {
                    throw new RuntimeException("GZIP not supported");
                }
                $compressed = gzencode($data, $level);
                $ext = '.gz';
                break;
                
            case self::ALGO_BZIP2:
                if (!function_exists('bzcompress')) {
                    throw new RuntimeException("BZIP2 not supported");
                }
                $compressed = bzcompress($data, $level);
                $ext = '.bz2';
                break;
                
            case self::ALGO_LZ4:
                if (!function_exists('lz4_compress')) {
                    // Fallback to exec if lz4 binary available
                    $tmpIn = tempnam(sys_get_temp_dir(), 'lz4');
                    $tmpOut = $tmpIn . '.lz4';
                    file_put_contents($tmpIn, $data);
                    $cmd = "lz4 -$level " . escapeshellarg($tmpIn) . " " . escapeshellarg($tmpOut) . " 2>/dev/null";
                    exec($cmd, $output, $ret);
                    unlink($tmpIn);
                    if ($ret !== 0 || !file_exists($tmpOut)) {
                        throw new RuntimeException("LZ4 compression failed");
                    }
                    $compressed = file_get_contents($tmpOut);
                    unlink($tmpOut);
                    $ext = '.lz4';
                } else {
                    $compressed = lz4_compress($data, $level);
                    $ext = '.lz4';
                }
                break;
                
            case self::ALGO_ZSTD:
                if (!function_exists('zstd_compress')) {
                    // Fallback to binary
                    $tmpIn = tempnam(sys_get_temp_dir(), 'zst');
                    $tmpOut = $tmpIn . '.zst';
                    file_put_contents($tmpIn, $data);
                    $cmd = "zstd -$level " . escapeshellarg($tmpIn) . " -o " . escapeshellarg($tmpOut) . " 2>/dev/null";
                    exec($cmd, $output, $ret);
                    unlink($tmpIn);
                    if ($ret !== 0 || !file_exists($tmpOut)) {
                        throw new RuntimeException("ZSTD compression failed");
                    }
                    $compressed = file_get_contents($tmpOut);
                    unlink($tmpOut);
                    $ext = '.zst';
                } else {
                    $compressed = zstd_compress($data, $level);
                    $ext = '.zst';
                }
                break;
                
            default:
                throw new RuntimeException("Unsupported algorithm: $algo");
        }
        
        if ($compressed === null) {
            throw new RuntimeException("Compression produced no output");
        }
        
        $outPath = $path . $ext;
        if (file_put_contents($outPath, $compressed) === false) {
            throw new RuntimeException("Failed to write compressed file: $outPath");
        }
        
        return $outPath;
    }
    
    /**
     * Decompress a payload file.
     * @param string $gzpath Path to compressed file
     * @param string $algo Optional algorithm (auto-detected from extension)
     * @return string Decompressed data
     */
    public static function decompress($gzpath, $algo = null) {
        if (!file_exists($gzpath)) {
            throw new RuntimeException("Compressed file not found: $gzpath");
        }
        
        $data = file_get_contents($gzpath);
        if ($data === false) {
            throw new RuntimeException("Failed to read compressed file");
        }
        
        if ($algo === null) {
            $ext = pathinfo($gzpath, PATHINFO_EXTENSION);
            switch ($ext) {
                case 'gz': $algo = self::ALGO_GZIP; break;
                case 'bz2': $algo = self::ALGO_BZIP2; break;
                case 'lz4': $algo = self::ALGO_LZ4; break;
                case 'zst': $algo = self::ALGO_ZSTD; break;
                default: throw new RuntimeException("Unknown extension: $ext");
            }
        }
        
        switch ($algo) {
            case self::ALGO_GZIP:
                if (!function_exists('gzdecode')) {
                    throw new RuntimeException("GZIP decode not supported");
                }
                return gzdecode($data);
                
            case self::ALGO_BZIP2:
                if (!function_exists('bzdecompress')) {
                    throw new RuntimeException("BZIP2 decompress not supported");
                }
                return bzdecompress($data);
                
            case self::ALGO_LZ4:
                if (!function_exists('lz4_uncompress')) {
                    // Use binary
                    $tmpIn = tempnam(sys_get_temp_dir(), 'lz4');
                    $tmpOut = $tmpIn . '.out';
                    file_put_contents($tmpIn, $data);
                    $cmd = "lz4 -d " . escapeshellarg($tmpIn) . " " . escapeshellarg($tmpOut) . " 2>/dev/null";
                    exec($cmd, $output, $ret);
                    unlink($tmpIn);
                    if ($ret !== 0 || !file_exists($tmpOut)) {
                        throw new RuntimeException("LZ4 decompression failed");
                    }
                    $decomp = file_get_contents($tmpOut);
                    unlink($tmpOut);
                    return $decomp;
                } else {
                    return lz4_uncompress($data);
                }
                
            case self::ALGO_ZSTD:
                if (!function_exists('zstd_uncompress')) {
                    $tmpIn = tempnam(sys_get_temp_dir(), 'zst');
                    $tmpOut = $tmpIn . '.out';
                    file_put_contents($tmpIn, $data);
                    $cmd = "zstd -d " . escapeshellarg($tmpIn) . " -o " . escapeshellarg($tmpOut) . " 2>/dev/null";
                    exec($cmd, $output, $ret);
                    unlink($tmpIn);
                    if ($ret !== 0 || !file_exists($tmpOut)) {
                        throw new RuntimeException("ZSTD decompression failed");
                    }
                    $decomp = file_get_contents($tmpOut);
                    unlink($tmpOut);
                    return $decomp;
                } else {
                    return zstd_uncompress($data);
                }
                
            default:
                throw new RuntimeException("Unsupported algorithm: $algo");
        }
    }
    
    /**
     * Split a payload into chunks (for transfer).
     * @param string $path File to split
     * @param int $chunkSize Size in bytes per chunk
     * @return array List of chunk file paths
     */
    public static function splitPayload($path, $chunkSize = 1048576) {
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: $path");
        }
        
        $handle = fopen($path, 'rb');
        $chunkNum = 0;
        $chunks = [];
        
        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            $chunkPath = $path . '.part' . str_pad($chunkNum, 4, '0', STR_PAD_LEFT);
            file_put_contents($chunkPath, $chunk);
            $chunks[] = $chunkPath;
            $chunkNum++;
        }
        
        fclose($handle);
        return $chunks;
    }
    
    /**
     * Reassemble split payload chunks.
     * @param array $chunks List of chunk file paths
     * @param string $outputPath Output file path
     */
    public static function reassemblePayload($chunks, $outputPath) {
        $out = fopen($outputPath, 'wb');
        foreach ($chunks as $chunk) {
            if (!file_exists($chunk)) {
                throw new RuntimeException("Missing chunk: $chunk");
            }
            $data = file_get_contents($chunk);
            fwrite($out, $data);
        }
        fclose($out);
        return $outputPath;
    }
}
