<?php
/**
 * DateTime Helper - Timezone Management
 * UX-CRIT-002 Implementation
 * 
 * Handles timezone conversion and user timezone detection
 * Prevents timezone confusion in job scheduling
 */

class DateTimeHelper
{
    /**
     * Server timezone (configured in config.php)
     */
    private const SERVER_TIMEZONE = 'Europe/Istanbul';
    
    /**
     * Convert user input datetime to server timezone
     * 
     * @param string $datetimeString DateTime string from user input
     * @param string|null $userTimezone User's timezone (if known)
     * @return string DateTime in server timezone (Y-m-d H:i:s format)
     */
    public static function userToServer($datetimeString, $userTimezone = null)
    {
        $userTz = $userTimezone ?? self::SERVER_TIMEZONE;
        
        try {
            $dt = new DateTime($datetimeString, new DateTimeZone($userTz));
            $dt->setTimezone(new DateTimeZone(self::SERVER_TIMEZONE));
            
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log("DateTimeHelper::userToServer error: " . $e->getMessage());
            // Return as-is if conversion fails
            return $datetimeString;
        }
    }
    
    /**
     * Convert server datetime to user timezone for display
     * 
     * @param string $datetimeString DateTime from database
     * @param string|null $userTimezone User's timezone (if known)
     * @return string DateTime in user timezone
     */
    public static function serverToUser($datetimeString, $userTimezone = null)
    {
        $userTz = $userTimezone ?? self::SERVER_TIMEZONE;
        
        try {
            $dt = new DateTime($datetimeString, new DateTimeZone(self::SERVER_TIMEZONE));
            $dt->setTimezone(new DateTimeZone($userTz));
            
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log("DateTimeHelper::serverToUser error: " . $e->getMessage());
            return $datetimeString;
        }
    }
    
    /**
     * Get server timezone name
     */
    public static function getServerTimezone()
    {
        return self::SERVER_TIMEZONE;
    }
    
    /**
     * Get server current time
     */
    public static function getServerTime()
    {
        $dt = new DateTime('now', new DateTimeZone(self::SERVER_TIMEZONE));
        return $dt->format('Y-m-d H:i:s');
    }
    
    /**
     * Check if user timezone differs from server
     * 
     * @param string $userTimezone
     * @return bool True if different
     */
    public static function isTimezoneDifferent($userTimezone)
    {
        return $userTimezone !== self::SERVER_TIMEZONE;
    }
    
    /**
     * Get timezone offset difference (in hours)
     */
    public static function getTimezoneOffset($userTimezone)
    {
        try {
            $serverTz = new DateTimeZone(self::SERVER_TIMEZONE);
            $userTz = new DateTimeZone($userTimezone);
            
            $serverDt = new DateTime('now', $serverTz);
            $userDt = new DateTime('now', $userTz);
            
            $serverOffset = $serverTz->getOffset($serverDt);
            $userOffset = $userTz->getOffset($userDt);
            
            $diffSeconds = $userOffset - $serverOffset;
            return $diffSeconds / 3600; // Convert to hours
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Format datetime for display (Turkish locale)
     */
    public static function formatTurkish($datetimeString, $format = 'd.m.Y H:i')
    {
        try {
            $dt = new DateTime($datetimeString);
            return $dt->format($format);
        } catch (Exception $e) {
            return $datetimeString;
        }
    }
}

