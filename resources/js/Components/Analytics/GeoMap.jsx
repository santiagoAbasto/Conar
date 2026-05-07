import "leaflet/dist/leaflet.css";
import { MapContainer, TileLayer, CircleMarker, Popup } from "react-leaflet";

export default function GeoMap({ points }) {
    const max = Math.max(...points.map((point) => point.page_views), 1);

    return (
        <div className="rounded-[24px] border border-white/70 bg-white p-6 shadow-[0_18px_44px_rgba(15,23,42,0.08)] ring-1 ring-slate-200/80">
            <div className="mb-5">
                <h2 className="text-base font-semibold text-slate-950">De dónde visitan</h2>
                <p className="text-xs text-slate-500">Ciudades desde donde llegaron personas en los últimos 30 días</p>
            </div>

            <div className="h-[380px] overflow-hidden rounded-[22px] border border-slate-200 shadow-inner">
                <MapContainer center={[-34.6037, -58.3816]} zoom={3} scrollWheelZoom={false} className="h-full w-full">
                    <TileLayer
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    />
                    {points.map((point) => (
                        <CircleMarker
                            key={`${point.city}-${point.country}-${point.latitude}-${point.longitude}`}
                            center={[point.latitude, point.longitude]}
                            radius={Math.max(6, Math.min(24, (point.page_views / max) * 24))}
                            pathOptions={{ color: "#0891b2", fillColor: "#06b6d4", fillOpacity: 0.35, weight: 2 }}
                        >
                            <Popup>
                                <strong>{point.city}, {point.country}</strong><br />
                                {point.page_views} vistas<br />
                                {point.sessions} visitas
                            </Popup>
                        </CircleMarker>
                    ))}
                </MapContainer>
            </div>
        </div>
    );
}
