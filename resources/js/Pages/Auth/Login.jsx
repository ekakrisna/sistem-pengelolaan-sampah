import ApplicationLogo from "@/Components/ApplicationLogo";
import LayoutGeneral from "@/Layouts/GeneralLayout";
import { Link, useForm } from "@inertiajs/react";
import { Button, Checkbox, Form, Input } from "antd";

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    const submit = (_) => {
        post(route("login"), { onFinish: () => reset("password") });
    };

    return (
        <LayoutGeneral title="Login">
            <div className="flex flex-col items-center justify-center min-h-screen p-4">
                <div>
                    <Link href="/">
                        <ApplicationLogo className="w-20 h-20 text-gray-500 fill-current" />
                    </Link>
                </div>

                <div className="w-full px-6 py-4 mt-6 overflow-hidden bg-white rounded shadow-sm sm:max-w-md sm:rounded-lg">
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
                            rules={[
                                {
                                    required: true,
                                    message: "Please input your email!",
                                },
                            ]}
                            validateStatus={errors.email ? "error" : ""}
                            help={errors.email}
                            label="Email"
                        >
                            <Input
                                type="email"
                                placeholder="Email"
                                onChange={(e) =>
                                    setData("email", e.target.value)
                                }
                            />
                        </Form.Item>

                        <Form.Item
                            name="password"
                            rules={[
                                {
                                    required: true,
                                    message: "Please input your password!",
                                },
                            ]}
                            validateStatus={errors.password ? "error" : ""}
                            help={errors.password}
                            label="Password"
                        >
                            <Input.Password
                                type="password"
                                placeholder="Password"
                                onChange={(e) =>
                                    setData("password", e.target.value)
                                }
                            />
                        </Form.Item>

                        <Form.Item name="remember" valuePropName="checked">
                            <Checkbox
                                name="remember"
                                onChange={(e) =>
                                    setData("remember", e.target.checked)
                                }
                            >
                                Remember me
                            </Checkbox>
                        </Form.Item>

                        <div className="flex items-center justify-end mt-4">
                            {canResetPassword && (
                                <Link
                                    href={route("password.request")}
                                    className="text-sm text-gray-600 underline hover:text-gray-900"
                                >
                                    Forgot your password?
                                </Link>
                            )}

                            <Button
                                className="ml-4"
                                disabled={processing}
                                loading={processing}
                                htmlType="submit"
                            >
                                Log in
                            </Button>
                        </div>
                    </Form>
                </div>
            </div>
        </LayoutGeneral>
    );
}
