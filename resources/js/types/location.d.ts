export interface Province {
    id: number;
    name: string;
    code: string;
    meta: string;
    cities: City[];
}

export interface City {
    id: number;
    code: string;
    province_code: string;
    name: string;
    meta: string;
    districts: District[];
}

export interface District {
    id: number;
    code: string;
    city_code: string;
    name: string;
    meta: string;
    villages: Village[];
}

export interface Village {
    id: number;
    code: string;
    district_code: string;
    name: string;
    meta: string;
}
