export interface User {
    id: number | "";
    name: string;
    email: string;
    email_verified_at?: string;
    phone: string | "";
    role: "admin" | "petugas" | "customer";
    province_id: number | "";
    city_id: number | "";
    district_id: number | "";
    village_id: number | "";
    address_detail: string | "";
    created_at: string;
    updated_at: string;
}

export interface UserFormData {
    // id: string | number;
    name: string;
    email: string;
    phone?: string;
    role: string;
    province_id?: string | number;
    city_id?: string | number;
    district_id?: string | number;
    village_id?: string | number;
    address_detail?: string;
    [key: string]: any;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>
> = T & {
    auth: {
        user: User;
    };
};
