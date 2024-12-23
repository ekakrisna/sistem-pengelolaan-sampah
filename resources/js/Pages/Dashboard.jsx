import CustomCarousel from "@/Components/Carousel";
import GeneralLayout from "@/Layouts/GeneralLayout";
import { Link } from "@inertiajs/react";
import { Card, Image, Tag, Typography } from "antd";
const { Paragraph, Text } = Typography;

export default function Dashboard() {
    return (
        <GeneralLayout title="Dashboard">
            <Image
                className="object-cover w-full rounded-lg"
                rootClassName="w-full mb-4"
                height={250}
                src="images/illustration-with-organic-farm-design_23-2148437844.png"
                preview={{ mask: false, destroyOnClose: true }}
            />
            <section className="mb-4">
                <div className="flex items-center justify-between mb-2">
                    <Paragraph
                        style={{ marginBottom: 0 }}
                        className="font-semibold"
                    >
                        Lokasi Saya
                    </Paragraph>
                    <Link href="#">See all</Link>
                </div>
                <CustomCarousel
                    dots={false}
                    arrows
                    className="carousel-listing"
                    responsive={[
                        { breakpoint: 640, settings: { slidesToShow: 1 } },
                    ]}
                >
                    <div>
                        <div className="flex items-center gap-3 p-3 bg-white rounded shadow-xl">
                            <Image
                                className="object-cover w-full rounded"
                                style={{ height: 147 }}
                                src="images/unnamed.png"
                                preview={{
                                    visible: false,
                                    mask: false,
                                    destroyOnClose: true,
                                }}
                            />
                            <div>
                                <Paragraph
                                    className="font-semibold"
                                    style={{ marginBottom: 0 }}
                                >
                                    Farm 1
                                </Paragraph>
                                <Paragraph
                                    style={{ marginBottom: 8, fontSize: 12 }}
                                >
                                    Jl. Jend. Sudirman No. 1, Jakarta Pusat
                                </Paragraph>
                                <div className="flex flex-wrap gap-1 mb-2 text-xs">
                                    <Tag
                                        color="#87d068"
                                        bordered={false}
                                        style={{ marginInlineEnd: 0 }}
                                    >
                                        Organic
                                    </Tag>
                                    <Tag
                                        color="#cd201f"
                                        bordered={false}
                                        style={{ marginInlineEnd: 0 }}
                                    >
                                        Non-Organic
                                    </Tag>
                                    <Tag
                                        color="#fadb14"
                                        bordered={false}
                                        style={{ marginInlineEnd: 0 }}
                                    >
                                        Hazardous
                                    </Tag>
                                </div>
                                <Paragraph
                                    style={{ marginBottom: 0, fontSize: 12 }}
                                >
                                    Sabtu, 12 Maret 2022, 08:00 WIB
                                </Paragraph>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div className="flex items-center gap-3 p-3 bg-white rounded shadow-xl">
                            <Image
                                className="object-cover w-full rounded"
                                style={{ height: 147 }}
                                src="images/unnamed.png"
                                preview={{
                                    visible: false,
                                    mask: false,
                                    destroyOnClose: true,
                                }}
                            />
                            <div>
                                <Paragraph
                                    className="font-semibold"
                                    style={{ marginBottom: 0 }}
                                >
                                    Farm 2
                                </Paragraph>
                                <Paragraph
                                    style={{ marginBottom: 8, fontSize: 12 }}
                                >
                                    Jl. Jend. Sudirman No. 1, Jakarta Pusat
                                </Paragraph>
                                <div className="flex flex-wrap gap-1 mb-2 text-xs">
                                    <Tag
                                        color="#87d068"
                                        bordered={false}
                                        style={{ marginInlineEnd: 0 }}
                                    >
                                        Organic
                                    </Tag>
                                    <Tag
                                        color="#cd201f"
                                        bordered={false}
                                        style={{ marginInlineEnd: 0 }}
                                    >
                                        Non-Organic
                                    </Tag>
                                    <Tag
                                        color="#fadb14"
                                        bordered={false}
                                        style={{ marginInlineEnd: 0 }}
                                    >
                                        Hazardous
                                    </Tag>
                                </div>
                                <Paragraph
                                    style={{ marginBottom: 0, fontSize: 12 }}
                                >
                                    Sabtu, 12 Maret 2022, 08:00 WIB
                                </Paragraph>
                            </div>
                        </div>
                    </div>
                </CustomCarousel>
            </section>

            <section className="grid grid-cols-2 gap-2 mb-4">
                <Card
                    style={{ backgroundColor: "#6cb358", padding: 1 }}
                    styles={{ body: { padding: 12 } }}
                >
                    <Paragraph
                        strong
                        style={{ color: "#fff", marginBottom: 8 }}
                    >
                        Next Pickup
                    </Paragraph>
                    <Paragraph style={{ marginBottom: 8, color: "#fff" }}>
                        Scheduled for:{" "}
                        <Text strong style={{ color: "#fff" }}>
                            24 Dec 2024, 10:00 AM
                        </Text>
                    </Paragraph>
                </Card>
                <Card
                    style={{ backgroundColor: "#eab308", padding: 1 }}
                    styles={{ body: { padding: 12 } }}
                >
                    <Paragraph
                        strong
                        style={{ color: "#fff", marginBottom: 8 }}
                    >
                        Payment Status
                    </Paragraph>
                    <Paragraph style={{ marginBottom: 0, color: "#fff" }}>
                        Package:{" "}
                        <Text strong style={{ color: "#fff" }}>
                            Monthly
                        </Text>
                    </Paragraph>
                    <Paragraph style={{ marginBottom: 0, color: "#fff" }}>
                        Status:{" "}
                        <Text strong style={{ color: "#fff" }}>
                            Active
                        </Text>
                    </Paragraph>
                </Card>

                <Card
                    style={{ backgroundColor: "#3b82f6", padding: 1 }}
                    styles={{ body: { padding: 12 } }}
                >
                    <Paragraph
                        strong
                        style={{ color: "#fff", marginBottom: 8 }}
                    >
                        Total Locations
                    </Paragraph>
                    <Paragraph style={{ marginBottom: 0, color: "#fff" }}>
                        You have added{" "}
                        <Text strong style={{ color: "#fff" }}>
                            3 locations
                        </Text>
                    </Paragraph>
                </Card>

                <Card
                    style={{ backgroundColor: "#ef4444", padding: 1 }}
                    styles={{ body: { padding: 12 } }}
                >
                    <Paragraph
                        strong
                        style={{ color: "#fff", marginBottom: 8 }}
                    >
                        Feedback{" "}
                    </Paragraph>
                    <Paragraph style={{ marginBottom: 0, color: "#fff" }}>
                        We value your input!{" "}
                        <Link
                            href="#"
                            style={{
                                color: "#fff",
                                textDecoration: "underline",
                            }}
                        >
                            Submit feedback
                        </Link>
                    </Paragraph>
                </Card>
            </section>

            <section className="mb-4">
                <div className="flex items-center justify-between mb-2">
                    <Paragraph
                        style={{ marginBottom: 0 }}
                        className="font-semibold"
                    >
                        Riwayat Transaksi
                    </Paragraph>
                    <Link href="#">See all</Link>
                </div>
                <ul className="mt-2">
                    <li className="p-4 mb-2 bg-white rounded shadow">
                        <p className="text-sm">
                            20 Desember 2024 - Rp 150.000 - Paket Bulanan -{" "}
                            <Text type="success">Berhasil</Text>
                        </p>
                    </li>
                    <li className="p-4 bg-white rounded shadow">
                        <p className="text-sm">
                            25 November 2024 - Rp 150.000 - Paket Bulanan -{" "}
                            <Text type="danger">Gagal</Text>
                        </p>
                    </li>
                </ul>
            </section>
        </GeneralLayout>
    );
}
