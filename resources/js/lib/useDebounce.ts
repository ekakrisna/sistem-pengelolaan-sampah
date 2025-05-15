import { useEffect, useRef } from "react";

export function useDebounce(callback: (...args: any[]) => void, delay: number) {
    const timeoutRef = useRef<number | null>(null);

    const debouncedFn = (...args: any[]) => {
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        timeoutRef.current = window.setTimeout(() => {
            callback(...args);
        }, delay);
    };

    useEffect(() => {
        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, []);

    return debouncedFn;
}
