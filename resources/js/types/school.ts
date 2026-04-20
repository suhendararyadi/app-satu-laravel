export interface School {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
    // School fields
    npsn: string | null;
    school_type: 'Sma' | 'Smk' | 'Ma' | null;
    address: string | null;
    city: string | null;
    province: string | null;
    postal_code: string | null;
    phone: string | null;
    email: string | null;
    logo_path: string | null;
    accreditation: string | null;
    principal_name: string | null;
    founded_year: number | null;
    vision: string | null;
    mission: string | null;
    description: string | null;
    website_theme: string | null;
    custom_domain: string | null;
    created_at: string;
    updated_at: string;
}

export interface Page {
    id: number;
    title: string;
    slug: string;
    content: string;
    is_published: boolean;
    sort_order: number;
    meta_description: string | null;
    created_at: string;
    updated_at: string;
}

export interface Post {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    content: string;
    featured_image_path: string | null;
    is_published: boolean;
    published_at: string | null;
    author: { id: number; name: string };
    meta_description: string | null;
    created_at: string;
    updated_at: string;
}

export interface Gallery {
    id: number;
    title: string;
    description: string | null;
    is_published: boolean;
    images: GalleryImage[];
    images_count?: number;
    created_at: string;
    updated_at: string;
}

export interface GalleryImage {
    id: number;
    image_path: string;
    caption: string | null;
    sort_order: number;
}
