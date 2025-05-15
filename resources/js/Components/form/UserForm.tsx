import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { useEffect, useState } from "react";
import { City, District, Province, Village } from "@/types/location";
import { UserFormData } from "@/types";
import { Textarea } from "../ui/textarea";

interface Props {
    provinces: Province[];
    value: UserFormData;
    onChange: (value: UserFormData) => void;
}

export default function PetugasForm({ provinces, value, onChange }: Props) {
    const [cities, setCities] = useState<City[]>([]);
    const [districts, setDistricts] = useState<District[]>([]);
    const [villages, setVillages] = useState<Village[]>([]);

    useEffect(() => {
        const province = provinces.find((p) => p.code == value.province_id);
        setCities(province?.cities || []);
    }, [value.province_id, provinces]);

    useEffect(() => {
        if (value.city_id) {
            fetch(`/api/districts/${value.city_id}`)
                .then((res) => res.json())
                .then(setDistricts);
        }
    }, [value.city_id]);

    useEffect(() => {
        if (value.district_id) {
            fetch(`/api/villages/${value.district_id}`)
                .then((res) => res.json())
                .then(setVillages);
        }
    }, [value.district_id]);

    return (
        <div className="space-y-4">
            <div>
                <Label htmlFor="name">Nama</Label>
                <Input
                    id="name"
                    placeholder="Nama Lengkap"
                    value={value.name}
                    onChange={(e) =>
                        onChange({ ...value, name: e.target.value })
                    }
                />
            </div>
            <div>
                <Label htmlFor="email">Email</Label>
                <Input
                    id="email"
                    placeholder="Email"
                    value={value.email}
                    onChange={(e) =>
                        onChange({ ...value, email: e.target.value })
                    }
                />
            </div>
            <div>
                <Label htmlFor="phone">No HP</Label>
                <Input
                    id="phone"
                    placeholder="No HP"
                    value={value.phone}
                    onChange={(e) =>
                        onChange({ ...value, phone: e.target.value })
                    }
                />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <Label>Provinsi</Label>
                    <Select
                        value={value.province_id?.toString() || ""}
                        onValueChange={(val) =>
                            onChange({
                                ...value,
                                province_id: parseInt(val),
                                city_id: "",
                                district_id: "",
                                village_id: "",
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="-- Pilih Provinsi --" />
                        </SelectTrigger>
                        <SelectContent>
                            {provinces.map((p) => (
                                <SelectItem key={p.id} value={p.id.toString()}>
                                    {p.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label>Kota/Kabupaten</Label>
                    <Select
                        value={value.city_id?.toString() || ""}
                        onValueChange={(val) =>
                            onChange({
                                ...value,
                                city_id: parseInt(val),
                                district_id: "",
                                village_id: "",
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="-- Pilih Kota --" />
                        </SelectTrigger>
                        <SelectContent>
                            {cities.map((c) => (
                                <SelectItem key={c.id} value={c.id.toString()}>
                                    {c.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label>Kecamatan</Label>
                    <Select
                        value={value.district_id?.toString() || ""}
                        onValueChange={(val) =>
                            onChange({
                                ...value,
                                district_id: parseInt(val),
                                village_id: "",
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="-- Pilih Kecamatan --" />
                        </SelectTrigger>
                        <SelectContent>
                            {districts.map((d) => (
                                <SelectItem key={d.id} value={d.id.toString()}>
                                    {d.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label>Desa</Label>
                    <Select
                        value={value.village_id?.toString() || ""}
                        onValueChange={(val) =>
                            onChange({
                                ...value,
                                village_id: parseInt(val),
                            })
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="-- Pilih Desa --" />
                        </SelectTrigger>
                        <SelectContent>
                            {villages.map((v) => (
                                <SelectItem key={v.id} value={v.id.toString()}>
                                    {v.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <div>
                <Label>Alamat</Label>
                <Textarea
                    id="address_detail"
                    value={value.address_detail}
                    placeholder="Alamat Lengkap"
                    onChange={(e) =>
                        onChange({
                            ...value,
                            address_detail: e.target.value,
                        })
                    }
                />
            </div>
        </div>
    );
}
