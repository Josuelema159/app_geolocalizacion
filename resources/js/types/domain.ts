export type Category = {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
};

export type EstablishmentNeed = {
    id: number;
    product_name: string;
    quantity: string | null;
    frequency: string | null;
    notes: string | null;
    status: string;
    category?: Category;
};

export type Establishment = {
    id: number;
    name: string;
    slug: string;
    type: 'tienda' | 'minimercado' | 'supermercado';
    address: string;
    province: string;
    canton: string;
    parish: string | null;
    sector: string | null;
    latitude: number;
    longitude: number;
    reception_status: 'recibe' | 'no_recibe' | 'por_confirmar';
    verification_status: string;
    verified_at: string | null;
    last_verified_at: string | null;
    updated_at: string;
    commercial_contact_name: string | null;
    commercial_phone: string | null;
    commercial_email: string | null;
    authorized_channel: string | null;
    has_refrigeration: boolean;
    refrigeration_capacity: string | null;
    weekly_purchase_volume: number | null;
    supply_frequency: string | null;
    attention_hours: string[] | null;
    supplier_attention_hours: string[] | null;
    entry_requirements: string[] | null;
    purchase_conditions: {
        payment_mode?: string;
        initial_order?: string;
        returns?: string;
    } | null;
    supermarket_purchase_mode: 'sucursal' | 'oficina_central' | null;
    central_office_contact: string | null;
    photo_path: string | null;
    categories?: Category[];
    needs?: EstablishmentNeed[];
};

export type Product = {
    id: number;
    name: string;
    slug: string;
    photo_path: string | null;
    category_id: number | null;
    description: string | null;
    presentation: string;
    wholesale_price: number | null;
    suggested_retail_price: number | null;
    minimum_order: number;
    weekly_capacity: number | null;
    shelf_life_days: number | null;
    storage_conditions: string | null;
    required_temperature_min: number | null;
    required_temperature_max: number | null;
    coverage_zones: string[] | null;
    delivery_frequency: string | null;
    documentation: string[] | null;
    is_public: boolean;
    category?: Category;
};

export type EntrepreneurProfile = {
    id: number;
    business_name: string;
    description: string | null;
    phone: string | null;
    whatsapp: string | null;
    coverage_zones: string[] | null;
    slug: string;
};

export type Opportunity = {
    id: number;
    status: string;
    private_notes: string | null;
    next_follow_up_at: string | null;
    agreed_conditions: string | null;
    closed_reason: string | null;
    establishment?: Establishment;
    proposals?: Proposal[];
};

export type Proposal = {
    id: number;
    message: string;
    status: string;
    merchant_response: string | null;
    responded_at: string | null;
    products?: Product[];
};

export type PaginatedData<T> = {
    data: T[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
    meta?: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
    };
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
    from?: number | null;
    to?: number | null;
};
