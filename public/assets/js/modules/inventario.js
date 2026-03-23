/**
 * Prooriente WMS - Inventario & Conteos Module
 */
window.Inventario = {
    currentConteo: null,

    /* --- UI GENERATORS --- */
    getNuevoConteoHTML: function() {
        return `
            <div style="background:white; border-radius:12px; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; max-width:600px; margin:0 auto;">
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="width:60px; height:60px; background:#f0fdf4; color:#22c55e; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; font-size:1.5rem;">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <h3 style="margin:0; color:#0f172a;">Iniciar Nuevo Conteo</h3>
                    <p style="color:#64748b; font-size:0.9rem; margin-top:5px;">Seleccione el tipo de inventario a realizar</p>
                </div>

                <div style="display:grid; gap:15px; margin-bottom:25px;">
                    <button class="btn-primary" style="background:#f8fafc; color:#334155; border:1px solid #e2e8f0; height:auto; padding:20px; text-align:left; display:flex; align-items:center; gap:15px; transition:all 0.2s;" onclick="window.Inventario.startConteo('General')">
                        <div style="width:40px; height:40px; background:#eff6ff; color:#3b82f6; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:700; font-size:1rem;">Conteo General</div>
                            <div style="font-size:0.8rem; color:#64748b; font-weight:400;">Inventario total de todos los productos y ubicaciones.</div>
                        </div>
                        <i class="fa-solid fa-chevron-right" style="color:#cbd5e1;"></i>
                    </button>

                    <button class="btn-primary" style="background:#f8fafc; color:#334155; border:1px solid #e2e8f0; height:auto; padding:20px; text-align:left; display:flex; align-items:center; gap:15px; opacity:0.6; cursor:not-allowed;">
                        <div style="width:40px; height:40px; background:#f5f3ff; color:#8b5cf6; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:700; font-size:1rem;">Por Ubicación</div>
                            <div style="font-size:0.8rem; color:#64748b; font-weight:400;">Cíclico dirigido a zonas o pasillos específicos.</div>
                        </div>
                    </button>
                    
                    <button class="btn-primary" style="background:#f8fafc; color:#334155; border:1px solid #e2e8f0; height:auto; padding:20px; text-align:left; display:flex; align-items:center; gap:15px; opacity:0.6; cursor:not-allowed;">
                        <div style="width:40px; height:40px; background:#fff7ed; color:#f97316; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                            <i class="fa-solid fa-barcode"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:700; font-size:1rem;">Por Referencia</div>
                            <div style="font-size:0.8rem; color:#64748b; font-weight:400;">Conteo selectivo de productos específicos.</div>
                        </div>
                    </button>
                </div>

                <div id="conteo-active-panel" style="display:none; border-top:2px dashed #e2e8f0; padding-top:20px;">
                    <div style="background:#0f172a; border-radius:12px; padding:20px; color:white; margin-bottom:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:4px;">Conteo en Progreso</div>
                                <div id="active-conteo-type" style="font-size:1.25rem; font-weight:700;">GENERAL</div>
                            </div>
                            <div style="text-align:right;">
                                <div id="conteo-timer" style="font-family:monospace; font-size:1.25rem; font-weight:700;">00:00:00</div>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid; gap:15px;">
                        <div class="input-group">
                            <label style="font-weight:600; color:#475569;">Escanear Ubicación / Producto</label>
                            <div style="display:flex; gap:10px;">
                                <input type="text" id="conteo-scan-field" class="input-field" placeholder="Escanee código de barras..." style="flex:1;">
                                <button class="btn-primary" style="width:50px; background:#6366f1;"><i class="fa-solid fa-qrcode"></i></button>
                            </div>
                        </div>
                        
                        <div id="conteo-items-list" style="max-height:300px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; background:#f8fafc;">
                            <div style="padding:40px; text-align:center; color:#94a3b8;">
                                <i class="fa-solid fa-barcode" style="font-size:2rem; margin-bottom:10px; opacity:0.3;"></i>
                                <br>Comience a escanear productos
                            </div>
                        </div>

                        <button class="btn-primary" style="background:#ef4444; margin-top:10px;" onclick="window.Inventario.finishConteo()">
                            <i class="fa-solid fa-stop"></i> Finalizar y Guardar Conteo
                        </button>
                    </div>
                </div>
            </div>
        `;
    },

    startConteo: async function(tipo) {
        try {
            const res = await window.api.post('/inventario/conteo/nuevo', { tipo: tipo });
            if (res.error) return window.showToast(res.message, 'error');
            
            this.currentConteo = res.data;
            document.getElementById('conteo-active-panel').style.display = 'block';
            document.getElementById('active-conteo-type').innerText = tipo.toUpperCase();
            
            window.showToast('Conteo iniciado con éxito', 'success');
            
            // Start Timer UI (simplistic)
            this.startTimer();
        } catch (e) {
            window.showToast('Error al iniciar: ' + e.message, 'error');
        }
    },

    startTimer: function() {
        let seconds = 0;
        const display = document.getElementById('conteo-timer');
        if (!display) return;
        
        if (this.timerInterval) clearInterval(this.timerInterval);
        
        this.timerInterval = setInterval(() => {
            seconds++;
            const h = Math.floor(seconds / 3600).toString().padStart(2, '0');
            const m = Math.floor((seconds % 3600) / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            display.innerText = `${h}:${m}:${s}`;
        }, 1000);
    },

    finishConteo: async function() {
        if (!this.currentConteo) return;
        
        try {
            const res = await window.api.post(`/inventario/conteo/${this.currentConteo.id}/finalizar`);
            if (res.error) return window.showToast(res.message, 'error');
            
            clearInterval(this.timerInterval);
            window.showToast('Conteo finalizado y guardado', 'success');
            
            // Go to history
            window.openSubView('conteos_historial', 'Historial Conteos');
        } catch (e) {
            window.showToast('Error al finalizar: ' + e.message, 'error');
        }
    },

    getHistorialConteosHTML: function() {
        return `
            <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0;">
                <h4 style="margin:0; color:#0f172a; margin-bottom:16px;">Historial de Conteos</h4>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom:2px solid #e2e8f0; color:#64748b;">
                                <th style="padding:10px 8px;">ID</th>
                                <th style="padding:10px 8px;">Fecha</th>
                                <th style="padding:10px 8px;">Tipo</th>
                                <th style="padding:10px 8px;">Estado</th>
                                <th style="padding:10px 8px;">Duración</th>
                                <th style="padding:10px 8px; width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="conteos-history-tbody">
                            <tr><td colspan="6" style="text-align:center; padding:20px; color:#94a3b8;">Cargando historial...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    },

    loadHistorialConteos: async function() {
        const tbody = document.getElementById('conteos-history-tbody');
        if(!tbody) return;
        
        try {
            // This endpoint might need to be created in the backend if not existing
            // For now, let's assume it exists or we'll mock it if it fails
            const res = await window.api.get('/inventario/conteos');
            let html = '';
            
            if (res.data && res.data.length > 0) {
                res.data.forEach(c => {
                    const start = c.hora_inicio || '00:00';
                    const end = c.hora_fin || '...';
                    html += `
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:12px 8px; font-weight:600;">#${c.id}</td>
                            <td style="padding:12px 8px; color:#475569;">${c.fecha_movimiento}</td>
                            <td style="padding:12px 8px;"><span style="background:#f1f5f9; padding:4px 8px; border-radius:6px; font-size:0.8rem;">${c.tipo_conteo}</span></td>
                            <td style="padding:12px 8px;"><span style="color:${c.estado === 'Abierto' ? '#f59e0b' : '#10b981'}; font-weight:600;">${c.estado}</span></td>
                            <td style="padding:12px 8px; color:#64748b; font-family:monospace;">${start} - ${end}</td>
                            <td style="padding:12px 8px; text-align:center;">
                                <button class="btn-primary" style="background:#f1f5f9; color:#475569; width:30px; height:30px; padding:0; border-radius:4px;"><i class="fa-solid fa-eye"></i></button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#94a3b8;">No se registran conteos previos.</td></tr>';
            }
            tbody.innerHTML = html;
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#ef4444;">Error al cargar historial.</td></tr>';
        }
    },

    initNuevoConteo: function() {
        this.currentConteo = null;
        if(this.timerInterval) clearInterval(this.timerInterval);
    }
};
