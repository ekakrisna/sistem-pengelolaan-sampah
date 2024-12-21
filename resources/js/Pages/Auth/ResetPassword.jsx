import ApplicationLogo from "@/Components/ApplicationLogo";
import LayoutGeneral from "@/Layouts/GeneralLayout";
import { Link, useForm } from "@inertiajs/react";
import { Button, Form, Input } from "antd";

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email,
        password: "",
        password_confirmation: "",
    });

    const submit = (_) => {
        post(route("password.store"), {
            onFinish: () => reset("password", "password_confirmation"),
        });
    };

    return (
        <LayoutGeneral title="Reset Password">
            <div className="flex flex-col items-center justify-center min-h-screen p-4">
                <div>
                    <Link href="/">
                        <ApplicationLogo className="w-20 h-20 text-gray-500 fill-current" />
                    </Link>
                </div>

                <div className="w-full px-6 py-4 mt-6 overflow-hidden bg-white rounded shadow-sm sm:max-w-md sm:rounded-lg">
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
                                placeholder="Email"
                                type="email"
                                onChange={(e) =>
                                    setData("email", e.target.value)
                                }
                            />
                        </Form.Item>

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

                        <Form.Item
                            name="password_confirmation"
                            label="Password Confirmation"
                            rules={[
                                {
                                    required: true,
                                    message:
                                        "Please input your password confirmation!",
                                },
                            ]}
                            validateStatus={
                                errors.password_confirmation
                                    ? "error"
                                    : "success"
                            }
                            help={errors.password_confirmation}
                        >
                            <Input.Password
                                placeholder="Password Confirmation"
                                onChange={(e) =>
                                    setData(
                                        "password_confirmation",
                                        e.target.value
                                    )
                                }
                            />
                        </Form.Item>

                        <div className="flex items-center justify-end mt-4">
                            <Button
                                htmlType="submit"
                                className="ml-4"
                                disabled={processing}
                                loading={processing}
                            >
                                Reset Password
                            </Button>
                        </div>
                    </Form>
                </div>
            </div>
        </LayoutGeneral>
    );
}
