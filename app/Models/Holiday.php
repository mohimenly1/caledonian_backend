<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Holiday extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'repeats_annually', 'description'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'repeats_annually' => 'boolean',
    ];

    /**
     * Scope to find holidays that include a specific date
     */
    public function scopeOnDate(Builder $query, $date)
    {
        $date = Carbon::parse($date);
        
        return $query->where(function($q) use ($date) {
            $q->where(function($q) use ($date) {
                $q->where('start_date', '<=', $date)
                  ->where('end_date', '>=', $date);
            })->orWhere(function($q) use ($date) {
                $q->where('repeats_annually', true)
                  ->whereRaw('MONTH(start_date) = ?', [$date->month])
                  ->whereRaw('DAY(start_date) <= ?', [$date->day])
                  ->whereRaw('MONTH(end_date) = ?', [$date->month])
                  ->whereRaw('DAY(end_date) >= ?', [$date->day]);
            });
        });
    }

    /**
     * Scope to find holidays within a date range
     */
    public function scopeOnDateRange(Builder $query, $startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->where(function($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            })->orWhere(function($q) use ($startDate, $endDate) {
                $q->where('repeats_annually', true)
                  ->where(function($q) use ($startDate, $endDate) {
                      $q->where(function($q) use ($startDate, $endDate) {
                          $q->whereRaw('MONTH(start_date) = ?', [$startDate->month])
                            ->whereRaw('DAY(start_date) <= ?', [$endDate->day])
                            ->whereRaw('MONTH(end_date) = ?', [$startDate->month])
                            ->whereRaw('DAY(end_date) >= ?', [$startDate->day]);
                      })->orWhere(function($q) use ($startDate, $endDate) {
                          // Handle holidays that span across year end (like Dec 20 - Jan 5)
                          $nextYear = $startDate->year + 1;
                          $prevYear = $startDate->year - 1;
                          
                          $q->whereMonth('start_date', 12)
                            ->whereMonth('end_date', 1)
                            ->where(function($q) use ($startDate, $nextYear, $prevYear) {
                                $q->where(function($q) use ($startDate, $nextYear) {
                                    $q->whereRaw('DAY(start_date) <= ?', [$startDate->day])
                                      ->whereYear('start_date', $startDate->year)
                                      ->whereYear('end_date', $nextYear);
                                })->orWhere(function($q) use ($startDate, $prevYear) {
                                    $q->whereRaw('DAY(end_date) >= ?', [$startDate->day])
                                      ->whereYear('start_date', $prevYear)
                                      ->whereYear('end_date', $startDate->year);
                                });
                            });
                      });
                  });
            });
        });
    }

    /**
     * Scope to find upcoming holidays
     */
    public function scopeUpcoming(Builder $query, $days = 30)
    {
        $today = Carbon::today();
        $futureDate = $today->copy()->addDays($days);
        
        return $query->where(function($q) use ($today, $futureDate) {
            $q->where(function($q) use ($today, $futureDate) {
                $q->where('start_date', '>=', $today)
                  ->where('start_date', '<=', $futureDate);
            })->orWhere(function($q) use ($today, $futureDate) {
                $q->where('repeats_annually', true)
                  ->where(function($q) use ($today, $futureDate) {
                      $q->where(function($q) use ($today, $futureDate) {
                          $q->whereRaw('MONTH(start_date) = ?', [$today->month])
                            ->whereRaw('DAY(start_date) >= ?', [$today->day])
                            ->whereRaw('MONTH(end_date) = ?', [$today->month])
                            ->whereRaw('DAY(end_date) <= ?', [$futureDate->day]);
                      })->orWhere(function($q) use ($today, $futureDate) {
                          // Handle holidays that span across months
                          $nextMonth = $today->copy()->addMonth();
                          $q->whereRaw('MONTH(start_date) = ?', [$today->month])
                            ->whereRaw('DAY(start_date) >= ?', [$today->day])
                            ->whereRaw('MONTH(end_date) = ?', [$nextMonth->month])
                            ->whereRaw('DAY(end_date) <= ?', [$futureDate->day]);
                      });
                  });
            });
        })->orderByRaw("
            CASE 
                WHEN repeats_annually = 1 THEN 
                    CONCAT(YEAR(CURDATE()), '-', MONTH(start_date), '-', DAY(start_date))
                ELSE start_date
            END
        ");
    }

    // In Holiday model
public function scopeCurrent($query)
{
    return $query->where(function($q) {
        $q->where('start_date', '<=', now())
          ->where('end_date', '>=', now())
          ->orWhere(function($q) {
              $q->where('repeats_annually', true)
                ->whereMonth('start_date', '<=', now()->month)
                ->whereDay('start_date', '<=', now()->day)
                ->whereMonth('end_date', '>=', now()->month)
                ->whereDay('end_date', '>=', now()->day);
          });
    });
}
}