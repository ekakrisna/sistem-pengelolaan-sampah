import React from "react";
import { Layout } from "antd";
import { Head } from "@inertiajs/react";
const { Content } = Layout;

const LayoutGeneral = ({ children, title }) => {
    return (
        <>
            <Head title={title} />
            <Layout>
                <Content>{children}</Content>
            </Layout>
        </>
    );
};

export default LayoutGeneral;
