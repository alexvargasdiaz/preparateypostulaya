import { AlertTriangle } from 'lucide-react';

export default function DisclaimerBanner() {
    return (
        <div className="border-y border-upc-red/20 bg-upc-red/5">
            <div className="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10 py-4">
                <div className="flex items-start gap-3 font-sans text-xs leading-relaxed text-upc-grey">
                    <AlertTriangle className="mt-0.5 h-4 w-4 flex-shrink-0 text-upc-red" />
                    <p>
                        <strong className="text-tertiary-800">Aviso importante:</strong> Estos exámenes son{' '}
                        <strong className="text-tertiary-800">simulacros de preparación</strong> y no constituyen procesos de
                        admisión oficiales de ninguna universidad. Los resultados son referenciales.
                    </p>
                </div>
            </div>
        </div>
    );
}
