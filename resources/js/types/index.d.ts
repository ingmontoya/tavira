import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    permissions?: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: LucideIcon;
    isActive?: boolean;
    items?: NavItem[];
    tourId?: string;
    visible?: boolean;
    disabled?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

// Conjunto Configuration Types
export interface FloorConfiguration {
    apartments_count?: number;
    type_assignments?: Record<number, number>; // position -> apartment_type_id
}

export interface PenthouseConfiguration {
    apartments_count: number;
}

export interface ConfigurationMetadata {
    architect?: string;
    construction_year?: string;
    common_areas?: string[];
    security_features?: string[];
    floor_configuration?: Record<number, FloorConfiguration>; // floor -> configuration
    penthouse_configuration?: PenthouseConfiguration;
}

export interface ApartmentType {
    id?: number;
    conjunto_config_id?: number;
    name: string;
    description: string;
    area_sqm: number;
    bedrooms: number;
    bathrooms: number;
    has_balcony: boolean;
    has_laundry_room: boolean;
    has_maid_room: boolean;
    coefficient: number;
    administration_fee: number;
    floor_positions: number[];
    features?: string[];
    apartments_count?: number;
}

export interface ConjuntoConfig {
    id?: number;
    name: string;
    description: string;
    number_of_towers: number;
    floors_per_tower: number;
    apartments_per_floor: number;
    is_active: boolean;
    tower_names: string[];
    configuration_metadata: ConfigurationMetadata;
    apartment_types?: ApartmentType[];
    apartment_types_count?: number;
    apartments_count?: number;
    estimated_apartments_count?: number;
    total_apartments?: number;
    created_at?: string;
    updated_at?: string;
}

export interface Apartment {
    id: number;
    apartment_type_id: number;
    conjunto_config_id: number;
    number: string;
    tower: string;
    floor: number;
    position_on_floor: number;
    status: 'Available' | 'Occupied' | 'Maintenance' | 'Reserved';
    monthly_fee: number;
    utilities?: Record<string, any>;
    features?: string[];
    apartment_type?: ApartmentType;
    created_at: string;
    updated_at: string;
    full_address?: string;
}

// Invoice Email System Types
export interface Invoice {
    id: number;
    invoice_number: string;
    apartment: {
        id: number;
        number: string;
        tower: string;
        full_address: string;
    } | null;
    type: string;
    type_label: string;
    billing_date: string;
    due_date: string;
    billing_period_label: string;
    total_amount: number;
    paid_amount: number;
    balance_due: number;
    status: 'pending' | 'partial' | 'paid' | 'overdue' | 'cancelled';
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    days_overdue?: number;
    items: InvoiceItem[];
    can_send_email: boolean;
    email_deliveries?: InvoiceEmailDelivery[];
}

export interface InvoiceItem {
    id: number;
    description: string;
    quantity: number;
    unit_price: number;
    total_price: number;
}

export interface InvoiceEmailBatch {
    id: number;
    name: string;
    description?: string;
    status: 'borrador' | 'listo' | 'procesando' | 'completado' | 'con_errores';
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    total_invoices: number;
    sent_count: number;
    failed_count: number;
    pending_count: number;
    created_at: string;
    updated_at: string;
    sent_at?: string;
    completed_at?: string;
    created_by: User;
    deliveries: InvoiceEmailDelivery[];
    can_edit: boolean;
    can_send: boolean;
    can_delete: boolean;
}

export interface InvoiceEmailDelivery {
    id: number;
    batch_id: number;
    invoice_id: number;
    recipient_email: string;
    recipient_name?: string;
    status: 'pendiente' | 'enviado' | 'fallido' | 'rebotado';
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    sent_at?: string;
    failed_at?: string;
    error_message?: string;
    retry_count: number;
    max_retries: number;
    created_at: string;
    updated_at: string;
    invoice: Invoice;
    can_retry: boolean;
}

export interface EmailTemplate {
    id: number;
    name: string;
    subject: string;
    body: string;
    is_active: boolean;
    variables: string[];
}

// Email System API Responses
export interface InvoiceEmailBatchResponse {
    data: InvoiceEmailBatch[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface EligibleInvoice {
    id: number;
    invoice_number: string;
    apartment_number: string;
    apartment_id: number;
    status: 'pending' | 'partial' | 'paid' | 'overdue' | 'cancelled';
    type: 'monthly' | 'individual' | 'late_fee';
    total_amount: number;
    due_date: string;
    billing_period_year: number;
    billing_period_month: number;
    billing_period_label: string;
    recipients: {
        name: string;
        email: string;
        type: string;
    }[];
}

export interface EligibleInvoicesResponse {
    data: EligibleInvoice[];
    total: number;
}

// Filter interfaces
export interface InvoiceEmailBatchFilters {
    search?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
}

export interface EligibleInvoiceFilters {
    search?: string;
    apartment_id?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    type?: string;
}

// Form data interfaces
export interface CreateInvoiceEmailBatchData {
    name: string;
    description?: string;
    invoice_ids: number[];
    template_id?: number;
    send_immediately?: boolean;
}

export interface BatchProgressData {
    total: number;
    sent: number;
    failed: number;
    pending: number;
    percentage: number;
    current_status: string;
}

// Correspondence System Types
export interface CorrespondenceAttachment {
    id: number;
    correspondence_id: number;
    filename: string;
    original_filename: string;
    file_path: string;
    file_size: number;
    mime_type: string;
    type: 'photo' | 'signature' | 'document';
    created_at: string;
    updated_at: string;
}

export interface Correspondence {
    id: number;
    tracking_number: string;
    sender_name: string;
    sender_company?: string | null;
    type: 'package' | 'letter' | 'document' | 'other';
    type_label: string;
    description: string;
    apartment: {
        id: number;
        number: string;
        tower: string;
        floor: number;
        full_address: string;
        residents?: Array<{
            id: number;
            first_name: string;
            last_name: string;
            full_name: string;
            resident_type: string;
            status: string;
        }>;
    } | null;
    status: 'received' | 'delivered' | 'pending_signature' | 'returned';
    status_label: string;
    status_badge: {
        text: string;
        class: string;
    };
    received_at: string;
    delivered_at?: string | null;
    returned_at?: string | null;
    requires_signature: boolean;
    recipient_name?: string | null;
    recipient_document?: string | null;
    notes?: string | null;
    received_by: User;
    delivered_by?: User | null;
    attachments: CorrespondenceAttachment[];
    created_at: string;
    updated_at: string;
    can_edit: boolean;
    can_deliver: boolean;
    can_return: boolean;
    days_pending?: number;
}

export interface CorrespondenceFilters {
    search?: string;
    apartment_id?: string;
    tower?: string;
    type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
    received_by?: string;
    requires_signature?: boolean;
}

export interface CorrespondenceResponse {
    data: Correspondence[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    prev_page_url?: string;
    next_page_url?: string;
}

export interface CreateCorrespondenceData {
    sender_name: string;
    sender_company?: string;
    type: 'package' | 'letter' | 'document' | 'other';
    description: string;
    apartment_id?: number;
    requires_signature: boolean;
    recipient_name?: string;
    recipient_document?: string;
    notes?: string;
    attachments?: File[];
}

export interface DeliveryData {
    recipient_name?: string;
    recipient_document?: string;
    signature?: string;
    notes?: string;
    delivered_at: string;
}

export interface CorrespondenceStats {
    total: number;
    received: number;
    delivered: number;
    pending_signature: number;
    returned: number;
    pending_percentage: number;
    delivered_percentage: number;
}

// Email Template Types
export interface EmailTemplate {
    id: number;
    name: string;
    description?: string;
    type: 'invoice' | 'payment_receipt' | 'payment_reminder' | 'welcome' | 'announcement' | 'custom';
    subject: string;
    body: string;
    variables: string[];
    design_config: EmailTemplateDesignConfig;
    is_active: boolean;
    is_default: boolean;
    created_by: number;
    updated_by: number;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
    created_by_user?: User;
    updated_by_user?: User;
}

export interface EmailTemplateDesignConfig {
    primary_color: string;
    secondary_color: string;
    accent_color?: string;
    background_color: string;
    text_color: string;
    font_family: string;
    header_style: 'modern' | 'classic' | 'gradient' | 'success' | 'warning';
    footer_style: 'modern' | 'simple' | 'classic';
    button_style: 'rounded' | 'square';
    layout: 'centered' | 'full-width';
    max_width: string;
    show_logo: boolean;
    show_contact_info: boolean;
}

export interface EmailTemplateResponse {
    data: EmailTemplate[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    prev_page_url?: string;
    next_page_url?: string;
}

export interface CreateEmailTemplateData {
    name: string;
    description?: string;
    type: string;
    subject: string;
    body: string;
    variables?: string[];
    design_config?: Partial<EmailTemplateDesignConfig>;
    is_active?: boolean;
    is_default?: boolean;
}

export interface EmailTemplatePreviewData {
    subject: string;
    body: string;
    design_config: EmailTemplateDesignConfig;
}
