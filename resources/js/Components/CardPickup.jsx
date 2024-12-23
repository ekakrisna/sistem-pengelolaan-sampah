import { Avatar, Card, Space, Tag } from "antd";
import React from "react";
import { Typography } from "antd";
import { UserOutline } from "antd-mobile-icons";
const { Paragraph } = Typography;

function CardPickup({ item = {}, ...props }) {
    return (
        <Card {...props}>
            <Paragraph style={{ fontSize: 14 }}>
                Minggu, 6 Januari 2023, 13:00
            </Paragraph>
            <Paragraph>
                <Tag color="#87d068" bordered={false}>
                    Sudah Selesai
                </Tag>
                <Tag color="#cd201f" bordered={false}>
                    Belum Selesai
                </Tag>
            </Paragraph>
            <Space size={[8, 8]} align="center">
                <Avatar icon={<UserOutline />} className="cursor-pointer" />
                <Paragraph style={{ marginBottom: 0, fontSize: 14 }}>
                    Petugas Pertama
                </Paragraph>
            </Space>
        </Card>
    );
}

export default CardPickup;
