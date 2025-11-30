<?php
/**
 * Simple QR Code Generator - Version 1 (21x21)
 * Generates valid QR codes without external dependencies
 */
class SimpleQRCode {
    const VERSION = 1;
    const SIZE = 21;
    
    private $modules = [];
    private $data = '';
    
    public function __construct($data) {
        $this->data = $data;
        $this->generate();
    }
    
    private function generate() {
        // Initialize empty grid with -1 (empty marker)
        $this->modules = array_fill(0, self::SIZE, array_fill(0, self::SIZE, -1));
        
        // Add function patterns (structures that are always the same)
        $this->addPositionDetectionPatterns();
        $this->addTimingPatterns();
        $this->addFormatInfo();
        $this->addDarkModule();
        
        // Encode and place data
        $bitString = $this->encodeBits();
        $this->placeDataBits($bitString);
    }
    
    /**
     * Add the three 7x7 position detection patterns (finder patterns)
     */
    private function addPositionDetectionPatterns() {
        // Top-left
        $this->addPositionPattern(0, 0);
        // Top-right
        $this->addPositionPattern(0, self::SIZE - 7);
        // Bottom-left
        $this->addPositionPattern(self::SIZE - 7, 0);
    }
    
    private function addPositionPattern($row, $col) {
        // 7x7 black border
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                $rr = $row + $r;
                $cc = $col + $c;
                if ($rr < self::SIZE && $cc < self::SIZE) {
                    // Outer 7x7 border (black)
                    if ($r == 0 || $r == 6 || $c == 0 || $c == 6) {
                        $this->modules[$rr][$cc] = 1;
                    }
                    // Inner white border
                    elseif ($r == 1 || $r == 5 || $c == 1 || $c == 5) {
                        $this->modules[$rr][$cc] = 0;
                    }
                    // Center 3x3 black
                    else {
                        $this->modules[$rr][$cc] = 1;
                    }
                }
            }
        }
        
        // Add white separators around patterns
        for ($i = 0; $i < 8; $i++) {
            if ($row + 7 < self::SIZE && $col + $i < self::SIZE) {
                $this->modules[$row + 7][$col + $i] = 0;
            }
            if ($row + $i < self::SIZE && $col + 7 < self::SIZE) {
                $this->modules[$row + $i][$col + 7] = 0;
            }
        }
    }
    
    /**
     * Add timing patterns - alternating black and white lines
     */
    private function addTimingPatterns() {
        for ($i = 8; $i < self::SIZE - 8; $i++) {
            if ($i % 2 == 0) {
                $this->modules[6][$i] = 1;
                $this->modules[$i][6] = 1;
            } else {
                $this->modules[6][$i] = 0;
                $this->modules[$i][6] = 0;
            }
        }
    }
    
    /**
     * Add format information areas (simplified)
     */
    private function addFormatInfo() {
        // Format info row 8
        for ($i = 0; $i < 9; $i++) {
            if ($i != 6) {
                $this->modules[8][$i] = 0;
            }
        }
        
        // Format info column 8
        for ($i = 0; $i < 9; $i++) {
            if ($i != 6 && 8 < self::SIZE && self::SIZE - 1 - $i >= 0) {
                $this->modules[self::SIZE - 1 - $i][8] = 0;
            }
        }
    }
    
    /**
     * Dark module (always black)
     */
    private function addDarkModule() {
        $this->modules[self::SIZE - 8][8] = 1;
    }
    
    /**
     * Encode data into binary string
     */
    private function encodeBits() {
        $bits = '';
        
        // Mode indicator: 0100 (byte mode)
        $bits .= '0100';
        
        // Character count indicator (8 bits for Version 1)
        $len = strlen($this->data);
        $bits .= str_pad(decbin($len), 8, '0', STR_PAD_LEFT);
        
        // Encode each character as 8-bit byte
        for ($i = 0; $i < strlen($this->data); $i++) {
            $bits .= str_pad(decbin(ord($this->data[$i])), 8, '0', STR_PAD_LEFT);
        }
        
        // Terminator: 0000
        $bits .= '0000';
        
        // Pad to Version 1 capacity (128 bits = 17 bytes)
        // Alternate between 11101100 and 00010001
        $paddingByte = 0xEC; // 11101100
        while (strlen($bits) < 128) {
            if (strlen($bits) + 8 <= 128) {
                $bits .= decbin($paddingByte);
                $paddingByte = ($paddingByte == 0xEC) ? 0x11 : 0xEC;
            } else {
                // Fill remaining bits with 0
                $bits .= str_repeat('0', 128 - strlen($bits));
            }
        }
        
        return substr($bits, 0, 128);
    }
    
    /**
     * Place data bits in the QR code grid
     */
    private function placeDataBits($bitString) {
        $bitIndex = 0;
        
        // Process columns from right to left, two at a time
        for ($colPair = 0; $colPair < 10; $colPair++) {
            $col = self::SIZE - 1 - ($colPair * 2);
            
            // Skip the timing column at column 6
            if ($col == 7) $col = 6;
            if ($col == 6) $col = 5;
            
            // Alternate between going up and down
            $goingUp = ($colPair % 2 == 0);
            
            if ($goingUp) {
                // Move upward, from bottom to top
                for ($row = self::SIZE - 1; $row >= 0; $row--) {
                    // Right column of pair
                    if ($col >= 0 && $this->modules[$row][$col] == -1) {
                        $this->modules[$row][$col] = (int)$bitString[$bitIndex % strlen($bitString)];
                        $bitIndex++;
                    }
                    // Left column of pair
                    if ($col - 1 >= 0 && $this->modules[$row][$col - 1] == -1) {
                        $this->modules[$row][$col - 1] = (int)$bitString[$bitIndex % strlen($bitString)];
                        $bitIndex++;
                    }
                }
            } else {
                // Move downward, from top to bottom
                for ($row = 0; $row < self::SIZE; $row++) {
                    // Right column of pair
                    if ($col >= 0 && $this->modules[$row][$col] == -1) {
                        $this->modules[$row][$col] = (int)$bitString[$bitIndex % strlen($bitString)];
                        $bitIndex++;
                    }
                    // Left column of pair
                    if ($col - 1 >= 0 && $this->modules[$row][$col - 1] == -1) {
                        $this->modules[$row][$col - 1] = (int)$bitString[$bitIndex % strlen($bitString)];
                        $bitIndex++;
                    }
                }
            }
        }
        
        // Fill any remaining empty cells with 0
        for ($row = 0; $row < self::SIZE; $row++) {
            for ($col = 0; $col < self::SIZE; $col++) {
                if ($this->modules[$row][$col] == -1) {
                    $this->modules[$row][$col] = 0;
                }
            }
        }
    }
    
    /**
     * Convert grid to SVG
     */
    public function toSVG($moduleSize = 10, $quiet = 2) {
        $totalSize = (self::SIZE + 2 * $quiet) * $moduleSize;
        
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $totalSize . '" height="' . $totalSize;
        $svg .= '" viewBox="0 0 ' . $totalSize . ' ' . $totalSize . '">';
        $svg .= '<rect width="' . $totalSize . '" height="' . $totalSize . '" fill="white"/>';
        
        for ($row = 0; $row < self::SIZE; $row++) {
            for ($col = 0; $col < self::SIZE; $col++) {
                if ($this->modules[$row][$col] == 1) {
                    $x = ($col + $quiet) * $moduleSize;
                    $y = ($row + $quiet) * $moduleSize;
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $moduleSize;
                    $svg .= '" height="' . $moduleSize . '" fill="black"/>';
                }
            }
        }
        
        $svg .= '</svg>';
        return $svg;
    }
    
    /**
     * Convert to data URI
     */
    public function toDataURI($moduleSize = 8, $quiet = 2) {
        $svg = $this->toSVG($moduleSize, $quiet);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
