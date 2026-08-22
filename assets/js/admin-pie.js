/**
 * GlobeTrotter — Admin Trip Status Distribution (React 18 Component)
 */

(function () {
    if (typeof React === 'undefined' || typeof ReactDOM === 'undefined') {
        return;
    }

    const { useState, useEffect } = React;

    const STATUS_CONFIG = {
        upcoming: { color: '#3b82f6', label: 'Upcoming' },
        ongoing:  { color: '#10b981', label: 'Ongoing' },
        completed:{ color: '#8b5cf6', label: 'Completed' }
    };

    function AdminTripStatusPie() {
        const [tripData, setTripData] = useState(() => window.ADMIN_PIE_DATA || { upcoming: 0, ongoing: 0, completed: 0 });
        const [hoveredSlice, setHoveredSlice] = useState(null);
        const [mousePos, setMousePos] = useState({ x: 0, y: 0 });

        useEffect(() => {
            const handleUpdate = () => {
                if (window.ADMIN_PIE_DATA) {
                    setTripData({ ...window.ADMIN_PIE_DATA });
                }
            };
            window.addEventListener('adminDataLoaded', handleUpdate);
            return () => window.removeEventListener('adminDataLoaded', handleUpdate);
        }, []);

        const total = (tripData.upcoming || 0) + (tripData.ongoing || 0) + (tripData.completed || 0);

        const items = [
            { key: 'upcoming',  value: tripData.upcoming || 0,  ...STATUS_CONFIG.upcoming },
            { key: 'ongoing',   value: tripData.ongoing || 0,   ...STATUS_CONFIG.ongoing },
            { key: 'completed', value: tripData.completed || 0, ...STATUS_CONFIG.completed }
        ].filter(item => item.value > 0);

        if (total === 0 || items.length === 0) {
            return (
                <div style={{
                    height: '240px',
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    color: '#94a3b8',
                    gap: '8px'
                }}>
                    <i className="fa-solid fa-chart-pie" style={{ fontSize: '2.5rem', opacity: 0.3 }}></i>
                    <p style={{ margin: 0, fontSize: '0.9rem', fontWeight: '500' }}>No trips registered yet</p>
                </div>
            );
        }

        // SVG Arc Calculations
        const size = 220;
        const center = size / 2;
        const outerRadius = 90;
        const innerRadius = 58;

        let accumulatedAngle = 0;
        const slices = items.map(item => {
            const percent = item.value / total;
            const angle = percent * 360;
            const startAngle = accumulatedAngle;
            const endAngle = accumulatedAngle + angle;
            accumulatedAngle = endAngle;

            const isFull = angle >= 359.99;
            const startRad = (startAngle - 90) * (Math.PI / 180);
            const endRad = (endAngle - 90) * (Math.PI / 180);

            let pathData;
            if (isFull) {
                // Draw a complete donut using two arcs
                pathData = `
                    M ${center} ${center - outerRadius}
                    A ${outerRadius} ${outerRadius} 0 1 0 ${center} ${center + outerRadius}
                    A ${outerRadius} ${outerRadius} 0 1 0 ${center} ${center - outerRadius}
                    M ${center} ${center - innerRadius}
                    A ${innerRadius} ${innerRadius} 0 1 1 ${center} ${center + innerRadius}
                    A ${innerRadius} ${innerRadius} 0 1 1 ${center} ${center - innerRadius}
                    Z
                `;
            } else {
                const x1 = center + outerRadius * Math.cos(startRad);
                const y1 = center + outerRadius * Math.sin(startRad);
                const x2 = center + outerRadius * Math.cos(endRad);
                const y2 = center + outerRadius * Math.sin(endRad);

                const x3 = center + innerRadius * Math.cos(endRad);
                const y3 = center + innerRadius * Math.sin(endRad);
                const x4 = center + innerRadius * Math.cos(startRad);
                const y4 = center + innerRadius * Math.sin(startRad);

                const largeArc = angle > 180 ? 1 : 0;
                pathData = `M ${x1} ${y1} A ${outerRadius} ${outerRadius} 0 ${largeArc} 1 ${x2} ${y2} L ${x3} ${y3} A ${innerRadius} ${innerRadius} 0 ${largeArc} 0 ${x4} ${y4} Z`;
            }

            return {
                ...item,
                percent: (percent * 100).toFixed(1),
                pathData
            };
        });

        return (
            <div 
                style={{ position: 'relative', width: '100%', height: '240px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                onMouseMove={(e) => {
                    const rect = e.currentTarget.getBoundingClientRect();
                    setMousePos({ x: e.clientX - rect.left, y: e.clientY - rect.top });
                }}
            >
                <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`} style={{ overflow: 'visible' }}>
                    <defs>
                        <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur stdDeviation="4" result="blur" />
                            <feComposite in="SourceGraphic" in2="blur" operator="over" />
                        </filter>
                    </defs>

                    {slices.map((slice) => {
                        const isHovered = hoveredSlice && hoveredSlice.key === slice.key;
                        return (
                            <path
                                key={slice.key}
                                d={slice.pathData}
                                fill={slice.color}
                                stroke="#ffffff"
                                strokeWidth="2"
                                style={{
                                    cursor: 'pointer',
                                    transition: 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)',
                                    transformOrigin: `${center}px ${center}px`,
                                    transform: isHovered ? 'scale(1.04)' : 'scale(1)',
                                    filter: isHovered ? 'url(#glow)' : 'drop-shadow(0 2px 4px rgba(0,0,0,0.1))',
                                    opacity: hoveredSlice && !isHovered ? 0.6 : 1
                                }}
                                onMouseEnter={() => setHoveredSlice(slice)}
                                onMouseLeave={() => setHoveredSlice(null)}
                            />
                        );
                    })}
                </svg>

                {/* Center Badge */}
                <div style={{
                    position: 'absolute',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    textAlign: 'center',
                    pointerEvents: 'none'
                }}>
                    <div style={{ fontSize: '1.5rem', fontWeight: '800', color: '#0f172a', lineHeight: '1', fontFamily: "'Outfit', sans-serif" }}>
                        {total}
                    </div>
                    <div style={{ fontSize: '0.68rem', fontWeight: '700', textTransform: 'uppercase', color: '#64748b', marginTop: '2px', letterSpacing: '0.04em' }}>
                        {total === 1 ? 'Trip' : 'Trips'}
                    </div>
                </div>

                {/* Floating Glassmorphism Tooltip */}
                {hoveredSlice && (
                    <div style={{
                        position: 'absolute',
                        left: `${mousePos.x + 12}px`,
                        top: `${mousePos.y - 30}px`,
                        background: 'rgba(15, 23, 42, 0.9)',
                        backdropFilter: 'blur(8px)',
                        border: '1px solid rgba(255, 255, 255, 0.15)',
                        borderRadius: '10px',
                        padding: '6px 12px',
                        color: '#ffffff',
                        fontSize: '12px',
                        fontWeight: '600',
                        pointerEvents: 'none',
                        zIndex: 10,
                        boxShadow: '0 8px 20px rgba(0,0,0,0.25)',
                        whiteSpace: 'nowrap'
                    }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                            <span style={{
                                width: '8px',
                                height: '8px',
                                borderRadius: '50%',
                                backgroundColor: hoveredSlice.color,
                                display: 'inline-block'
                            }}></span>
                            <span>{hoveredSlice.label}: <strong>{hoveredSlice.value}</strong> ({hoveredSlice.percent}%)</span>
                        </div>
                    </div>
                )}
            </div>
        );
    }

    const rootEl = document.getElementById('admin-pie-root');
    if (rootEl && ReactDOM && ReactDOM.createRoot) {
        const root = ReactDOM.createRoot(rootEl);
        root.render(<AdminTripStatusPie />);
    }
})();
