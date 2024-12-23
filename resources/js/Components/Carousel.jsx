import { Carousel } from "antd";
import { LeftOutline, RightOutline } from "antd-mobile-icons";

const CustomCarousel = ({ children, ...props }) => {
    const NextArrow = ({ className, style, onClick }) => (
        <div
            className={`${className} custom-slick-next rounded-full`}
            style={{
                ...style,
                color: "#fff",
                backgroundColor: "#000",
                padding: 6,
                height: "auto",
                width: "auto",
            }}
            onClick={onClick}
        >
            <RightOutline />
        </div>
    );

    const PrevArrow = ({ className, style, onClick }) => (
        <div
            className={`${className} custom-slick-prev rounded-full`}
            style={{
                ...style,
                color: "#fff",
                backgroundColor: "#000",
                padding: 6,
                height: "auto",
                width: "auto",
            }}
            onClick={onClick}
        >
            <LeftOutline />
        </div>
    );

    const settings = {
        nextArrow: <NextArrow />,
        prevArrow: <PrevArrow />,
        infinite: true,
        ...props,
    };

    return <Carousel {...settings}>{children}</Carousel>;
};

export default CustomCarousel;
