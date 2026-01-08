<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Listen for failed FCM notifications
        Event::listen(NotificationFailed::class, function (NotificationFailed $event) {
            if ($event->channel === 'NotificationChannels\Fcm\FcmChannel') {
                $reportData = null;
                if (isset($event->data['report'])) {
                    $report = $event->data['report'];
                    try {
                        $target = method_exists($report, 'target') ? $report->target() : null;
                        $targetString = null;
                        if ($target) {
                            // ✅ Handle MessageTarget object properly
                            if (method_exists($target, 'value')) {
                                $targetString = $target->value();
                            } elseif (method_exists($target, '__toString')) {
                                $targetString = (string)$target;
                            } else {
                                $targetString = get_class($target);
                            }
                        }
                        
                        $reportData = [
                            'is_success' => method_exists($report, 'isSuccess') ? $report->isSuccess() : 'N/A',
                            'is_failure' => method_exists($report, 'isFailure') ? $report->isFailure() : 'N/A',
                            'message' => method_exists($report, 'getMessage') ? $report->getMessage() : 'N/A',
                            'target' => $targetString,
                            'target_type' => $target ? get_class($target) : 'N/A',
                        ];
                        
                        // ✅ Try to get exception details
                        if (method_exists($report, 'exception')) {
                            try {
                                $exception = $report->exception();
                                if ($exception) {
                                    $reportData['exception'] = [
                                        'message' => $exception->getMessage(),
                                        'code' => $exception->getCode(),
                                        'class' => get_class($exception),
                                        'file' => $exception->getFile(),
                                        'line' => $exception->getLine(),
                                    ];
                                    
                                    // ✅ Try to get Firebase error code
                                    if (method_exists($exception, 'getErrors')) {
                                        $reportData['firebase_errors'] = $exception->getErrors();
                                    }
                                    if (method_exists($exception, 'errors')) {
                                        $reportData['firebase_errors'] = $exception->errors();
                                    }
                                } else {
                                    $reportData['exception'] = null;
                                }
                            } catch (\Exception $e) {
                                $reportData['exception_error'] = $e->getMessage();
                            }
                        }
                        
                        // ✅ Log all available methods to understand what we can access
                        $reportData['available_methods'] = array_filter(get_class_methods($report), function($method) {
                            return !str_starts_with($method, '__');
                        });
                    } catch (\Exception $e) {
                        $reportData = [
                            'error_parsing_report' => $e->getMessage(),
                        ];
                    }
                }
                
                Log::error('[AppServiceProvider] ❌ FCM Notification Failed', [
                    'notifiable_id' => $event->notifiable->id ?? 'N/A',
                    'notifiable_class' => get_class($event->notifiable),
                    'notification_class' => get_class($event->notification),
                    'error_data' => $event->data,
                    'report' => $reportData,
                ]);
            }
        });
    }
    
    /**
     * Parse SendReport object for logging
     */
    protected function parseSendReport($report): array
    {
        try {
            $data = [
                'is_success' => method_exists($report, 'isSuccess') ? $report->isSuccess() : 'N/A',
                'is_failure' => method_exists($report, 'isFailure') ? $report->isFailure() : 'N/A',
            ];
            
            // Try to get target safely
            if (method_exists($report, 'target')) {
                try {
                    $target = $report->target();
                    if ($target) {
                        if (method_exists($target, 'value')) {
                            $data['target'] = $target->value();
                        } elseif (method_exists($target, 'type')) {
                            $data['target'] = [
                                'type' => $target->type(),
                                'value' => method_exists($target, 'value') ? $target->value() : 'N/A',
                            ];
                        } else {
                            $data['target'] = get_class($target);
                        }
                        $data['target_class'] = get_class($target);
                    }
                } catch (\Exception $e) {
                    $data['target_error'] = $e->getMessage();
                }
            }
            
            // Try to get message
            if (method_exists($report, 'getMessage')) {
                try {
                    $data['message'] = $report->getMessage();
                } catch (\Exception $e) {
                    $data['message_error'] = $e->getMessage();
                }
            }
            
            // Try to get exception
            if (method_exists($report, 'exception')) {
                try {
                    $exception = $report->exception();
                    if ($exception) {
                        $data['exception'] = [
                            'message' => $exception->getMessage(),
                            'code' => $exception->getCode(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                        ];
                    }
                } catch (\Exception $e) {
                    $data['exception_error'] = $e->getMessage();
                }
            }
            
            return $data;
        } catch (\Exception $e) {
            return [
                'error_parsing_report' => $e->getMessage(),
                'report_class' => get_class($report),
            ];
        }
    }
}
