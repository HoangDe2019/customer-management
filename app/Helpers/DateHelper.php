<?php
// app/Helpers/DateHelper.php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Parse Vietnamese date format (dd/MM/yyyy) to Carbon instance
     */
    public static function parseVietnameseDate(string $date): Carbon
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $date)->startOfDay();
        } catch (\Exception $e) {
            // Try with time
            try {
                return Carbon::createFromFormat('d/m/Y H:i:s', $date, 'Asia/Bangkok');
            } catch (\Exception $e) {
                return Carbon::parse($date);
            }
        }
    }

    /**
     * Format date to Vietnamese format (dd/MM/yyyy HH:mm:ss)
     */
    public static function formatVietnamese(?Carbon $date): string
    {
        return $date ? $date->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') : '';
    }

    /**
     * Format date to Vietnamese date only (dd/MM/yyyy)
     */
    public static function formatVietnameseDateOnly(?Carbon $date): string
    {
        return $date ? $date->format('d/m/Y') : '';
    }
}
