import ApplicationLogo from "@/Components/ApplicationLogo";
import LayoutGeneral from "@/Layouts/GeneralLayout";
import { Link, useForm } from "@inertiajs/react";
import { Button, Form, Input } from "antd";

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
    });

    const submit = (_) => {
        post(route("register"), {
            onFinish: () => reset("password", "password_confirmation"),
        });
    };

    return (
        <LayoutGeneral title="Register">
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
                            name="name"
                            rules={[
                                {
                                    required: true,
                                    message: "Please input your name!",
                                },
                            ]}
                            validateStatus={errors.name ? "error" : "success"}
                            help={errors.name}
                            label="Name"
                        >
                            <Input
                                placeholder="Name"
                                onChange={(e) =>
                                    setData("name", e.target.value)
                                }
                            />
                        </Form.Item>

                        <Form.Item
                            name="email"
                            rules={[
                                {
                                    required: true,
                                    message: "Please input your email!",
                                },
                            ]}
                            validateStatus={errors.email ? "error" : "success"}
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
                            validateStatus={
                                errors.password ? "error" : "success"
                            }
                            help={errors.password}
                            label="Password"
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
                            label="Password Confirmation"
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
                            <Link
                                href={route("login")}
                                className="text-sm text-gray-600 underline hover:text-gray-900"
                            >
                                Already registered?
                            </Link>

                            <Button
                                htmlType="submit"
                                className="ms-4"
                                disabled={processing}
                                loading={processing}
                            >
                                Register
                            </Button>
                        </div>
                    </Form>
                </div>
            </div>
        </LayoutGeneral>
    );
}
