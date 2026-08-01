import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import AuthenticatedLayout from './Components/AuthenticatedLayout';
import DialogProvider from './Components/DialogProvider';

const appName = import.meta.env.VITE_APP_NAME || 'Prepárate y Postula Ya';

// Páginas que NO deben mostrar el sidebar
const pagesWithoutLayout = new Set([
    'Welcome',
    'Rendicion/Index',
    'Auth/Login',
    'Auth/Register',
    'Auth/Pendiente',
]);

createInertiaApp({
    title: (title) => `${title} — ${appName}`,
    resolve: (name) => {
        const page = resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx')
        );

        return page.then((module) => {
            const Page = module.default;

            // Usar layout property (Inertia lo preserva entre navegaciones)
            if (!pagesWithoutLayout.has(name)) {
                Page.layout = (page) => (
                    <AuthenticatedLayout>{page}</AuthenticatedLayout>
                );
            }

            return { default: Page };
        });
    },
    setup({ el, App, props }) {
        const WrappedApp = (appProps) => (
            <DialogProvider>
                <App {...appProps} />
            </DialogProvider>
        );
        if (el.dataset.server) {
            hydrateRoot(el, <WrappedApp {...props} />);
        } else {
            createRoot(el).render(<WrappedApp {...props} />);
        }
    },
    progress: {
        color: '#00f0ff',
        showSpinner: true,
    },
});
