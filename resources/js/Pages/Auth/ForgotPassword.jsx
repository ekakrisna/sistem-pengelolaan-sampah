import ApplicationLogo from "@/Components/ApplicationLogo";
import LayoutGeneral from "@/Layouts/GeneralLayout";
import { Link, useForm } from "@inertiajs/react";
import { Button, Form, Input } from "antd";

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: "",
    });

    const submit = (_) => post(route("password.email"));

    return (
        <LayoutGeneral title="Forgot Password">
            <div className="flex flex-col items-center justify-center min-h-screen p-4">
                <div>
                    <Link href="/">
                        <ApplicationLogo className="w-20 h-20 text-gray-500 fill-current" />
                    </Link>
                </div>

                <div className="w-full px-6 py-4 mt-6 overflow-hidden bg-white rounded shadow-sm sm:max-w-md sm:rounded-lg">
                    <div className="mb-4 text-sm text-gray-600">
                        Forgot your password? No problem. Just let us know your
                        email address and we will email you a password reset
                        link that will allow you to choose a new one.
                    </div>

                    {status && (
                        <div className="mb-4 text-sm font-medium text-green-600">
                            {status}
                        </div>
                    )}

                    <Form
                        onFinish={submit}
                        requiredMark={false}
                        layout="vertical"
                        initialValues={data}
                    >
                        <Form.Item
                            name="email"
                            label="Email"
                            rules={[
                                {
                                    required: true,
                                    message: "Please input your email!",
                                },
                            ]}
                            validateStatus={errors.email ? "error" : "success"}
                            help={errors.email}
                        >
                            <Input
                                type="email"
                                onChange={(e) =>
                                    setData("email", e.target.value)
                                }
                                placeholder="Email"
                            />
                        </Form.Item>

                        <div className="flex items-center justify-end mt-4">
                            <Button
                                className="ms-4"
                                disabled={processing}
                                loading={processing}
                                htmlType="submit"
                            >
                                Email Password Reset Link
                            </Button>
                        </div>
                    </Form>
                </div>
            </div>
        </LayoutGeneral>
    );
}
