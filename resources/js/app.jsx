import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createRoot } from "react-dom/client";
import { ConfigProvider, App as AntdApp } from "antd";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob("./Pages/**/*.jsx")
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <ConfigProvider
                theme={{
                    token: {
                        fontFamily: "Montserrat, sans-serif",
                        // fontSize: 16,
                        // fontSizeHeading1: 38,
                        // fontSizeHeading2: 30,
                        // fontSizeHeading3: 24,
                        // fontSizeHeading4: 20,
                        // fontSizeHeading5: 16,
                        // borderRadius: 12,
                    },
                    components: {
                        Typography: {
                            titleMarginBottom: 0,
                            titleMarginTop: 0,
                        },
                    },

                    cssVar: true,
                    hashed: true,
                }}
            >
                <AntdApp style={{ backgroundColor: "#f5f5f5" }}>
                    <App {...props} />
                </AntdApp>
            </ConfigProvider>
        );
    },
    progress: {
        color: "#4B5563",
    },
});
