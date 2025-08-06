<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice Email System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the invoice email
    | system in Habitta. These settings control how invoice emails are
    | sent, tracked, and managed throughout the application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Email Settings
    |--------------------------------------------------------------------------
    |
    | These are the default settings used when creating new email batches.
    | They can be overridden on a per-batch basis.
    |
    */
    'defaults' => [
        'template' => 'invoice',
        'subject' => 'Factura de Administración - {{invoice_number}}',
        'sender_name' => env('MAIL_FROM_NAME', config('app.name')),
        'sender_email' => env('MAIL_FROM_ADDRESS'),
        'reply_to' => env('MAIL_REPLY_TO', env('MAIL_FROM_ADDRESS')),
        'include_pdf' => true,
        'include_payment_link' => false,
        'batch_size' => 50, // Number of emails to process in one batch
        'delay_between_emails' => 100, // Milliseconds between emails
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    |
    | Available email templates for invoice emails. Each template should
    | have a corresponding Blade view in resources/views/emails/invoices/
    |
    */
    'templates' => [
        'invoice' => [
            'name' => 'Factura Estándar',
            'description' => 'Template básico para envío de facturas',
            'view' => 'emails.invoices.standard',
            'variables' => [
                'invoice_number',
                'apartment_number', 
                'resident_name',
                'total_amount',
                'due_date',
                'billing_period',
                'company_name',
                'payment_methods',
                'contact_info',
            ],
        ],
        'reminder' => [
            'name' => 'Recordatorio de Pago',
            'description' => 'Template para recordatorios de facturas vencidas',
            'view' => 'emails.invoices.reminder',
            'variables' => [
                'invoice_number',
                'apartment_number',
                'resident_name',
                'total_amount',
                'days_overdue',
                'late_fees',
                'payment_deadline',
                'company_name',
            ],
        ],
        'payment_plan' => [
            'name' => 'Plan de Pagos',
            'description' => 'Template para facturas con plan de pagos',
            'view' => 'emails.invoices.payment_plan',
            'variables' => [
                'invoice_number',
                'apartment_number',
                'resident_name',
                'total_amount',
                'installments',
                'next_payment_date',
                'company_name',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for email delivery and tracking
    |
    */
    'delivery' => [
        'max_retries' => 3,
        'retry_delays' => [5, 30, 120], // Minutes for each retry attempt
        'timeout' => 30, // Seconds
        'track_opens' => true,
        'track_clicks' => true,
        'bounce_handling' => true,
        'unsubscribe_handling' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for queue processing of email batches
    |
    */
    'queue' => [
        'connection' => env('INVOICE_EMAIL_QUEUE_CONNECTION', 'redis'),
        'queue_name' => 'invoice-emails',
        'max_jobs_per_batch' => 100,
        'timeout' => 300, // 5 minutes
        'max_exceptions' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cost Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for tracking email sending costs
    |
    */
    'costs' => [
        'cost_per_email' => env('INVOICE_EMAIL_COST', 0.001), // USD per email
        'currency' => 'USD',
        'track_costs' => true,
        'monthly_budget_limit' => env('INVOICE_EMAIL_MONTHLY_BUDGET', 500),
        'alert_threshold' => 0.8, // Alert when 80% of budget is used
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security configuration for email system
    |
    */
    'security' => [
        'rate_limiting' => [
            'enabled' => true,
            'max_emails_per_hour' => 1000,
            'max_batches_per_day' => 50,
            'max_recipients_per_batch' => 500,
        ],
        'validation' => [
            'require_valid_emails' => true,
            'block_disposable_emails' => false,
            'validate_mx_records' => false,
        ],
        'encryption' => [
            'encrypt_templates' => false,
            'encrypt_recipient_data' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for different email service providers
    |
    */
    'providers' => [
        'smtp' => [
            'name' => 'SMTP',
            'supports_tracking' => false,
            'supports_webhooks' => false,
            'cost_per_email' => 0.0,
        ],
        'sendgrid' => [
            'name' => 'SendGrid',
            'supports_tracking' => true,
            'supports_webhooks' => true,
            'cost_per_email' => 0.00095,
            'webhook_events' => [
                'processed', 'delivered', 'open', 'click',
                'bounce', 'dropped', 'spamreport', 'unsubscribe'
            ],
        ],
        'ses' => [
            'name' => 'Amazon SES',
            'supports_tracking' => true,
            'supports_webhooks' => true,
            'cost_per_email' => 0.0001,
            'webhook_events' => [
                'send', 'delivery', 'open', 'click',
                'bounce', 'complaint', 'reject'
            ],
        ],
        'mailgun' => [
            'name' => 'Mailgun',
            'supports_tracking' => true,
            'supports_webhooks' => true,
            'cost_per_email' => 0.0008,
            'webhook_events' => [
                'delivered', 'opened', 'clicked',
                'bounced', 'dropped', 'complained', 'unsubscribed'
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for logging email activities
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'invoice-emails',
        'level' => 'info',
        'log_successful_sends' => true,
        'log_failed_sends' => true,
        'log_batch_operations' => true,
        'retention_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Alerts
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring email system health
    |
    */
    'monitoring' => [
        'enabled' => true,
        'metrics' => [
            'delivery_rate_threshold' => 0.95, // Alert if delivery rate below 95%
            'bounce_rate_threshold' => 0.05,   // Alert if bounce rate above 5%
            'complaint_rate_threshold' => 0.001, // Alert if complaint rate above 0.1%
        ],
        'alerts' => [
            'channels' => ['mail', 'slack'],
            'recipients' => [
                'admin@example.com',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for cleaning up old email data
    |
    */
    'cleanup' => [
        'enabled' => true,
        'retain_completed_batches_days' => 365,
        'retain_failed_batches_days' => 180,
        'retain_delivery_logs_days' => 90,
        'compress_old_data' => true,
        'archive_before_delete' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing and Development
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments
    |
    */
    'testing' => [
        'mock_email_sending' => env('INVOICE_EMAIL_MOCK', false),
        'test_email_recipient' => env('INVOICE_EMAIL_TEST_RECIPIENT'),
        'simulate_delivery_delays' => env('INVOICE_EMAIL_SIMULATE_DELAYS', false),
        'simulate_failures' => env('INVOICE_EMAIL_SIMULATE_FAILURES', false),
        'failure_rate' => 0.05, // 5% simulated failure rate
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature toggles for email system functionality
    |
    */
    'features' => [
        'batch_scheduling' => true,
        'automatic_retries' => true,
        'cost_tracking' => true,
        'delivery_tracking' => true,
        'template_versioning' => false,
        'a_b_testing' => false,
        'personalization' => true,
        'unsubscribe_management' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for email system API endpoints
    |
    */
    'api' => [
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 60,
        ],
        'webhook_verification' => [
            'enabled' => true,
            'verify_signatures' => true,
            'allowed_ips' => [
                // Provider webhook IPs would be listed here
            ],
        ],
    ],

];