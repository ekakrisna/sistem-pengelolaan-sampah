import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { useForm, router, Head, usePage } from "@inertiajs/react";
import { PageProps } from "@/types";
import { User } from "@/types";
import { useState } from "react";
import { Card, CardContent } from "@/Components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogTrigger,
    DialogTitle,
} from "@/Components/ui/dialog";

import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";

interface Props extends PageProps {
    petugas: {
        data: User[];
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}

export default function PetugasIndex({ auth, petugas }: Props) {
    console.log(petugas);
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<User | null>(null);

    const createForm = useForm({ name: "", email: "", phone: "" });
    const editForm = useForm({ name: "", email: "", phone: "" });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route("admin.petugas.store"), {
            onSuccess: () => {
                createForm.reset();
                setOpen(false);
            },
        });
    };

    const handleEdit = (user: User) => {
        setEditing(user);
        editForm.setData({
            name: user.name,
            email: user.email,
            phone: user.phone ?? "",
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editing) return;

        editForm.put(route("admin.petugas.update", editing.id), {
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
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button>Tambah Petugas</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Tambah Petugas</DialogTitle>

                            <form onSubmit={handleCreate} className="space-y-4">
                                <div>
                                    <Label htmlFor="name">Nama</Label>
                                    <Input
                                        id="name"
                                        value={createForm.data.name}
                                        onChange={(e) =>
                                            createForm.setData(
                                                "name",
                                                e.target.value
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        value={createForm.data.email}
                                        onChange={(e) =>
                                            createForm.setData(
                                                "email",
                                                e.target.value
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="phone">No HP</Label>
                                    <Input
                                        id="phone"
                                        value={createForm.data.phone}
                                        onChange={(e) =>
                                            createForm.setData(
                                                "phone",
                                                e.target.value
                                            )
                                        }
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={createForm.processing}
                                >
                                    Simpan
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Table */}
                <Card>
                    <CardContent className="p-4 overflow-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-xs text-left text-gray-500 border-b">
                                    <th className="py-2">Nama</th>
                                    <th>Email</th>
                                    <th>No HP</th>
                                    <th colSpan={2}>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {petugas.data.map((p) => (
                                    <tr
                                        key={p.id}
                                        className="border-b hover:bg-gray-50"
                                    >
                                        <td className="py-2">{p.name}</td>
                                        <td>{p.email}</td>
                                        <td>{p.phone}</td>
                                        <td>
                                            <Button
                                                variant="outline"
                                                onClick={() => handleEdit(p)}
                                            >
                                                Edit
                                            </Button>
                                        </td>
                                        <td>
                                            <Button
                                                variant="destructive"
                                                onClick={() => handleDelete(p)}
                                            >
                                                Hapus
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                {/* Edit Modal */}
                <Dialog open={!!editing} onOpenChange={() => setEditing(null)}>
                    <DialogContent>
                        <DialogTitle>Edit Petugas</DialogTitle>

                        <form onSubmit={submitEdit} className="space-y-4">
                            <div>
                                <Label htmlFor="edit-name">Nama</Label>
                                <Input
                                    id="edit-name"
                                    value={editForm.data.name}
                                    onChange={(e) =>
                                        editForm.setData("name", e.target.value)
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="edit-email">Email</Label>
                                <Input
                                    id="edit-email"
                                    value={editForm.data.email}
                                    onChange={(e) =>
                                        editForm.setData(
                                            "email",
                                            e.target.value
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <Label htmlFor="edit-phone">No HP</Label>
                                <Input
                                    id="edit-phone"
                                    value={editForm.data.phone}
                                    onChange={(e) =>
                                        editForm.setData(
                                            "phone",
                                            e.target.value
                                        )
                                    }
                                />
                            </div>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                            >
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
