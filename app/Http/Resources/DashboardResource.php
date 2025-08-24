<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource['user']),
            'apartment_info' => [
                'number' => $this->resource['apartment']->number ?? null,
                'tower' => $this->resource['apartment']->tower ?? null,
                'type' => $this->resource['apartment']->apartmentType->name ?? null,
            ],
            'account_status' => [
                'current_balance' => $this->resource['current_balance'] ?? 0,
                'next_payment_amount' => $this->resource['next_payment_amount'] ?? 0,
                'next_payment_due' => $this->resource['next_payment_due'] ?? null,
                'is_up_to_date' => ($this->resource['current_balance'] ?? 0) <= 0,
                'overdue_amount' => max(0, $this->resource['current_balance'] ?? 0),
            ],
            'recent_activity' => [
                'total_notifications' => $this->resource['unread_notifications'] ?? 0,
                'pending_visits' => $this->resource['pending_visits'] ?? 0,
                'maintenance_requests' => $this->resource['open_maintenance_requests'] ?? 0,
                'new_announcements' => $this->resource['unread_announcements'] ?? 0,
            ],
            'quick_stats' => [
                'payments_this_year' => $this->resource['payments_this_year'] ?? 0,
                'visits_this_month' => $this->resource['visits_this_month'] ?? 0,
                'last_payment_date' => $this->resource['last_payment_date'] ?? null,
                'last_payment_amount' => $this->resource['last_payment_amount'] ?? 0,
            ],
            'recent_items' => [
                'latest_invoices' => InvoiceResource::collection($this->resource['latest_invoices'] ?? []),
                'recent_payments' => PaymentResource::collection($this->resource['recent_payments'] ?? []),
                'latest_announcements' => AnnouncementResource::collection($this->resource['latest_announcements'] ?? []),
                'recent_visits' => VisitResource::collection($this->resource['recent_visits'] ?? []),
            ],
        ];
    }
}
