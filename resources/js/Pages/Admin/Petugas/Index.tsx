import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { useForm, router, Head, usePage } from "@inertiajs/react";
import { PageProps, UserFormData } from "@/types";
import { User } from "@/types";
import { useState } from "react";
import { Card, CardContent } from "@/Components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogTrigger,
    DialogTitle,
} from "@/Components/ui/dialog";
import {
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableCell,
    TableHead,
    TableCaption,
} from "@/Components/ui/table";

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import PetugasForm from "@/Components/form/UserForm";
import { Province } from "@/types/location";

interface Props extends PageProps {
    petugas: {
        data: User[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        search: string;
        page: number;
        page_size: number;
    };
    provinces: Province[];
}

export default function PetugasIndex({
    auth,
    petugas,
    filters,
    provinces,
}: Props) {
    console.log("PROVINCES", provinces);
    const props = usePage().props;
    console.log("PROPS", props);
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<UserFormData | null>(null);
    const [search, setSearch] = useState(filters.search || "");

    const { data, setData, post, put, processing, reset } =
        useForm<UserFormData>({
            // id: "",
            name: "",
            email: "",
            phone: "",
            role: "petugas",
            province_id: "",
            city_id: "",
            district_id: "",
            village_id: "",
            address_detail: "",
        });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();

        router.get(
            route("admin.petugas.index"),
            { search },
            { preserveScroll: true, replace: true }
        );
    };

    const handleReset = () => {
        setSearch("");
        router.get(route("admin.petugas.index"));
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        post(route("admin.petugas.store"), {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    const handleEdit = (user: UserFormData) => {
        console.log("user", user);
        setEditing(user);
        setData((previousData) => ({
            ...previousData,
            name: user.name,
            email: user.email,
            phone: user.phone,
            province_id: user.province_id,
            city_id: user.city_id,
            district_id: user.district_id,
            village_id: user.village_id,
            address_detail: user.address_detail,
        }));
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editing) return;

        put(route("admin.petugas.update", editing.id), {
            onSuccess: () => setEditing(null),
        });
    };

    const handleDelete = (user: User) => {
        if (confirm(`Hapus petugas ${user.name}?`)) {
            router.delete(route("admin.petugas.destroy", user.id));
        }
    };

    return (
        <AuthenticatedLayout
            // user={auth.user}
            header={
                <h2 className="text-xl font-semibold text-gray-800">
                    Manajemen Petugas
                </h2>
            }
        >
            <Head title="Manajemen Petugas" />
            <div className="px-4 py-6 mx-auto space-y-6 max-w-7xl sm:px-6 lg:px-8">
                {/* Tambah Petugas */}
                <div className="flex justify-end">
                    <Dialog
                        open={open}
                        onOpenChange={() => {
                            setOpen(!open);
                            setEditing(null);
                            setData({
                                name: "",
                                email: "",
                                phone: "",
                                role: "petugas",
                                province_id: "",
                                city_id: "",
                                district_id: "",
                                village_id: "",
                                address_detail: "",
                            });
                        }}
                    >
                        <DialogTrigger asChild>
                            <Button>Tambah Petugas</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Tambah Petugas</DialogTitle>

                            <form onSubmit={handleCreate} className="space-y-4">
                                <PetugasForm
                                    provinces={provinces}
                                    value={data}
                                    onChange={setData}
                                />
                                <Button type="submit" disabled={processing}>
                                    Simpan
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="p-4 overflow-auto">
                        {/* Search */}
                        <form
                            onSubmit={handleSearch}
                            className="flex max-w-md gap-2 mb-4"
                        >
                            <Input
                                type="text"
                                placeholder="Cari petugas..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="flex-1"
                            />
                            <Button type="submit">Search</Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={handleReset}
                            >
                                Reset
                            </Button>
                        </form>

                        <Table>
                            <TableCaption>
                                Daftar Petugas yang Terdaftar
                            </TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>No HP</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {petugas.data.map((p) => (
                                    <TableRow key={p.id}>
                                        <TableCell>{p.name}</TableCell>
                                        <TableCell>{p.email}</TableCell>
                                        <TableCell>{p.phone}</TableCell>
                                        <TableCell className="space-x-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleEdit(p)}
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => handleDelete(p)}
                                            >
                                                Hapus
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Edit Modal */}
                <Dialog open={!!editing} onOpenChange={() => setEditing(null)}>
                    <DialogContent>
                        <DialogTitle>Edit Petugas</DialogTitle>

                        <form onSubmit={submitEdit} className="space-y-4">
                            <PetugasForm
                                provinces={provinces}
                                value={data}
                                onChange={setData}
                            />
                            <Button type="submit" disabled={processing}>
                                Perbarui
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Pagination */}
                <div className="flex justify-center gap-2">
                    {petugas.links.map((link, i) =>
                        link.url ? (
                            <Button
                                key={i}
                                variant={link.active ? "default" : "ghost"}
                                onClick={() => router.visit(link.url || "")}
                            >
                                <span
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            </Button>
                        ) : null
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
