export default function GuestLayout({ children }) {
    return (
        <div className="conar-auth">

            {/* ── Panel izquierdo: marca CONAR ── */}
            <div className="conar-auth__brand">
                <div className="conar-auth__brand-inner">
                    <img
                        src="/storage/images/logos/logo-white.png"
                        alt="CONAR"
                        className="conar-auth__logo"
                    />
                    <span className="conar-auth__brand-sub">Instrumentación Industrial</span>
                    <span className="conar-auth__brand-sub">Bienvenido al panel de administracion</span>

                </div>
            </div>

            {/* ── Panel derecho: formulario ── */}
            <div className="conar-auth__form-panel">
                <div className="conar-auth__form-box">
                    {children}
                </div>
            </div>

        </div>
    );
}
