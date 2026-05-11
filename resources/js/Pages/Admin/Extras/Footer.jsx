import AdminLayout from "@/Layouts/AdminLayout";
import { ContactInfoCard } from "@/Pages/Admin/CMS/Contacto";

export default function Footer({ contactInfo }) {
    return (
        <AdminLayout>
            <div className="max-w-3xl">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-800">Footer</h1>
                    <p className="text-sm text-gray-500">Editá los datos que aparecen solamente en el pie de página.</p>
                </div>

                <ContactInfoCard
                    contactInfo={contactInfo}
                    submitUrl="/admin/extras/footer"
                    title="Datos del footer"
                    description="Estos datos se muestran solamente en el footer global de la web."
                    itemTitle="Iconos del footer"
                    itemDescription="Elegí si cada dato se muestra como correo, teléfono o WhatsApp. La web usa automáticamente el SVG correspondiente."
                    fixedPhoneLabel="Teléfono fijo del footer"
                    recipientsLabel="Correos que reciben presupuestos"
                    recipientsHelp="El formulario de presupuesto usa esta lista. Separá varios correos con coma."
                    saveLabel="Guardar Footer"
                />
            </div>
        </AdminLayout>
    );
}
