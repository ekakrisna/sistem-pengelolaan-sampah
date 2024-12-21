import ApplicationLogo from "@/Components/ApplicationLogo";
import LayoutGeneral from "@/Layouts/GeneralLayout";
import { Link, useForm } from "@inertiajs/react";
import { Button, Form, Input } from "antd";

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: "",
    });

    const submit = (_) => {
        post(route("password.confirm"), {
            onFinish: () => reset("password"),
        });
    };

    return (
        <LayoutGeneral title="Confirm Password">
            <div className="flex flex-col items-center justify-center min-h-screen p-4">
                <div>
                    <Link href="/">
                        <ApplicationLogo className="w-20 h-20 text-gray-500 fill-current" />
                    </Link>
                </div>

                <div className="w-full px-6 py-4 mt-6 overflow-hidden bg-white rounded shadow-sm sm:max-w-md sm:rounded-lg">
                    <div className="mb-4 text-sm text-gray-600">
                        This is a secure area of the application. Please confirm
                        your password before continuing.
                    </div>

                    <Form
                        onFinish={submit}
                        requiredMark={false}
                        layout="vertical"
                        initialValues={data}
                    >
                        <div className="mt-4">
                            <Form.Item
                                name="password"
                                label="Password"
                                rules={[
                                    {
                                        required: true,
                                        message: "Please input your password!",
                                    },
                                ]}
                                validateStatus={
                                    errors.password ? "error" : "success"
                                }
                                help={errors.password}
                            >
                                <Input.Password
                                    placeholder="Password"
                                    onChange={(e) =>
                                        setData("password", e.target.value)
                                    }
                                />
                            </Form.Item>
                        </div>

                        <div className="flex items-center justify-end mt-4">
                            <Button
                                className="ms-4"
                                disabled={processing}
                                loading={processing}
                                htmlType="submit"
                            >
                                Confirm
                            </Button>
                        </div>
                    </Form>
                </div>
            </div>
        </LayoutGeneral>
    );
}
