import React from "react";
import { Avatar, Dropdown, Flex, Layout, Typography } from "antd";
import { Head, Link, usePage } from "@inertiajs/react";
import {
    AppstoreOutline,
    CalendarOutline,
    UnorderedListOutline,
    UserOutline,
} from "antd-mobile-icons";
const { Content, Footer, Header } = Layout;
const { Paragraph, Title } = Typography;

const LayoutGeneral = ({ children, title }) => {
    const user = usePage().props.auth.user;
    const items = [
        {
            key: "1",
            label: (
                <Link
                    target="_blank"
                    rel="noopener noreferrer"
                    href={route("profile.edit")}
                >
                    Profile
                </Link>
            ),
        },
        {
            key: "2",
            label: (
                <Link
                    href={route("logout")}
                    method="post"
                    as="button"
                    className="text-red-600 "
                >
                    Logout
                </Link>
            ),
        },
    ];
    return (
        <>
            <Head title={title} />
            <Layout
                className="max-w-screen-sm min-h-screen mx-auto"
                // style={{ background: "#fff" }}
            >
                {user && (
                    <>
                        <Header
                            style={{
                                backgroundColor: "#6CB358",
                                padding: "16px",
                                lineHeight: "normal",
                            }}
                            className="sticky top-0 left-0 right-0 z-50 w-full h-auto max-w-screen-sm mx-auto rounded-b-3xl drop-shadow-2xl"
                        >
                            <Flex
                                gap="middle"
                                justify="space-between"
                                align="center"
                            >
                                <div className="text-white">
                                    <Title level={4} style={{ color: "#fff" }}>
                                        Hi, {user.name}
                                    </Title>

                                    <Paragraph
                                        style={{ color: "#ffff", margin: 0 }}
                                    >
                                        {user.email}
                                    </Paragraph>
                                </div>

                                <Dropdown
                                    menu={{ items }}
                                    trigger={["click"]}
                                    placement="bottomRight"
                                    overlayClassName="w-40"
                                >
                                    <Avatar
                                        size={45}
                                        icon={<UserOutline />}
                                        className="cursor-pointer"
                                    />
                                </Dropdown>
                            </Flex>
                        </Header>
                    </>
                )}
                <Content className="p-4">{children}</Content>
                {user && (
                    <Footer
                        className="sticky bottom-0 left-0 right-0 w-full max-w-screen-sm mx-auto drop-shadow-2xl rounded-t-3xl"
                        style={{
                            padding: 0,
                            background: "#fff",
                            boxShadow:
                                "rgba(0, 0, 0, 0.25) 0px 25px 50px -12px",
                        }}
                    >
                        <div className="flex items-center justify-center">
                            <Link
                                href="/"
                                className={
                                    route().current("dashboard")
                                        ? "flex flex-col items-center justify-center flex-1 h-full p-6 text-2xl text-blue-500"
                                        : "flex flex-col items-center justify-center flex-1 h-full p-6 text-2xl text-gray-500"
                                }
                            >
                                <AppstoreOutline />
                            </Link>
                            <Link
                                href="/managements"
                                className={
                                    route().current("managements")
                                        ? "flex flex-col items-center justify-center flex-1 h-full p-6 text-2xl text-blue-500"
                                        : "flex flex-col items-center justify-center flex-1 h-full p-6 text-2xl text-gray-500"
                                }
                            >
                                <CalendarOutline />
                            </Link>
                            <Link
                                href="/transactions"
                                className={
                                    route().current("transactions")
                                        ? "flex flex-col items-center justify-center flex-1 h-full p-6 text-2xl text-blue-500"
                                        : "flex flex-col items-center justify-center flex-1 h-full p-6 text-2xl text-gray-500"
                                }
                            >
                                <UnorderedListOutline />
                            </Link>
                            <Link
                                href="/accounts"
                                className={
                                    route().current("accounts")
                                        ? "flex flex-col items-center justify-center flex-1 h-full p-6 text-2xl text-blue-500"
                                        : "flex flex-col items-center justify-center flex-1 h-full p-6 text-2xl text-gray-500"
                                }
                            >
                                <UserOutline />
                            </Link>
                        </div>
                    </Footer>
                )}
            </Layout>
        </>
    );
};

export default LayoutGeneral;
